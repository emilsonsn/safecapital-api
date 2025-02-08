<?php

namespace App\Services\Routine;

use App\Enums\ClientStatusEnum;
use App\Models\ClientPayment;
use Exception;

class RoutineService
{

    public function verifyClientPayments()
    {
        try {
            $clientPayments = ClientPayment::where('status', 'pending')
                ->get();

            foreach ($clientPayments as $clientPayment) {
                $paymentId = $clientPayment->id;
    
                $payment = $this->getPaymentStatus($paymentId);
    
                if (!$payment) {                    
                    return response()->json(['error' => 'Pagamento não encontrado'], 404);
                }
    
                $clientPayment = ClientPayment::where('external_id', $payment['id'])->first();
    
                if (!$clientPayment) {                    
                    return response()->json(['error' => 'Pagamento não registrado no sistema'], 404);
                }
    
                $clientPayment->status = $payment['status'];
                $clientPayment->save();
    
                if ($payment['status'] === 'approved'){
                    $clientPayment->client->status = ClientStatusEnum::WaitingContract->value;
                    $clientPayment->client->save();
                }
            }
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    private function getPaymentStatus($paymentId)
    {
        $accessToken = env('MERCADO_PAGO_ACCESS_TOKEN');
        $url = "https://api.mercadopago.com/v1/payments/{$paymentId}";

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->get($url, [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            return null;
        }
    }
}