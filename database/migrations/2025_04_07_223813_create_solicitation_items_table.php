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
        Schema::create('solicitation_items', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->decimal('value');
            $table->date('due_date');
            $table->unsignedBigInteger('solicitation_id');
            $table->timestamps();

            $table->foreign('solicitation_id')
                ->references('id')
                ->on('solicitations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitation_items');
    }
};
