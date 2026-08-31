<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_installments', function (Blueprint $table) {
            $table->string('provider_correlation_id')->nullable()->after('mercado_pago_id');
            $table->string('digitable_line')->nullable()->after('provider_correlation_id');
            $table->json('meta')->nullable()->after('digitable_line');
            $table->renameColumn('mercado_pago_id', 'provider_external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_installments', function (Blueprint $table) {
            $table->renameColumn('provider_external_id', 'mercado_pago_id');
            $table->dropColumn('provider_correlation_id');
            $table->dropColumn('digitable_line');
            $table->dropColumn('meta');
        });
    }
};
