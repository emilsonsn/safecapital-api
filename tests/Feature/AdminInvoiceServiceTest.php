<?php

namespace Tests\Feature;

use App\Enums\ClientStatusEnum;
use App\Enums\InstallmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentFormEnum;
use App\Models\Client;
use App\Models\ClientInstallment;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Finance\InvoiceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminInvoiceServiceTest extends TestCase
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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('surname');
            $table->string('email');
            $table->string('cpf');
            $table->decimal('policy_value', 12, 2);
            $table->string('status');
            $table->string('payment_form');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('client_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedInteger('installment_number');
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('status');
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
            $table->unsignedBigInteger('paid_by_user_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('payment_notes')->nullable();
            $table->timestamps();
        });
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('client_installment_id');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function test_admin_manual_payment_settles_the_invoice_and_its_installments(): void
    {
        $admin = $this->user('Admin');
        $clientUser = $this->user('Client');
        $client = Client::query()->create([
            'user_id' => $clientUser->id, 'name' => 'Locatário', 'surname' => 'Um',
            'email' => 'locatario@example.com', 'cpf' => '12345678900',
            'policy_value' => 1200, 'status' => ClientStatusEnum::Active,
            'payment_form' => PaymentFormEnum::Invoiced,
        ]);
        $installment = ClientInstallment::query()->create([
            'client_id' => $client->id, 'installment_number' => 1, 'amount' => 100,
            'due_date' => now()->subDay(), 'status' => InstallmentStatusEnum::Overdue,
        ]);
        $invoice = Invoice::query()->create([
            'user_id' => $clientUser->id, 'closing_date' => now()->subDays(10),
            'due_date' => now()->subDay(), 'amount' => 100, 'status' => InvoiceStatusEnum::Overdue,
        ]);
        $invoice->installments()->attach($installment->id, ['amount' => 100]);

        $paid = app(InvoiceService::class)->markAsPaid($clientUser, $invoice, $admin, [
            'payment_reference' => 'REC-100', 'payment_notes' => 'Baixa manual conferida.',
        ]);

        $this->assertSame(InvoiceStatusEnum::Paid, $paid->status);
        $this->assertSame('MANUAL_ADMIN', $paid->payment_method);
        $this->assertSame($admin->id, $paid->paid_by_user_id);
        $this->assertSame('REC-100', $paid->payment_reference);
        $this->assertSame(InstallmentStatusEnum::Paid, $installment->fresh()->status);
        $this->assertSame(100.0, $installment->fresh()->paid_amount);
    }

    private function user(string $role): User
    {
        return User::query()->create([
            'name' => $role, 'surname' => 'User', 'email' => strtolower($role).'@example.com',
            'password' => 'secret', 'role' => $role,
        ]);
    }
}
