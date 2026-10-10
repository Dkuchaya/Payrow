<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_final_settlements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('employee_separation_id')
                ->constrained('employee_separations')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Settlement Period
            |--------------------------------------------------------------------------
            */

            $table->date('last_working_date');

            $table->date('settlement_date')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Earnings
            |--------------------------------------------------------------------------
            */

            $table->decimal('basic_salary', 15, 2)->default(0);

            $table->decimal('salary_due', 15, 2)->default(0);

            $table->decimal('leave_pay', 15, 2)->default(0);

            $table->decimal('allowances', 15, 2)->default(0);

            $table->decimal('overtime', 15, 2)->default(0);

            $table->decimal('bonus', 15, 2)->default(0);

            $table->decimal('commission', 15, 2)->default(0);

            $table->decimal('notice_pay', 15, 2)->default(0);

            $table->decimal('gratuity', 15, 2)->default(0);

            $table->decimal('severance_pay', 15, 2)->default(0);

            $table->decimal('other_earnings', 15, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | Deductions
            |--------------------------------------------------------------------------
            */

            $table->decimal('loan_deduction', 15, 2)->default(0);

            $table->decimal('advance_deduction', 15, 2)->default(0);

            $table->decimal('notice_deduction', 15, 2)->default(0);

            $table->decimal('statutory_deductions', 15, 2)->default(0);

            $table->decimal('other_deductions', 15, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | Totals
            |--------------------------------------------------------------------------
            */

            $table->decimal('gross_amount', 15, 2)->default(0);

            $table->decimal('total_deductions', 15, 2)->default(0);

            $table->decimal('net_amount', 15, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | Workflow
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [
                'draft',
                'review',
                'approved',
                'paid',
                'cancelled',
            ])->default('draft');

            $table->foreignId('prepared_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->foreignId('paid_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('paid_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            $table->string('payment_reference')->nullable();

            $table->string('payment_method')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['company_id', 'employee_id'],
                'efs_company_employee_idx'
            );

            $table->index(
                ['company_id', 'status'],
                'efs_company_status_idx'
            );

            $table->index(
                ['company_id', 'employee_separation_id'],
                'efs_company_separation_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_final_settlements');
    }
};