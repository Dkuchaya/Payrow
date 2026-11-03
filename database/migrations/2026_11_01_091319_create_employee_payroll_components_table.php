<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_payroll_components', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->foreignId('payroll_component_id')
                ->constrained('payroll_components')
                ->cascadeOnDelete();

            $table->decimal('amount', 15, 2)->default(0);

            $table->decimal('percentage', 8, 2)->nullable();

            $table->date('effective_from');

            $table->date('effective_to')->nullable();

            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['employee_id', 'is_active']);
            $table->index(['payroll_component_id', 'is_active']);

            $table->unique(
                ['employee_id', 'payroll_component_id', 'effective_from'],
                'employee_component_effective_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payroll_components');
    }
}; 