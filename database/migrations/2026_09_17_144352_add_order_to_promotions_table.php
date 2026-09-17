<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->unsignedInteger('order')->default(0)->after('active');
        });

        DB::table('promotions')->orderBy('created_at')->orderBy('id')->get(['id'])
            ->each(function ($promotion, $index) {
                DB::table('promotions')->where('id', $promotion->id)->update(['order' => $index + 1]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
