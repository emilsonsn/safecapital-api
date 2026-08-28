<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('environment', 20);
            $table->string('company_id')->nullable();
            $table->string('account_id')->nullable();
            $table->string('account_branch')->nullable();
            $table->string('account_number')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('access_token_expires_at')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->string('status', 30);
            $table->foreignId('authorized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('last_refreshed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'environment']);
        });

        Schema::create('bank_oauth_states', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('state_hash', 64)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->index(['provider', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_oauth_states');
        Schema::dropIfExists('bank_integrations');
    }
};
