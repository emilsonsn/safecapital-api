<?php

namespace App\Services\Client;

use App\Enums\InstallmentStatusEnum;
use App\Models\Client;
use App\Models\ClientInstallment;
use App\Traits\BtgTrait;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Support\Str;
use App\Mail\InstallmentBoletoMail;
use Illuminate\Support\Facades\Mail;

class ClientInstallmentService
{
    use BtgTrait;

    public function listByClient($clientId)
    {
        try {
            $installments = ClientInstallment::where('client_id', $clientId)
                ->orderBy('installment_number')
                ->get();

            return [
                'status' => true,
                'data' => $installments
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'error' => $e->getMessage(),
                'statusCode' => 400
            ];
        }
    }    

    public function generateNextInstallment(Client $client)
    {
        try {
            DB::beginTransaction();

            $totalInstallments = $client->payment_form->installments();

            $lastInstallment = ClientInstallment::where('client_id', $client->id)
                ->orderByDesc('installment_number')
                ->first();

            if (! $lastInstallment) {
                $referenceDate = $client->actived_at ?? $client->updated_at;
                $dueDate = $this->calculateFirstDueDate($referenceDate);
                $installmentNumber = 1;
            } else {
                if ($lastInstallment->installment_number >= $totalInstallments) {
                    DB::rollBack();
                    return ['status' => true];
                }

                $cycleDay = $lastInstallment->due_date->day;
                $nextMonth = Carbon::parse($lastInstallment->due_date)->copy()->addMonth();

                if ($cycleDay > $nextMonth->daysInMonth) {
                    $dueDate = $nextMonth->endOfMonth();
                } else {
                    $dueDate = $nextMonth->day($cycleDay);
                }

                $installmentNumber = $lastInstallment->installment_number + 1;
            }

            $amountPerInstallment = round(
                $client->policy_value / $totalInstallments,
                2
            );

            $this->prepareBtg($amountPerInstallment);            

            $externalReference = (string) Str::uuid();

            $payment = $this->makeBtgBoletoPayment(
                externalReference: $externalReference,
                dueDate: $dueDate->format('Y-m-d'),
                client: $client
            );

            if (isset($payment['error'])) {
                throw new Exception('Erro ao gerar boleto BTG da parcela ' . $installmentNumber);
            }

            $installment = ClientInstallment::create([
                'client_id' => $client->id,
                'installment_number' => $installmentNumber,
                'amount' => $amountPerInstallment,
                'due_date' => $dueDate,
                'provider_external_id' => $payment['bankSlipId'] ?? $externalReference,
                'provider_correlation_id' => $payment['correlationId'] ?? $externalReference,
                'digitable_line' => $payment['digitableLine'] ?? null,
                'boleto_url' => null,
                'boleto_barcode' => $payment['barCode'] ?? null,
                'status' => InstallmentStatusEnum::Open,
                'meta' => [
                    'btg_status' => $payment['status'] ?? null,
                    'our_number' => $payment['ourNumber'] ?? null,
                    'our_number_digit' => $payment['ourNumberDigit'] ?? null,
                    'pix' => $payment['pixInfo'] ?? null,
                ],
            ]);

            DB::commit();

            Mail::to($client->email)
                ->send(new InstallmentBoletoMail($installment));

            $installment->update([
                'boleto_sent_at' => now()
            ]);

            return ['status' => true];

        } catch (Exception $e) {
            DB::rollBack();

            return [
                'status' => false,
                'error' => $e->getMessage(),
                'statusCode' => 400
            ];
        }
    }    

    private function calculateFirstDueDate(Carbon $activedAt): Carbon
    {
        $day = (int) $activedAt->format('d');

        $nextMonth = $activedAt->copy()->addMonth();

        if ($day >= 5 && $day <= 20) {
            if ($nextMonth->daysInMonth < 30) {
                return $nextMonth->endOfMonth();
            }

            return $nextMonth->day(30);
        }

        return $nextMonth->day(5);
    }

    public function markAsPaid(ClientInstallment $installment)
    {
        $installment->update([
            'status' => InstallmentStatusEnum::Paid,
            'paid_at' => now(),
            'paid_amount' => $installment->amount
        ]);

        return ['status' => true];
    }

    public function uploadPaymentProof($request, ClientInstallment $installment)
    {
        try {
            if (! $request->hasFile('file')) {
                throw new Exception('Arquivo não enviado');
            }

            $file = $request->file('file');

            $path = $file->store('installment-proofs', 'public');

            $installment->update([
                'boleto_uploaded_path' => $path,
                'status' => InstallmentStatusEnum::BoletoSent,
            ]);

            return [
                'status' => true,
                'data' => $installment
            ];

        } catch (Exception $e) {
            return [
                'status' => false,
                'error' => $e->getMessage(),
                'statusCode' => 400
            ];
        }
    }    
}