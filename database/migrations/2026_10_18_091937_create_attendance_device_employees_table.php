<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_device_employees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('attendance_device_id')
                ->constrained('attendance_devices')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            // ID assigned to the employee by the biometric/device system.
            $table->string('device_user_id', 150);

            // Optional username/name stored on the device.
            $table->string('device_username', 150)->nullable();

            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['company_id', 'employee_id'],
                'attendance_device_employees_company_employee_idx'
            );

            $table->index(
                ['attendance_device_id', 'is_active'],
                'attendance_device_employees_device_active_idx'
            );

            $table->unique(
                ['attendance_device_id', 'device_user_id'],
                'attendance_device_employees_device_user_unique'
            );

            $table->unique(
                ['attendance_device_id', 'employee_id'],
                'attendance_device_employees_device_employee_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_device_employees');
    }
};