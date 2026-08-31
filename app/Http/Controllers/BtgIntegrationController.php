<?php

namespace App\Http\Controllers;

use App\Services\Btg\BtgOAuthService;
use App\Services\Btg\BtgTokenManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BtgIntegrationController extends Controller
{
    public function show(BtgTokenManager $tokens)
    {
        $integration = $tokens->integration();

        return response()->json([
            'status' => true,
            'data' => $integration ? [
                'provider' => $integration->provider,
                'environment' => $integration->environment,
                'connection_status' => $integration->status->value,
                'company_id' => $integration->company_id,
                'account_id' => $integration->account_id,
                'account_branch' => $integration->account_branch,
                'account_number' => $integration->account_number,
                'scopes' => $integration->scopes,
                'authorized_at' => $integration->authorized_at,
                'access_token_expires_at' => $integration->access_token_expires_at,
                'refresh_token_expires_at' => $integration->refresh_token_expires_at,
                'last_refreshed_at' => $integration->last_refreshed_at,
                'last_error' => $integration->last_error,
            ] : null,
        ]);
    }

    public function connect(Request $request, BtgOAuthService $oauth)
    {
        return response()->json([
            'status' => true,
            'data' => ['authorization_url' => $oauth->authorizationUrl($request->user())],
        ]);
    }

    public function callback(Request $request, BtgOAuthService $oauth)
    {
        $frontendUrl = config('services.btg.frontend_callback_url');

        try {
            if ($request->filled('error')) {
                $state = $request->query('state');
                if (! is_string($state) || strlen($state) !== 64) {
                    throw new RuntimeException('O estado da autorização é inválido.');
                }
                $oauth->consumeState($state);
                throw new RuntimeException('A autorização foi cancelada ou recusada no BTG.');
            }

            $validated = $request->validate([
                'code' => ['required', 'string'],
                'state' => ['required', 'string', 'size:64'],
            ]);
            $oauth->handleCallback($validated['code'], $validated['state']);

            return redirect()->away($this->resultUrl($frontendUrl, 'success'));
        } catch (\Throwable $exception) {
            Log::warning('Falha no callback OAuth do BTG.', ['message' => $exception->getMessage()]);

            return redirect()->away($this->resultUrl($frontendUrl, 'error', $exception->getMessage()));
        }
    }

    public function refresh(BtgTokenManager $tokens)
    {
        $tokens->accessToken(true);

        return response()->json(['status' => true, 'message' => 'Token BTG renovado com sucesso.']);
    }

    public function disconnect(BtgOAuthService $oauth)
    {
        $oauth->disconnect();

        return response()->json(['status' => true, 'message' => 'Integração BTG desconectada.']);
    }

    private function resultUrl(string $baseUrl, string $status, ?string $message = null): string
    {
        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl.$separator.http_build_query(array_filter([
            'btg_status' => $status,
            'btg_message' => $message,
        ]), '', '&', PHP_QUERY_RFC3986);
    }
}
