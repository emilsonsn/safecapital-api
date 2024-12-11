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
        Schema::table('clients', function (Blueprint $table) {
            $table->decimal('rental_value')->default(0)->after('number');
            $table->decimal('property_tax')->default(0)->after('rental_value');
            $table->decimal('condominium_fee')->default(0)->after('property_tax');
            $table->decimal('policy_value')->default(0)->after('condominium_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('rental_value');
            $table->dropColumn('property_tax');
            $table->dropColumn('condominium_fee');
            $table->dropColumn('policy_value');
        });
    }
};
