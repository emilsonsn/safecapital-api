<?php

namespace App\Services\Finance;

use App\Enums\ClientStatusEnum;
use App\Enums\InstallmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentFormEnum;
use App\Enums\UserRoleEnum;
use App\Models\Client;
use App\Models\ClientInstallment;
use App\Models\Invoice;
use App\Models\User;
use App\Traits\BtgTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class InvoiceService
{
    use BtgTrait;

    public function close(Carbon $closingDate): int
    {
        $closingDate = $closingDate->copy()->startOfDay();
        if (! in_array($closingDate->day, [5, 20], true)) {
            throw new RuntimeException('O fechamento só pode ocorrer nos dias 05 ou 20.');
        }

        $dueDate = $closingDate->day === 5
            ? $closingDate->copy()->day(15)
            : $closingDate->copy()->day(30);

        $created = 0;
        User::query()->whereHas('clients', function ($query) use ($closingDate) {
            $query->where('status', ClientStatusEnum::Active->value)
                ->where('payment_form', PaymentFormEnum::Invoiced->value)
                ->whereDate('actived_at', '<=', $closingDate);
        })->chunkById(100, function ($users) use ($closingDate, $dueDate, &$created) {
            foreach ($users as $user) {
                $created += $this->closeForUser($user, $closingDate, $dueDate) ? 1 : 0;
            }
        });

        return $created;
    }

    private function closeForUser(User $user, Carbon $closingDate, Carbon $dueDate): ?Invoice
    {
        return DB::transaction(function () use ($user, $closingDate, $dueDate) {
            if (Invoice::where('user_id', $user->id)->whereDate('closing_date', $closingDate)->exists()) {
                return null;
            }

            $installments = collect();
            $clients = Client::query()->where('user_id', $user->id)
                ->where('status', ClientStatusEnum::Active->value)
                ->where('payment_form', PaymentFormEnum::Invoiced->value)
                ->whereDate('actived_at', '<=', $closingDate)->lockForUpdate()->get();

            foreach ($clients as $client) {
                $activationDay = Carbon::parse($client->actived_at)->day;
                $clientClosingDay = ($activationDay <= 5 || $activationDay > 20) ? 5 : 20;
                if ($clientClosingDay !== $closingDate->day) {
                    continue;
                }

                $number = $client->installments()->count() + 1;
                if ($number > 12) {
                    continue;
                }

                $amount = $number === 12
                    ? round($client->policy_value - round($client->policy_value / 12, 2) * 11, 2)
                    : round($client->policy_value / 12, 2);

                $installments->push(ClientInstallment::create([
                    'client_id' => $client->id, 'installment_number' => $number,
                    'amount' => $amount, 'due_date' => $dueDate,
                    'status' => InstallmentStatusEnum::Open,
                ]));
            }

            if ($installments->isEmpty()) {
                return null;
            }

            $amount = round($installments->sum('amount'), 2);
            $reference = (string) Str::uuid();
            $this->prepareBtg($amount);
            $payment = $this->makeBtgInvoiceBoleto($reference, $dueDate->format('Y-m-d'), $user);
            if (isset($payment['error'])) {
                throw new RuntimeException($payment['error']);
            }

            $invoice = Invoice::create([
                'user_id' => $user->id, 'closing_date' => $closingDate, 'due_date' => $dueDate,
                'amount' => $amount, 'status' => InvoiceStatusEnum::Open,
                'provider_external_id' => $payment['bankSlipId'] ?? $reference,
                'provider_correlation_id' => $payment['correlationId'] ?? null,
                'digitable_line' => $payment['digitableLine'] ?? null,
                'boleto_url' => $payment['url'] ?? $payment['bankSlipUrl'] ?? null,
                'boleto_barcode' => $payment['barCode'] ?? null, 'meta' => $payment,
            ]);
            $invoice->installments()->attach($installments->mapWithKeys(fn ($item) => [
                $item->id => ['amount' => $item->amount],
            ])->all());

            return $invoice;
        }, 3);
    }

    public function listForUser(User $user)
    {
        $this->syncOverdueInvoices();

        return $user->invoices()->with('installments.client')->latest('due_date')->paginate(15);
    }

    public function listClientUsers(array $filters): LengthAwarePaginator
    {
        $this->syncOverdueInvoices();

        $search = trim((string) ($filters['search'] ?? ''));
        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);

        return User::query()
            ->where('role', UserRoleEnum::Client->value)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                });
            })
            ->withCount([
                'invoices',
                'invoices as open_invoices_count' => fn ($query) => $query->where('status', InvoiceStatusEnum::Open->value),
                'invoices as paid_invoices_count' => fn ($query) => $query->where('status', InvoiceStatusEnum::Paid->value),
                'invoices as overdue_invoices_count' => fn ($query) => $query->where('status', InvoiceStatusEnum::Overdue->value),
            ])
            ->withSum('invoices as invoices_total_amount', 'amount')
            ->orderBy('name')
            ->orderBy('surname')
            ->paginate($perPage);
    }

    public function listForAdmin(User $clientUser, array $filters): LengthAwarePaginator
    {
        $this->assertClientUser($clientUser);
        $this->syncOverdueInvoices();

        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);
        $statuses = collect(explode(',', (string) ($filters['status'] ?? '')))
            ->map(fn (string $status) => trim($status))
            ->filter()
            ->values()
            ->all();

        return $clientUser->invoices()
            ->with(['installments.client', 'paidBy:id,name,surname'])
            ->when($statuses !== [], fn ($query) => $query->whereIn('status', $statuses))
            ->when(! empty($filters['due_from']), fn ($query) => $query->whereDate('due_date', '>=', $filters['due_from']))
            ->when(! empty($filters['due_to']), fn ($query) => $query->whereDate('due_date', '<=', $filters['due_to']))
            ->latest('due_date')
            ->paginate($perPage);
    }

    public function markAsPaid(User $clientUser, Invoice $invoice, User $admin, array $payment): Invoice
    {
        $this->assertClientUser($clientUser);

        return DB::transaction(function () use ($clientUser, $invoice, $admin, $payment) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($invoice->user_id !== $clientUser->id) {
                throw ValidationException::withMessages([
                    'invoice' => 'A fatura não pertence ao cliente informado.',
                ]);
            }

            if ($invoice->status === InvoiceStatusEnum::Cancelled) {
                throw ValidationException::withMessages([
                    'invoice' => 'Uma fatura cancelada não pode ser marcada como paga.',
                ]);
            }

            if ($invoice->status === InvoiceStatusEnum::Paid) {
                return $invoice->load(['installments.client', 'paidBy:id,name,surname']);
            }

            $paidAt = isset($payment['paid_at']) ? Carbon::parse($payment['paid_at']) : now();
            $invoice->update([
                'status' => InvoiceStatusEnum::Paid,
                'paid_at' => $paidAt,
                'paid_by_user_id' => $admin->id,
                'payment_method' => 'MANUAL_ADMIN',
                'payment_reference' => $payment['payment_reference'] ?? null,
                'payment_notes' => $payment['payment_notes'] ?? null,
            ]);

            $invoice->installments()
                ->whereIn('status', [
                    InstallmentStatusEnum::Open->value,
                    InstallmentStatusEnum::BoletoSent->value,
                    InstallmentStatusEnum::Overdue->value,
                ])
                ->update([
                    'status' => InstallmentStatusEnum::Paid->value,
                    'paid_at' => $paidAt,
                    'paid_amount' => DB::raw('amount'),
                ]);

            return $invoice->fresh(['installments.client', 'paidBy:id,name,surname']);
        }, 3);
    }

    private function syncOverdueInvoices(): void
    {
        Invoice::query()
            ->where('status', InvoiceStatusEnum::Open->value)
            ->whereDate('due_date', '<', now()->startOfDay())
            ->update(['status' => InvoiceStatusEnum::Overdue->value]);
    }

    private function assertClientUser(User $user): void
    {
        if ($user->role !== UserRoleEnum::Client->value) {
            throw ValidationException::withMessages([
                'client' => 'O usuário informado não possui o perfil Client.',
            ]);
        }
    }
}
