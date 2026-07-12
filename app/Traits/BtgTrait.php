<?php

namespace App\Traits;

use App\Models\Client as ClientModel;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

trait BtgTrait
{
    private float $value;
    private string $btgApiUrl;
    private string $btgTokenUrl;
    private string $btgClientId;
    private string $btgClientSecret;
    private string $btgRefreshToken;
    private string $btgAccessToken;
    private string $btgAccountId;

    public function prepareBtg(float $value): void
    {
        $this->value = $value;
        $this->btgApiUrl = env('BTG_API_URL');
        $this->btgTokenUrl = env('BTG_TOKEN_URL');
        $this->btgClientId = env('BTG_CLIENT_ID');
        $this->btgClientSecret = env('BTG_CLIENT_SECRET');
        $this->btgRefreshToken = env('BTG_REFRESH_TOKEN');

        $this->authenticateBtgWithRefreshToken();
        $this->getBtgAccountId();        
    }

    private function authenticateBtgWithRefreshToken(): void
    {
        $cachedToken = cache()->get('btg_access_token');

        if (! empty($cachedToken)) {
            $this->btgAccessToken = $cachedToken;
            return;
        }

        $http = new Client();

        try {
            $response = $http->post($this->btgTokenUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'auth' => [
                    $this->btgClientId,
                    $this->btgClientSecret,
                ],
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->btgRefreshToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->btgAccessToken = $data['access_token'] ?? null;

            if (! empty($this->btgAccessToken)) {
                cache()->put(
                    'btg_access_token',
                    $this->btgAccessToken,
                    now()->addHour()
                );
            }

        } catch (RequestException $e) {
            $errorResponse = $e->getResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : [];

            Log::error('Erro ao autenticar BTG com refresh token: ' . $e->getMessage());
            Log::error('Detalhes BTG Auth: ' . json_encode($errorResponse, JSON_PRETTY_PRINT));

            $this->btgAccessToken = null;
        }
    }

    private function getBtgAccountId(): void
    {
        $cachedAccountId = cache()->get('btg_account_id');

        if (! empty($cachedAccountId)) {
            $this->btgAccountId = $cachedAccountId;
            return;
        }

        if (empty($this->btgAccessToken)) {
            $this->authenticateBtgWithRefreshToken();
        }

        if (empty($this->btgAccessToken)) {
            $this->btgAccountId = null;
            return;
        }

        $http = new Client();

        try {
            $response = $http->get("{$this->btgApiUrl}/v1/accounts", [
                'headers' => [
                    'Authorization' => "Bearer {$this->btgAccessToken}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->btgAccountId = $data['accountId'] ?? null;

            if (! empty($this->btgAccountId)) {
                cache()->put(
                    'btg_account_id',
                    $this->btgAccountId,
                    now()->addDay()
                );
            }

        } catch (RequestException $e) {
            $errorResponse = $e->getResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : [];

            Log::error('Erro ao obter accountId BTG: ' . $e->getMessage());
            Log::error('Detalhes BTG Account: ' . json_encode($errorResponse, JSON_PRETTY_PRINT));

            $this->btgAccountId = null;
        }
    }

    public function makeBtgBoletoPayment(
        string $externalReference,
        string $dueDate,
        ClientModel $client
    ): array {
        $accessToken = $this->btgAccessToken ?? null;

        if (! $accessToken) {
            return [
                'error' => 'Erro ao autenticar no BTG',
                'details' => 'Token de acesso não disponível',
            ];
        }

        $http = new Client();

        $url = "{$this->btgApiUrl}/v1/bank-slips";

        try {
            $response = $http->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Idempotency-Key' => (string) $externalReference,
                ],
                'query' => [
                    'accountId' => $this->btgAccountId,
                ],
                'json' => [
                    'amount' => $this->value,
                    'dueDate' => $dueDate,
                    'referenceNumber' => substr((string) $externalReference, 0, 20),
                    'description' => 'Parcela do seguro',
                    'payer' => [
                        'name' => trim($client->name . ' ' . $client->surname),
                        'taxId' => preg_replace('/\D/', '', $client->cpf),
                        'email' => $client->email,
                    ],
                    'installments' => 1,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (RequestException $e) {
            $errorResponse = $e->getResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : [];

            Log::error('Erro ao criar boleto BTG: ' . $e->getMessage());
            Log::error('Detalhes BTG: ' . json_encode($errorResponse, JSON_PRETTY_PRINT));

            return [
                'error' => 'Erro ao criar boleto BTG',
                'details' => $e->getMessage(),
                'response' => $errorResponse,
            ];
        }
    }
}