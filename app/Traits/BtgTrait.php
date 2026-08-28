<?php

namespace App\Traits;

use App\Models\Client as ClientModel;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Services\Btg\BtgTokenManager;

trait BtgTrait
{
    private float $value;
    private string $btgApiUrl;
    private ?string $btgAccessToken = null;
    private ?string $btgAccountId = null;

    public function prepareBtg(float $value): void
    {
        $this->value = $value;
        $this->btgApiUrl = config('services.btg.api_url');
        $tokens = app(BtgTokenManager::class);
        $this->btgAccessToken = $tokens->accessToken();
        $this->btgAccountId = $tokens->integration()?->account_id ?: config('services.btg.account_id');
    }

    public function makeBtgInvoiceBoleto(string $externalReference, string $dueDate, User $user): array
    {
        return $this->sendBtgBoleto($externalReference, $dueDate, trim($user->name.' '.$user->surname),
            preg_replace('/\D/', '', (string) $user->cnpj), $user->email, 'Fatura mensal de garantias');
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
