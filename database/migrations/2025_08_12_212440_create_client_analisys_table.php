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
        Schema::create('client_analisys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('cpf');
            $table->string('score');
            $table->boolean('has_pendings');
            $table->boolean('has_processes');
            $table->enum('status', ['Approved','Pending','Disapproved']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_analisys');
    }
};
