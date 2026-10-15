<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balance_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->restrictOnDelete();

            $table->foreignId('leave_balance_id')
                ->constrained('leave_balances')
                ->cascadeOnDelete();

            $table->string('transaction_type', 40);

            $table->decimal('days', 8, 2);

            $table->decimal('balance_before', 8, 2)->default(0);

            $table->decimal('balance_after', 8, 2)->default(0);

            $table->string('source_type', 50)->nullable();

            $table->unsignedBigInteger('source_id')->nullable();

            $table->text('description')->nullable();

            $table->text('reason')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(
                ['company_id', 'employee_id', 'leave_type_id'],
                'leave_bal_tx_company_employee_type_idx'
            );

            $table->index(
                ['leave_balance_id', 'created_at'],
                'leave_bal_tx_balance_date_idx'
            );

            $table->index(
                ['source_type', 'source_id'],
                'leave_bal_tx_source_idx'
            );

            $table->index(
                ['company_id', 'transaction_type'],
                'leave_bal_tx_company_type_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balance_transactions');
    }
};