<?php

namespace App\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait MercadoPagoTrait
{
    private string $clientEmail;
    private float $value;
    private string $mpToken;

    public function prepareMercadoPago($clientEmail, $value)
    {
        $this->mpToken = env('MERCADO_PAGO_ACCESS_TOKEN');
        $this->clientEmail = $clientEmail;
        $this->value = (float) $value;
    }

    public function makePayment()
    {
        $client = new Client();
        $url = "https://api.mercadopago.com/v1/payments";

        try {
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$this->mpToken}",
                    'Content-Type' => 'application/json',
                    'X-Idempotency-Key' => (string) Str::uuid(),
                ],
                'json' => [
                    'transaction_amount' => $this->value,
                    'payment_method_id' => 'pix',
                    'payer' => [
                        'email' => $this->clientEmail,
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data;

        } catch (RequestException $e) {
            $errorResponse = $e->getResponse() ? json_decode($e->getResponse()->getBody()->getContents(), true) : [];

            Log::error('Erro ao criar pagamento: ' . $e->getMessage());
            Log::error('Detalhes do erro: ' . json_encode($errorResponse, JSON_PRETTY_PRINT));

            return [
                'error' => 'Erro ao criar pagamento',
                'details' => $e->getMessage(),
                'response' => $errorResponse
            ];
        }
    }
}