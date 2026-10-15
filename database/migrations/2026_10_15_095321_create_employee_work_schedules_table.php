<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_work_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->foreignId('work_schedule_id')
                ->constrained('work_schedules')
                ->restrictOnDelete();

            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->boolean('is_primary')
                ->default(true);

            $table->text('notes')->nullable();

            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(
                ['company_id', 'employee_id'],
                'employee_work_schedules_company_employee_idx'
            );

            $table->index(
                ['employee_id', 'effective_from', 'effective_to'],
                'employee_work_schedules_employee_dates_idx'
            );

            $table->index(
                ['work_schedule_id', 'effective_from'],
                'employee_work_schedules_schedule_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_work_schedules');
    }
};