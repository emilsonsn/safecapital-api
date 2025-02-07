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
            $table->boolean('has_law_processes')->after('end_score');
            $table->decimal('min_pending_value')
                ->nullable()
                ->after('has_pending_issues');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_configurations', function (Blueprint $table) {
            $table->dropColumn('has_law_processes');
            $table->dropColumn('min_pending_value');
        });
    }
};
