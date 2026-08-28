<?php

namespace App\Console\Commands;

use App\Services\Btg\BtgTokenManager;
use Illuminate\Console\Command;
use Throwable;

class RefreshBtgToken extends Command
{
    protected $signature = 'btg:refresh-token {--force}';

    protected $description = 'Mantém ativa a autorização OAuth da integração BTG';

    public function handle(BtgTokenManager $tokens): int
    {
        try {
            $integration = $tokens->integration();
            if (! $integration || ! $integration->refresh_token) {
                $this->warn('Integração BTG ainda não conectada.');

                return self::SUCCESS;
            }

            $mustRefresh = $this->option('force')
                || ! $integration->access_token_expires_at
                || $integration->access_token_expires_at->isBefore(now()->addHours(6))
                || $integration->last_refreshed_at?->isBefore(now()->subDays(5));

            if ($mustRefresh) {
                $tokens->accessToken(true);
                $this->info('Token BTG renovado.');
            } else {
                $this->info('Token BTG ainda está válido.');
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
