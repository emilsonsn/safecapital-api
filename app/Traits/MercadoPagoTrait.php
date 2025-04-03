<?php

namespace App\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

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

    public function makePayment(string $externalReference)
    {
        $client = new Client();
        $url = "https://api.mercadopago.com/checkout/preferences";
    
        try {
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$this->mpToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'items' => [
                        [
                            'title' => 'Taxa do seguro',
                            'description' => 'Taxa do seguro',
                            'quantity' => 1,
                            'currency_id' => 'BRL',
                            'unit_price' => $this->value
                        ]
                    ],
                    'payer' => [
                        'email' => $this->clientEmail,
                    ],
                    'external_reference' => (string) $externalReference
                ],
            ]);
    
            $data = json_decode($response->getBody()->getContents(), true);
    
            return $data;
    
        } catch (RequestException $e) {
            $errorResponse = $e->getResponse() ? json_decode($e->getResponse()->getBody()->getContents(), true) : [];
    
            Log::error('Erro ao criar preference de pagamento: ' . $e->getMessage());
            Log::error('Detalhes do erro: ' . json_encode($errorResponse, JSON_PRETTY_PRINT));
    
            return [
                'error' => 'Erro ao criar preference de pagamento',
                'details' => $e->getMessage(),
                'response' => $errorResponse
            ];
        }
    }    
}