<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('paid_by_user_id')->nullable()->after('paid_at')
                ->constrained('users')->nullOnDelete();
            $table->string('payment_method', 30)->nullable()->after('paid_by_user_id');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->text('payment_notes')->nullable()->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paid_by_user_id');
            $table->dropColumn(['payment_method', 'payment_reference', 'payment_notes']);
        });
    }
};
