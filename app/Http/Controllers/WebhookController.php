<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ClientPayment;
use App\Enums\ClientStatusEnum;
use Exception;

class WebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        try {
            Log::info('Webhook recebido:', $request->all());

            if ($request->type !== 'payment') {
                return response()->json(
                    data: ['message' => 'Ignorado - tipo diferente de payment'],
                    status: 200
                );
            }            

            if (!isset($request->data['id'])) {
                return response()->json(
                    data: ['error' => 'ID de pagamento não encontrado'],
                    status: 400
                );
            }

            $paymentId = $request->data['id'];

            $payment = $this->getPaymentStatus($paymentId);

            if (!$payment) {
                Log::error("Pagamento não encontrado ($paymentId)");
                return response()->json(['error' => 'Pagamento não encontrado'], 404);
            }

            $clientPayment = ClientPayment::where('external_id', $payment['external_reference'])
                ->first();

            if (!$clientPayment) {
                Log::info("Pagamento não registrado no sistema ($paymentId)");
                return response()->json(['error' => 'Pagamento não registrado no sistema'], 404);
            }

            $clientPayment->status = $payment['status'];
            $clientPayment->save();

            if ($payment['status'] !== 'approved'){
                return response()->json(
                    data: ['message' => "Pagamento não aprovado"],
                    status: 200
                );
            }

            $clientPayment->client->status = ClientStatusEnum::WaitingContract->value;
            $clientPayment->client->save();
            
            return response()->json(['success' => true], 200);
        } catch (Exception $e) {
            Log::error('Erro no webhook do Mercado Pago: ' . $e->getMessage());
            return response()->json(['error' => 'Erro interno'], 500);
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
            Log::error('Erro ao consultar pagamento: ' . $e->getMessage());
            return null;
        }
    }
}