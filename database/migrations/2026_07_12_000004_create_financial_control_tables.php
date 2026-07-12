<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tax_id')->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('status', 20);
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('paid_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'paid_at']);
            $table->index('due_date');
        });

        Schema::create('recoverable_values', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable()->unique();
            $table->string('case_number')->nullable()->index();
            $table->string('counterparty')->nullable();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->date('expected_recovery_date')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('status', 20);
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'expected_recovery_date']);
        });

        Schema::create('monthly_financial_reports', function (Blueprint $table) {
            $table->id();
            $table->date('reference_month')->unique();
            $table->decimal('invoice_income', 12, 2);
            $table->decimal('recoveries_income', 12, 2);
            $table->decimal('total_income', 12, 2);
            $table->decimal('total_expenses', 12, 2);
            $table->decimal('net_balance', 12, 2);
            $table->decimal('recoverable_balance', 12, 2);
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_financial_reports');
        Schema::dropIfExists('recoverable_values');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('suppliers');
    }
};
