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
        Schema::create('correspondings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('cpf');
            $table->string('fullname');
            $table->date('birthday');
            $table->decimal('declared_income');
            $table->string('occupation');
            $table->string('email');
            $table->string('phone');
            $table->timestamps();

            $table->foreign('client_id')
                ->references('id')
                ->on('users');
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correspondings');
    }
};
