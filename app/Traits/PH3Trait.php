<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Throwable;

trait PH3Trait
{
    protected $baseUrl;
    protected $token;

    private function preparePh3()
    {
        $this->baseUrl = "https://api.ph3a.com.br/DataBusca";

        $this->token = Cache::remember('ph3_api_token', now()->addMinutes(30), function () {
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
            return $data['Data'] ?? null;

        } catch (RequestException $e) {
            return ['error' => 'Erro ao buscar cliente'];
        }
    }
}