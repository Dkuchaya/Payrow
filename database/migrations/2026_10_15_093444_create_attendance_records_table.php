<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->date('attendance_date');

            $table->string('status', 30)
                ->default('present');

            $table->dateTime('clock_in_at')->nullable();
            $table->dateTime('clock_out_at')->nullable();

            $table->decimal('worked_hours', 8, 2)
                ->default(0);

            $table->decimal('scheduled_hours', 8, 2)
                ->default(0);

            $table->decimal('overtime_hours', 8, 2)
                ->default(0);

            $table->unsignedInteger('late_minutes')
                ->default(0);

            $table->unsignedInteger('early_departure_minutes')
                ->default(0);

            $table->unsignedInteger('break_minutes')
                ->default(0);

            $table->string('source', 30)
                ->default('manual');

            $table->foreignId('attendance_device_id')
                ->nullable()
                ->constrained('attendance_devices')
                ->nullOnDelete();

            $table->boolean('is_manual_adjustment')
                ->default(false);

            $table->foreignId('adjusted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('adjusted_at')->nullable();

            $table->text('adjustment_reason')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['company_id', 'employee_id', 'attendance_date'],
                'attendance_records_company_employee_date_unique'
            );

            $table->index(
                ['company_id', 'attendance_date'],
                'attendance_records_company_date_idx'
            );

            $table->index(
                ['company_id', 'status'],
                'attendance_records_company_status_idx'
            );

            $table->index(
                ['employee_id', 'attendance_date'],
                'attendance_records_employee_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};