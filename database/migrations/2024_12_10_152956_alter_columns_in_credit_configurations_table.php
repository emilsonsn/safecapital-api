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
        Schema::table('credit_configurations', function (Blueprint $table) {
            $table->dropColumn('start_approved_score');
            $table->dropColumn('end_approved_score');
            $table->dropColumn('start_pending_score');
            $table->dropColumn('end_pending_score');
            $table->dropColumn('start_disapproved_score');
            $table->dropColumn('end_disapproved_score');

            $table->string('description')->after('id');
            $table->integer('start_score')->after('description');
            $table->integer('end_score')->after('start_score');
            $table->boolean('has_pending_issues')->after('end_score');
            $table->enum('status', ['Pending', 'Approved', 'Disapproved'])
                ->after('has_pending_issues');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_configurations', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('start_score');
            $table->dropColumn('end_score');
            $table->dropColumn('has_pending_issues');
            $table->dropColumn('status');

            $table->integer('start_approved_score')->after('id');
            $table->integer('end_approved_score')->after('start_approved_score');
            $table->integer('start_pending_score')->after('end_approved_score');
            $table->integer('end_pending_score')->after('start_pending_score');
            $table->integer('start_disapproved_score')->after('end_pending_score');
            $table->integer('end_disapproved_score')->after('start_disapproved_score');
        });
    }
};