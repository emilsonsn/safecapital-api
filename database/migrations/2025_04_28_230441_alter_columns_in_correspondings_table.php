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
        Schema::table('correspondings', function (Blueprint $table) {
            $table->decimal('declared_income')->nullable()->change();
            $table->string('occupation')->nullable()->change();
        });  
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('correspondings', function (Blueprint $table) {
            $table->decimal('declared_income')->change();
            $table->string('occupation')->change();
        });
    }
};
