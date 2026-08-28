<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_documents', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('path')->nullable();
            $table->text('external_url')->nullable();
            $table->string('version')->unique();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        DB::table('term_documents')->insert([
            'filename' => 'termos-de-uso.pdf',
            'external_url' => 'https://www.safecapitalgarantias.com.br/_files/ugd/7cc209_5040678c9cd344799f37d37fa9a2e454.pdf',
            'version' => '1.0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('term_documents');
    }
};
