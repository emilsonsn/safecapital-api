<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_installments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('installment_number');

            $table->decimal('amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->nullable();
            $table->decimal('fine', 10, 2)->nullable();
            $table->decimal('interest', 10, 2)->nullable();

            $table->string('mercado_pago_id')->nullable();
            $table->string('boleto_url')->nullable();
            $table->string('boleto_barcode')->nullable();
            $table->string('boleto_pdf_path')->nullable();

            $table->string('boleto_uploaded_path')->nullable();

            $table->date('due_date');
            $table->timestamp('boleto_sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->string('status');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_installments');
    }
};