<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Log;
use Throwable;

trait PH3Trait
{
    protected $baseUrl;
    protected $token;

    private function preparePh3()
    {
        $this->baseUrl = "https://api.ph3a.com.br/DataBusca";

        $this->token = Cache::remember('ph3_api_token', now()->addMinutes(10), function () {
            $client = new Client();
            $url = "{$this->baseUrl}/api/Account/Login";
            $username = env('PH3_API_KEY');

            try {
                $response = $client->post($url, [
                    'json' => [
                        'userName' => $username,
                    ],
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                return $data['data']['Token'] ?? null;

            } catch (Throwable $e) {
                return null;
            }
        });
    }

    public function searchClientForCpfOrCnpj($cpfOrCnpj)
    {
        if (!$this->token) {
            return ['error' => 'Falha na autenticação'];
        }

        $cacheKey = "ph3_client_data_{$cpfOrCnpj}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $client = new Client();
        $url = "{$this->baseUrl}/data";

        try {
            $response = $client->post($url, [
                'json' => [
                    'Document' => $cpfOrCnpj
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'token' => $this->token,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $result = $data['Data'] ?? null;

            if (is_array($result)) {
                Cache::put($cacheKey, $result, now()->addMinutes(10));
            }

            return $result;

        } catch (RequestException $e) {
            Log::error($e->getMessage());
            return ['error' => 'Erro ao buscar cliente'];
        }
    }

    public function startSpiderCpf(string $cpf): array
    {
        if (!$this->token) {
            return ['error' => 'Falha na autenticação'];
        }

        $cacheKey = "ph3_spider_request_id_{$cpf}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $client = new Client();
        $url = 'https://api.ph3a.com.br/DataCrawler/Spider';

        try {
            $response = $client->post($url, [
                'json' => [
                    'Id' => '89c1cdcf-9823-41cf-baea-8842db445db4',
                    'Input' => [
                        'CpfCnpj_In' => $cpf,
                        'ApiType_In' => '2'
                    ]
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => '*/*',
                    'token' => $this->token,
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['RequestId'])) {
                Cache::put($cacheKey, $result, now()->addMinutes(2));
            }

            return $result;

        } catch (RequestException $e) {
            Log::error($e->getMessage());
            return ['error' => 'Erro ao iniciar Spider'];
        }
    }

    public function getSpiderResult(string $id): array
    {
        if (!$this->token) {
            return ['error' => 'Falha na autenticação'];
        }

        $client = new Client();
        $url = "https://api.ph3a.com.br/DataCrawler/Spider/{$id}";

        try {
            $response = $client->get($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => '*/*',
                    'token' => $this->token,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (RequestException $e) {
            Log::error($e->getMessage());
            return ['error' => 'Erro ao buscar resultado do Spider'];
        }
    }
}