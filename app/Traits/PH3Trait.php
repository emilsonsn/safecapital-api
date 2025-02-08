<?php

namespace App\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

trait PH3Trait
{
    protected $baseUrl;
    protected $token;

    private function preparePh3()
    {
        $this->baseUrl = "https://api.ph3a.com.br/DataBusca";
        
        $client = new Client();
        $url = "{$this->baseUrl}/api/Account/Login";

        try {
            $response = $client->post($url, [
                'json' => [
                    'userName' => env('PH3_API_KEY'),
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $this->token = $data['data']['Token'] ?? null;

        } catch (RequestException $e) {
            return null;
        }
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