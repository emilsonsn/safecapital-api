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
            $table->text('observations')->nullable()->after('status');
            $table->string('payment_form')->after('condominium_fee');
            $table->string('complement')->nullable()->after('number');            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('observations');
            $table->dropColumn('payment_form');
            $table->dropColumn('complement');
        });
    }
};
