<?php

namespace App\Services\Btg;

use App\Enums\BankIntegrationStatusEnum;
use App\Models\BankIntegration;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BtgTokenManager
{
    public function integration(): ?BankIntegration
    {
        return BankIntegration::query()
            ->where('provider', 'BTG')
            ->where('environment', config('services.btg.environment'))
            ->first();
    }

    public function accessToken(bool $forceRefresh = false): string
    {
        $integration = $this->integration();
        if (! $integration || ! $integration->refresh_token) {
            throw new RuntimeException('A integração BTG não está conectada.');
        }

        if (! $forceRefresh && $integration->access_token &&
            $integration->access_token_expires_at?->isAfter(now()->addMinutes(5))) {
            return $integration->access_token;
        }

        return $this->refresh($integration->id);
    }

    public function refresh(?int $integrationId = null): string
    {
        try {
            return DB::transaction(function () use ($integrationId) {
                $query = BankIntegration::query()->lockForUpdate();
                $integration = $integrationId
                    ? $query->find($integrationId)
                    : $query->where('provider', 'BTG')
                        ->where('environment', config('services.btg.environment'))->first();

                if (! $integration || ! $integration->refresh_token) {
                    throw new RuntimeException('A integração BTG não possui refresh token.');
                }

                if ($integration->refresh_token_expires_at?->isPast()) {
                    throw new RuntimeException('A autorização BTG expirou. Reconecte a conta.');
                }

                try {
                    $response = Http::asForm()
                        ->acceptJson()
                        ->withBasicAuth(config('services.btg.client_id'), config('services.btg.client_secret'))
                        ->post(config('services.btg.token_url'), [
                            'grant_type' => 'refresh_token',
                            'refresh_token' => $integration->refresh_token,
                        ])->throw();

                    $data = $response->json();
                    if (empty($data['access_token'])) {
                        throw new RuntimeException('O BTG não retornou um access token.');
                    }

                    $integration->update([
                        'access_token' => $data['access_token'],
                        'refresh_token' => $data['refresh_token'] ?? $integration->refresh_token,
                        'access_token_expires_at' => now()->addSeconds((int) ($data['expires_in'] ?? 86400)),
                        'refresh_token_expires_at' => now()->addDays(10),
                        'last_refreshed_at' => now(),
                        'status' => BankIntegrationStatusEnum::Connected,
                        'last_error' => null,
                    ]);

                    return $data['access_token'];
                } catch (ConnectionException|RequestException $exception) {
                    $status = $exception instanceof RequestException ? 'HTTP '.$exception->response->status() : 'conexão indisponível';
                    $message = 'Falha ao renovar a autorização no BTG ('.$status.').';
                    throw new RuntimeException($message, previous: $exception);
                }
            }, 3);
        } catch (RuntimeException $exception) {
            $integration = $integrationId
                ? BankIntegration::find($integrationId)
                : $this->integration();
            $integration?->update([
                'status' => str_contains($exception->getMessage(), 'expirou')
                    ? BankIntegrationStatusEnum::Expired
                    : BankIntegrationStatusEnum::Error,
                'last_error' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }
}
