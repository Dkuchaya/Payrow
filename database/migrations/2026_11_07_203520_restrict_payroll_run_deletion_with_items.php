<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->dropForeign(['payroll_run_id']);

            $table->foreign('payroll_run_id')
                ->references('id')
                ->on('payroll_runs')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->dropForeign(['payroll_run_id']);

            $table->foreign('payroll_run_id')
                ->references('id')
                ->on('payroll_runs')
                ->cascadeOnDelete();
        });
    }
};