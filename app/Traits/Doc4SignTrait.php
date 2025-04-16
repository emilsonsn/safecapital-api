<?php

namespace App\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

trait Doc4SignTrait
{
    private string $doc4signToken;
    private string $doc4signCriptykey;
    private string $doc4signSafeUuid;

    public function prepareDoc4Sign()
    {
        $this->doc4signToken = env('DOC4SIGN_TOKEN');
        $this->doc4signCriptykey = env('DOC4SIGN_CRYPTKEY');
        $this->doc4signSafeUuid = env('DOC4SIGN_SAFE_UUID');
    }

    public function uploadDocument(string $filePath, string $fileName)
    {
        $client = new Client();
        try {
            $response = $client->request(
                method: 'POST',
                uri: "https://secure.d4sign.com.br/api/v1/documents/{$this->doc4signSafeUuid}/upload",
                options: [
                    'headers' => [
                        'tokenAPI' => $this->doc4signToken,
                        'cryptKey' => $this->doc4signCriptykey,
                    ],
                    'multipart' => [
                        [
                            'name' => 'file',
                            'contents' => fopen($filePath, 'r'),
                            'filename' => $fileName,
                        ]
                    ]
                ]
            );
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Erro ao enviar documento para D4Sign', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function createSigner(string $documentUuid, array $signers)
    {
        $client = new Client();
        try {
            $response = $client->request('POST', "https://secure.d4sign.com.br/api/v1/documents/{$documentUuid}/createlist", [
                'headers' => [
                    'tokenAPI' => $this->doc4signToken,
                    'cryptKey' => $this->doc4signCriptykey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'signers' => $signers
                ]
            ]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Erro ao criar signatário na D4Sign', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function sendToSign(string $documentUuid)
    {
        $client = new Client();
        try {
            $response = $client->request('POST', "https://secure.d4sign.com.br/api/v1/documents/{$documentUuid}/sendtosigner", [
                'headers' => [
                    'tokenAPI' => $this->doc4signToken,
                    'cryptKey' => $this->doc4signCriptykey,
                ]
            ]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Erro ao enviar para assinatura na D4Sign', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function registerWebhook(string $documentUuid, string $url)
    {
        $client = new Client();
        try {
            $response = $client->request('POST', "https://secure.d4sign.com.br/api/v1/documents/{$documentUuid}/webhooks", [
                'headers' => [
                    'tokenAPI' => $this->doc4signToken,
                    'cryptKey' => $this->doc4signCriptykey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'url' => $url
                ]
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Erro ao registrar webhook na D4Sign', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

}