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
        Schema::create('solicitations', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number');
            $table->text('subject');
            $table->enum('status', ['Open', 'Closed'])->default('Open');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('solicitation_messages', function (Blueprint $table) {
            $table->id();
            $table->text('message')->nullable();
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('solicitation_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('solicitation_id')->references('id')->on('solicitations');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitation_messages');
        Schema::dropIfExists('solicitations');
    }
};
