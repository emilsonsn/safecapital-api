<?php

namespace App\Traits;

use GuzzleHttp\Client;
use App\Models\Client as ClientModel;
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

    public function makeBoletoPayment(
        string $externalReference,
        string $expirationDate,
        ClientModel $client
    )
    {
        $http = new Client();
        $url = "https://api.mercadopago.com/v1/payments";

        try {

            $response = $http->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$this->mpToken}",
                    'Content-Type' => 'application/json',
                    'X-Idempotency-Key' => (string) $externalReference,
                ],
                'json' => [
                    'transaction_amount' => $this->value,
                    'description' => 'Parcela do seguro',
                    'payment_method_id' => 'bolbradesco',
                    'external_reference' => (string) $externalReference,
                    'date_of_expiration' => $expirationDate,
                    'payer' => [
                        'email' => $client->email,
                        'first_name' => $client->name,
                        'last_name' => $client->surname,
                        'identification' => [
                            'type' => 'CPF',
                            'number' => preg_replace('/\D/', '', $client->cpf),
                        ],
                        'address' => [
                            'zip_code' => preg_replace('/\D/', '', $client->cep),
                            'street_name' => $client->street,
                            'street_number' => $client->number,
                            'neighborhood' => $client->neighborhood,
                            'city' => $client->city,
                            'federal_unit' => $client->state,
                        ],
                    ],
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (RequestException $e) {

            $errorResponse = $e->getResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : [];

            Log::error('Erro ao criar boleto Mercado Pago: ' . $e->getMessage());
            Log::error('Detalhes: ' . json_encode($errorResponse, JSON_PRETTY_PRINT));

            return [
                'error' => 'Erro ao criar boleto',
                'details' => $e->getMessage(),
                'response' => $errorResponse
            ];
        }
    }
}