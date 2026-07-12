<?php

namespace App\Services\Btg;

use App\Enums\BankIntegrationStatusEnum;
use App\Models\BankIntegration;
use App\Models\BankOAuthState;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class BtgOAuthService
{
    public function authorizationUrl(User $user): string
    {
        foreach (['client_id', 'client_secret', 'authorize_url', 'token_url', 'redirect_uri'] as $key) {
            if (blank(config('services.btg.'.$key))) {
                throw new RuntimeException("Configuração BTG ausente: {$key}.");
            }
        }

        $state = Str::random(64);
        BankOAuthState::where('provider', 'BTG')->where('expires_at', '<', now())->delete();
        BankOAuthState::create([
            'provider' => 'BTG',
            'state_hash' => hash('sha256', $state),
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        return config('services.btg.authorize_url').'?'.http_build_query([
            'client_id' => config('services.btg.client_id'),
            'response_type' => 'code',
            'redirect_uri' => config('services.btg.redirect_uri'),
            'scope' => implode(' ', config('services.btg.scopes')),
            'prompt' => 'login',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function handleCallback(string $code, string $state): BankIntegration
    {
        $oauthState = $this->consumeState($state);

        try {
            $response = Http::asForm()->acceptJson()
                ->withBasicAuth(config('services.btg.client_id'), config('services.btg.client_secret'))
                ->post(config('services.btg.token_url'), [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => config('services.btg.redirect_uri'),
                ])->throw();
        } catch (ConnectionException|RequestException $exception) {
            $status = $exception instanceof RequestException ? 'HTTP '.$exception->response->status() : 'conexão indisponível';
            throw new RuntimeException(
                'Não foi possível trocar o código de autorização no BTG ('.$status.').',
                previous: $exception
            );
        }

        $data = $response->json();
        if (empty($data['access_token']) || empty($data['refresh_token'])) {
            throw new RuntimeException('O BTG não retornou os tokens esperados.');
        }

        return BankIntegration::updateOrCreate([
            'provider' => 'BTG',
            'environment' => config('services.btg.environment'),
        ], [
            'company_id' => config('services.btg.company_id'),
            'account_id' => config('services.btg.account_id'),
            'account_branch' => config('services.btg.account_branch'),
            'account_number' => config('services.btg.account_number'),
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'access_token_expires_at' => now()->addSeconds((int) ($data['expires_in'] ?? 86400)),
            'refresh_token_expires_at' => now()->addDays(10),
            'scopes' => preg_split('/\s+/', trim((string) ($data['scope'] ?? '')), -1, PREG_SPLIT_NO_EMPTY),
            'status' => BankIntegrationStatusEnum::Connected,
            'authorized_by_user_id' => $oauthState->user_id,
            'authorized_at' => now(),
            'last_refreshed_at' => now(),
            'last_error' => null,
            'meta' => ['token_type' => $data['token_type'] ?? 'Bearer'],
        ]);
    }

    public function consumeState(string $state): BankOAuthState
    {
        return DB::transaction(function () use ($state) {
            $record = BankOAuthState::where('state_hash', hash('sha256', $state))
                ->lockForUpdate()->first();
            if (! $record || $record->provider !== 'BTG' || $record->used_at || $record->expires_at->isPast()) {
                throw new RuntimeException('O estado da autorização é inválido ou expirou.');
            }
            $record->update(['used_at' => now()]);

            return $record;
        });
    }

    public function disconnect(): void
    {
        $integration = app(BtgTokenManager::class)->integration();
        $integration?->update([
            'access_token' => null,
            'refresh_token' => null,
            'access_token_expires_at' => null,
            'refresh_token_expires_at' => null,
            'status' => BankIntegrationStatusEnum::Disconnected,
            'last_error' => null,
        ]);
    }
}
