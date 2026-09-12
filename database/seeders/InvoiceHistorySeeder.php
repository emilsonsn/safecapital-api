<?php

namespace Database\Seeders;

use App\Enums\ClientStatusEnum;
use App\Enums\InstallmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Client;
use App\Models\ClientInstallment;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Recria retroativamente o histórico de faturas (invoices) e parcelas
 * (client_installments) que deveriam existir desde a ativação de cada
 * contrato, para bases (como a de produção clonada) que nunca tiveram
 * esses registros gerados. Todo o histórico é criado já como PAGO, e
 * NENHUMA chamada é feita ao gateway BTG nem envio de e-mail: apenas
 * registros em banco.
 *
 * Regras replicadas de App\Services\Finance\InvoiceService::close():
 * - Só contratos (clients) com status Active entram.
 * - O "dia de fechamento" do contrato é definido pelo dia de ativação:
 *   dias 05 a 20 -> fecha todo dia 20 (vence dia 30);
 *   dias 21 a 04 -> fecha todo dia 05 (vence dia 15).
 * - Uma fatura agrupa, por (usuário, data de fechamento), as parcelas de
 *   todos os contratos daquele usuário que fecham naquela data.
 * - A forma de pagamento (payment_form) define quantas parcelas compõem
 *   uma vigência (payment_form->installments()): 12 para "Invoiced"
 *   (parcelado mensal) e 1 para "InCash" (à vista, fatura única com o
 *   valor cheio da apólice, cobrada uma vez por vigência de 12 meses).
 *
 * Regra adicional exclusiva deste seeder (histórico):
 * - Ao completar a vigência (12 parcelas mensais, ou a fatura única anual
 *   no caso à vista), entende-se que o contrato foi renovado e uma nova
 *   vigência se inicia, repetindo-se indefinidamente até hoje.
 *   (Em produção, o InvoiceService atual PARA ao final da vigência e não
 *   renova sozinho.)
 *
 * Executar manualmente com:
 *   php artisan db:seed --class="Database\Seeders\InvoiceHistorySeeder"
 */
class InvoiceHistorySeeder extends Seeder
{
    private const MONTHS_PER_TERM = 12;

    public function run(): void
    {
        $today = Carbon::now()->startOfDay();

        $usersProcessed = 0;
        $clientsProcessed = 0;
        $invoicesCreated = 0;
        $installmentsCreated = 0;

        User::query()
            ->where('role', UserRoleEnum::Client->value)
            ->whereHas('clients', function ($query) {
                $query->where('status', ClientStatusEnum::Active->value);
            })
            ->chunkById(50, function ($users) use ($today, &$usersProcessed, &$clientsProcessed, &$invoicesCreated, &$installmentsCreated) {
                foreach ($users as $user) {
                    $result = $this->seedForUser($user, $today);
                    $usersProcessed++;
                    $clientsProcessed += $result['clients'];
                    $invoicesCreated += $result['invoices'];
                    $installmentsCreated += $result['installments'];
                }
            });

        $this->command?->info('Usuários (imobiliárias) processados: '.$usersProcessed);
        $this->command?->info('Contratos (clients) considerados: '.$clientsProcessed);
        $this->command?->info('Faturas históricas criadas: '.$invoicesCreated);
        $this->command?->info('Parcelas históricas criadas: '.$installmentsCreated);
    }

    private function seedForUser(User $user, Carbon $today): array
    {
        $clients = Client::query()
            ->where('user_id', $user->id)
            ->where('status', ClientStatusEnum::Active->value)
            ->get()
            ->filter(fn (Client $client) => $this->activationDate($client) !== null)
            ->values();

        if ($clients->isEmpty()) {
            return ['clients' => 0, 'invoices' => 0, 'installments' => 0];
        }

        $byClosingDate = [];

        foreach ($clients as $client) {
            $activatedAt = $this->activationDate($client);
            $bucketDay = ($activatedAt->day < 5 || $activatedAt->day > 20) ? 5 : 20;

            $installmentsPerTerm = $client->payment_form->installments();
            $stepMonths = intdiv(self::MONTHS_PER_TERM, $installmentsPerTerm);

            $closingDate = $this->firstClosingDate($activatedAt, $bucketDay);
            $occurrence = 1;

            while ($closingDate->lte($today)) {
                $installmentNumber = (($occurrence - 1) % $installmentsPerTerm) + 1;

                $byClosingDate[$closingDate->toDateString()][] = [
                    'client_id' => $client->id,
                    'installment_number' => $installmentNumber,
                    'amount' => $this->installmentAmount((float) $client->policy_value, $installmentNumber, $installmentsPerTerm),
                    'due_date' => $this->dueDateFor($closingDate),
                ];

                $closingDate = $closingDate->copy()->addMonthsNoOverflow($stepMonths)->day($bucketDay);
                $occurrence++;
            }
        }

        if (empty($byClosingDate)) {
            return ['clients' => $clients->count(), 'invoices' => 0, 'installments' => 0];
        }

        ksort($byClosingDate);

        $invoicesCreated = 0;
        $installmentsCreated = 0;

        foreach ($byClosingDate as $closingDateStr => $items) {
            $created = DB::transaction(function () use ($user, $closingDateStr, $items) {
                $closingDate = Carbon::parse($closingDateStr);

                if (Invoice::where('user_id', $user->id)->whereDate('closing_date', $closingDate)->exists()) {
                    return null;
                }

                $dueDate = $items[0]['due_date'];
                $installments = collect();

                foreach ($items as $item) {
                    $installments->push(ClientInstallment::create([
                        'client_id' => $item['client_id'],
                        'installment_number' => $item['installment_number'],
                        'amount' => $item['amount'],
                        'paid_amount' => $item['amount'],
                        'due_date' => $dueDate,
                        'status' => InstallmentStatusEnum::Paid,
                        'paid_at' => $dueDate,
                    ]));
                }

                $invoice = Invoice::create([
                    'user_id' => $user->id,
                    'closing_date' => $closingDate,
                    'due_date' => $dueDate,
                    'amount' => round($installments->sum('amount'), 2),
                    'status' => InvoiceStatusEnum::Paid,
                    'paid_at' => $dueDate,
                    'payment_method' => 'HISTORICAL_SEED',
                    'payment_notes' => 'Registro histórico gerado retroativamente a partir da data de ativação do contrato.',
                    'meta' => ['source' => 'historical_seed'],
                ]);

                $invoice->installments()->attach($installments->mapWithKeys(fn ($item) => [
                    $item->id => ['amount' => $item->amount],
                ])->all());

                return $installments->count();
            });

            if ($created !== null) {
                $invoicesCreated++;
                $installmentsCreated += $created;
            }
        }

        return ['clients' => $clients->count(), 'invoices' => $invoicesCreated, 'installments' => $installmentsCreated];
    }

    private function activationDate(Client $client): ?Carbon
    {
        $value = $client->actived_at ?? $client->updated_at;

        return $value ? Carbon::parse($value)->startOfDay() : null;
    }

    private function firstClosingDate(Carbon $activatedAt, int $bucketDay): Carbon
    {
        $candidate = $activatedAt->copy()->day($bucketDay);

        if ($candidate->lt($activatedAt)) {
            $candidate = $candidate->addMonthNoOverflow()->day($bucketDay);
        }

        return $candidate;
    }

    private function dueDateFor(Carbon $closingDate): Carbon
    {
        return $closingDate->day === 5
            ? $closingDate->copy()->day(15)
            : $closingDate->copy()->day(30);
    }

    private function installmentAmount(float $policyValue, int $installmentNumber, int $installmentsPerTerm): float
    {
        return $installmentNumber === $installmentsPerTerm
            ? round($policyValue - round($policyValue / $installmentsPerTerm, 2) * ($installmentsPerTerm - 1), 2)
            : round($policyValue / $installmentsPerTerm, 2);
    }
}
