<?php

namespace Tests\Feature;

use App\Enums\BankIntegrationStatusEnum;
use App\Models\BankOAuthState;
use App\Models\User;
use App\Services\Btg\BtgOAuthService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BtgOAuthServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        app('db')->purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('surname');
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->timestamps();
        });
        Schema::create('bank_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('environment');
            $table->string('company_id')->nullable();
            $table->string('account_id')->nullable();
            $table->string('account_branch')->nullable();
            $table->string('account_number')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('access_token_expires_at')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('authorized_by_user_id')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('last_refreshed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'environment']);
        });
        Schema::create('bank_oauth_states', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('state_hash')->unique();
            $table->unsignedBigInteger('user_id');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        config()->set('services.btg', [
            'environment' => 'SANDBOX',
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'authorize_url' => 'https://id.sandbox.btgpactual.com/oauth2/authorize',
            'token_url' => 'https://id.sandbox.btgpactual.com/oauth2/token',
            'redirect_uri' => 'https://api.example.com/api/btg/callback',
            'scopes' => ['openid', 'brn:btg:empresas:banking:collections'],
            'company_id' => '12345678000199',
            'account_id' => null,
            'account_branch' => '50',
            'account_number' => '123456789',
        ]);
    }

    public function test_it_generates_single_use_state_and_stores_encrypted_tokens(): void
    {
        $user = User::forceCreate([
            'name' => 'Admin', 'surname' => 'Safe', 'email' => 'admin@example.com',
            'password' => 'secret', 'role' => 'Admin',
        ]);
        $service = app(BtgOAuthService::class);
        $authorizationUrl = $service->authorizationUrl($user);
        parse_str((string) parse_url($authorizationUrl, PHP_URL_QUERY), $query);

        $this->assertSame('client-id', $query['client_id']);
        $this->assertSame('openid brn:btg:empresas:banking:collections', $query['scope']);
        $this->assertSame(hash('sha256', $query['state']), BankOAuthState::first()->state_hash);

        Http::fake([
            config('services.btg.token_url') => Http::response([
                'access_token' => 'access-secret', 'refresh_token' => 'refresh-secret',
                'expires_in' => 86400,
                'scope' => 'openid brn:btg:empresas:banking:collections',
                'token_type' => 'Bearer',
            ]),
        ]);
        $integration = $service->handleCallback('authorization-code', $query['state']);

        $this->assertSame(BankIntegrationStatusEnum::Connected, $integration->status);
        $this->assertSame('refresh-secret', $integration->refresh_token);
        $this->assertNotSame('refresh-secret', $integration->getRawOriginal('refresh_token'));
        $this->assertNotContains('refresh_token', array_keys($integration->toArray()));
        $this->assertNotNull(BankOAuthState::first()->used_at);
    }
}
