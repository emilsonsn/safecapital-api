<?php

namespace Tests\Feature;

use App\Enums\ExpenseStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\RecoverableStatusEnum;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Finance\CashflowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashflowServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        app('db')->purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('surname');
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('closing_date');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->string('status');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('category');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('status');
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->unsignedBigInteger('paid_by_user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('recoverable_values', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->string('case_number')->nullable();
            $table->string('counterparty')->nullable();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->date('expected_recovery_date')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->unsignedBigInteger('resolved_by_user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
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

    public function test_monthly_report_combines_income_expenses_and_recoverable_balance(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin', 'surname' => 'Financeiro', 'email' => 'admin@example.com',
            'password' => 'secret', 'role' => 'Admin',
        ]);
        $month = now()->startOfMonth();
        Invoice::query()->create([
            'user_id' => $admin->id,
            'closing_date' => $month,
            'due_date' => $month->copy()->addDays(10),
            'amount' => 1000,
            'status' => InvoiceStatusEnum::Paid,
            'paid_at' => $month->copy()->addDays(2),
        ]);

        $service = app(CashflowService::class);
        $expense = $service->createExpense([
            'category' => 'Operacional', 'description' => 'Fornecedor de software', 'amount' => 300,
        ], $admin);
        $service->markExpenseAsPaid($expense, $admin, ['paid_at' => $month->copy()->addDays(3)->toDateTimeString()]);

        $pending = $service->createRecoverable([
            'description' => 'Processo judicial pendente', 'amount' => 400,
        ], $admin);
        $received = $service->createRecoverable([
            'description' => 'Processo judicial recebido', 'amount' => 200,
        ], $admin);
        $service->markRecoverableAsReceived($received, $admin, [
            'resolved_at' => $month->copy()->addDays(5)->toDateTimeString(),
        ]);

        $report = $service->generateMonthlyReport($month);

        $this->assertSame(1000.0, $report->invoice_income);
        $this->assertSame(200.0, $report->recoveries_income);
        $this->assertSame(1200.0, $report->total_income);
        $this->assertSame(300.0, $report->total_expenses);
        $this->assertSame(900.0, $report->net_balance);
        $this->assertSame(400.0, $report->recoverable_balance);
        $this->assertSame(ExpenseStatusEnum::Paid, $expense->fresh()->status);
        $this->assertSame(RecoverableStatusEnum::Pending, $pending->fresh()->status);
        $this->assertSame(RecoverableStatusEnum::Recovered, $received->fresh()->status);
    }
}
