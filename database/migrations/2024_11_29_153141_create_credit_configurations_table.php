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
        Schema::create('credit_configurations', function (Blueprint $table) {
            $table->id();
            $table->integer('start_approved_score');
            $table->integer('end_approved_score');
            $table->integer('start_pending_score');
            $table->integer('end_pending_score');
            $table->integer('start_disapproved_score');
            $table->integer('end_disapproved_score');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_configurations');
    }
};
