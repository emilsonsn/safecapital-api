<?php

namespace App\Traits;

use App\Models\Client as ClientModel;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Models\User;

trait BtgTrait
{
    private float $value;
    private string $btgApiUrl;
    private string $btgTokenUrl;
    private string $btgClientId;
    private string $btgClientSecret;
    private ?string $btgRefreshToken = null;
    private ?string $btgAccessToken = null;
    private ?string $btgAccountId = null;

    public function prepareBtg(float $value): void
    {
        $this->value = $value;
        $this->btgApiUrl = config('services.btg.api_url');
        $this->btgTokenUrl = config('services.btg.token_url');
        $this->btgClientId = config('services.btg.client_id');
        $this->btgClientSecret = config('services.btg.client_secret');
        $this->btgRefreshToken = config('services.btg.refresh_token');

        $this->authenticateBtgWithRefreshToken();
        $this->getBtgAccountId();        
    }

    public function makeBtgInvoiceBoleto(string $externalReference, string $dueDate, User $user): array
    {
        return $this->sendBtgBoleto($externalReference, $dueDate, trim($user->name.' '.$user->surname),
            preg_replace('/\D/', '', (string) $user->cnpj), $user->email, 'Fatura mensal de garantias');
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
                    now()->addSeconds(max(60, ((int) ($data['expires_in'] ?? 3600)) - 60))
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
        $configuredAccountId = config('services.btg.account_id');
        if (! empty($configuredAccountId)) {
            $this->btgAccountId = $configuredAccountId;
            return;
        }
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
        return $this->sendBtgBoleto($externalReference, $dueDate,
            trim($client->name.' '.$client->surname), preg_replace('/\D/', '', $client->cpf),
            $client->email, 'Parcela do seguro');
    }

    private function sendBtgBoleto(string $externalReference, string $dueDate, string $payerName,
        string $taxId, string $email, string $description): array
    {
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
                    'description' => $description,
                    'payer' => [
                        'name' => $payerName,
                        'taxId' => $taxId,
                        'email' => $email,
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
