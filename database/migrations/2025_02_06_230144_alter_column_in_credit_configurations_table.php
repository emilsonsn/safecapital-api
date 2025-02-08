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
        Schema::table('credit_configurations', function (Blueprint $table) {
            $table->renameColumn('min_pending_value', 'max_pending_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_configurations', function (Blueprint $table) {
            $table->renameColumn('max_pending_value', 'min_pending_value');
        });
    }
};
