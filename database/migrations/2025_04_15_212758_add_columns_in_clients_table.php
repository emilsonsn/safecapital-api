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
            $table->enum('property_type', [
                'Residential',
                'Commercial'
                ])
                ->default('Residential')
                ->after('number');

            $table->string('doc4sign_document_uuid')
                ->after('property_type')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('property_type');
            $table->dropColumn('doc4sign_document_uuid');
        });
    }
};
