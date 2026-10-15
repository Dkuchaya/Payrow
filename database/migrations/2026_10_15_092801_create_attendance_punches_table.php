<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_punches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->foreignId('attendance_device_id')
                ->nullable()
                ->constrained('attendance_devices')
                ->nullOnDelete();

            $table->date('punch_date');

            $table->dateTime('punched_at');

            $table->string('punch_type', 30)
                ->default('unknown');

            $table->string('source', 30)
                ->default('device');

            $table->string('external_id', 150)->nullable();

            $table->string('device_user_id', 150)->nullable();

            $table->json('raw_data')->nullable();

            $table->boolean('is_processed')->default(false);

            $table->timestamp('processed_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['company_id', 'employee_id', 'punch_date'],
                'attendance_punches_company_employee_date_idx'
            );

            $table->index(
                ['attendance_device_id', 'punched_at'],
                'attendance_punches_device_datetime_idx'
            );

            $table->index(
                ['company_id', 'punched_at'],
                'attendance_punches_company_datetime_idx'
            );

            $table->index(
                ['company_id', 'is_processed'],
                'attendance_punches_processing_idx'
            );

            $table->unique(
                ['attendance_device_id', 'external_id'],
                'attendance_punches_device_external_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_punches');
    }
};