<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->restrictOnDelete();

            $table->foreignId('leave_policy_id')
                ->nullable()
                ->constrained('leave_policies')
                ->nullOnDelete();

            $table->unsignedSmallInteger('year');

            $table->decimal('entitled_days', 8, 2)
                ->default(0);

            $table->decimal('accrued_days', 8, 2)
                ->default(0);

            $table->decimal('carried_forward_days', 8, 2)
                ->default(0);

            $table->decimal('used_days', 8, 2)
                ->default(0);

            $table->decimal('adjusted_days', 8, 2)
                ->default(0);

            $table->decimal('pending_days', 8, 2)
                ->default(0);

            $table->text('adjustment_reason')->nullable();

            $table->timestamps();

            $table->unique(
                ['company_id', 'employee_id', 'leave_type_id', 'year'],
                'leave_balances_employee_type_year_unique'
            );

            $table->index(
                ['company_id', 'year'],
                'leave_balances_company_year_index'
            );

            $table->index(
                ['employee_id', 'year'],
                'leave_balances_employee_year_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};