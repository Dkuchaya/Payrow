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
    Schema::table('payroll_run_item_components', function (Blueprint $table) {
        $table->dropForeign(['payroll_run_item_id']);

        $table->foreign('payroll_run_item_id')
            ->references('id')
            ->on('payroll_run_items')
            ->restrictOnDelete();
    });
}

public function down(): void
{
    Schema::table('payroll_run_item_components', function (Blueprint $table) {
        $table->dropForeign(['payroll_run_item_id']);

        $table->foreign('payroll_run_item_id')
            ->references('id')
            ->on('payroll_run_items')
            ->cascadeOnDelete();
    });
}
};
