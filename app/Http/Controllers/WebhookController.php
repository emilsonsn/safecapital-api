<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\Client\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ClientPayment;
use App\Enums\ClientStatusEnum;
use Exception;

class WebhookController extends Controller
{

    private $clientService;

    public function __construct(ClientService $clientService) {
        $this->clientService = $clientService;
    }

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

            $this->clientService
                ->makePolicy($clientPayment->id);

            $clientPayment->client->status = ClientStatusEnum::WaitingPolicy->value;
            $clientPayment->client->save();
            
            return response()->json(['success' => true], 200);
        } catch (Exception $e) {
            Log::error('Erro no webhook do Mercado Pago: ' . $e->getMessage());
            return response()->json(['error' => 'Erro interno'], 500);
        }
    }

    /**
     * Recebe o webhook da D4Sign.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function d4sign(Request $request): JsonResponse
    {
        try {
            Log::info('Webhook D4Sign recebido:', $request->all());

            $uuid = $request->input('uuid');
            $typePost = $request->input('type_post');

            if (!$uuid || !$typePost) {
                return response()->json(['error' => 'Dados incompletos'], 400);
            }

            $client = Client::where('doc4sign_document_uuid', $uuid)->first();

            if (!$client) {
                Log::warning("Cliente não encontrado para UUID do documento: $uuid");
                return response()->json(['error' => 'Cliente não encontrado'], 404);
            }

            switch ($typePost) {
                case 1:
                case 4:
                    $client->status = ClientStatusEnum::WaitingContract->value;
                    break;        
                case 3:
                    $client->status = ClientStatusEnum::Inactive->value;
                    break;            
                default:
                    return response()
                        ->json(
                            data: ['message' => 'Evento ignorado'],
                            status: 200
                        );
            }
            
            $client->save();

            return response()
                ->json(
                    data: ['success' => true],
                    status: 200
                );
        } catch (Exception $e) {
            Log::error('Erro no webhook da D4Sign: ' . $e->getMessage());
            return response()->json(
                data: ['error' => 'Erro interno'],
                status: 500
            );
        }
    }

    /**
     * Consulta o status de um pagamento no Mercado Pago.
     *
     * @param string $paymentId
     * @return array|null
     */
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