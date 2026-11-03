<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_run_item_components', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_run_item_id')
                ->constrained('payroll_run_items')
                ->cascadeOnDelete();

            $table->foreignId('payroll_component_id')
                ->nullable()
                ->constrained('payroll_components')
                ->nullOnDelete();

            // Snapshot of the component at the time payroll was calculated
            $table->string('name');
            $table->enum('type', [
                'allowance',
                'deduction',
            ]);

            $table->enum('calculation_type', [
                'fixed',
                'percentage',
            ]);

            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('percentage', 8, 2)->nullable();
            $table->decimal('calculated_amount', 15, 2)->default(0);

            $table->boolean('is_taxable')->default(false);

            $table->timestamps();

            $table->index([
                'payroll_run_item_id',
                'type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_item_components');
    }
};