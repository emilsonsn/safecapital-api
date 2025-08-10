<?php

namespace App\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

trait ApiaryTrait
{
    private int $scoreProductId = 351;

    private int $proccessProductId = 264;

    protected ?string $apiarybaseUrl = null;

    protected ?string $apiaryApiKey = null;

    protected ?string $apiaryApiSecret = null;

    private function getClient(string $metodo, int $produtoId): Client
    {
        $this->apiarybaseUrl = rtrim(env('APIARY_BASE_URL', 'http://servidor001.info'), '/');
        $this->apiaryApiKey = env('APIARY_KEY', '');
        $this->apiaryApiSecret = env('APIARY_PASSWORD', '');

        return new Client([
            'base_uri' => $this->apiarybaseUrl,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function consultarCpf(string $cpf): array
    {
        $metodo = 'restricoes-essencial';
        $produtoId = $this->scoreProductId;
        $client = $this->getClient($metodo, $produtoId);

        try {
            $response = $client->get('/api/admins', [
                'query' => [
                    'ws' => 'sim',
                    'chave' => $this->apiaryApiKey,
                    'senha' => $this->apiaryApiSecret,
                    'metodo' => $metodo,
                    'produtoID' => $produtoId,
                    'cpf' => $cpf,
                ],
            ]);

            return json_decode((string) $response->getBody(), true) ?? [];

        } catch (RequestException $e) {
            Log::error($e->getMessage());

            return ['error' => 'Erro ao consultar CPF'];
        }
    }

    public function consultarProcessosPorCpf(string $cpf): array
    {
        $metodo = 'processos';
        $produtoId = $this->proccessProductId;
        $client = $this->getClient($metodo, $produtoId);

        try {
            $response = $client->get('/api/admins', [
                'query' => [
                    'ws' => 'sim',
                    'chave' => $this->apiaryApiKey,
                    'senha' => $this->apiaryApiSecret,
                    'metodo' => $metodo,
                    'produtoID' => $produtoId,
                    'cpf' => $cpf,
                ],
            ]);

            return json_decode((string) $response->getBody(), true) ?? [];

        } catch (RequestException $e) {
            Log::error($e->getMessage());

            return ['error' => 'Erro ao consultar processos do CPF'];
        }
    }
}
