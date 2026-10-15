<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
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

            $table->date('start_date');
            $table->date('end_date');

            $table->decimal('days', 8, 2);

            $table->text('reason')->nullable();

            $table->string('attachment_path')->nullable();

            $table->string('status', 30)
                ->default('pending');

            $table->foreignId('submitted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->text('rejection_reason')->nullable();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('cancelled_at')->nullable();

            $table->date('return_date')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['company_id', 'status'],
                'leave_requests_company_status_index'
            );

            $table->index(
                ['employee_id', 'status'],
                'leave_requests_employee_status_index'
            );

            $table->index(
                ['company_id', 'start_date', 'end_date'],
                'leave_requests_dates_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};