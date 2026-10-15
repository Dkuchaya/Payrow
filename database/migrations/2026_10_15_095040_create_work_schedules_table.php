<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('code', 50);

            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Default Schedule
            |--------------------------------------------------------------------------
            */

            $table->time('default_start_time');
            $table->time('default_end_time');

            $table->unsignedInteger('default_break_minutes')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Attendance Rules
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('grace_period_minutes')
                ->default(0);

            $table->unsignedInteger('early_departure_grace_minutes')
                ->default(0);

            $table->unsignedInteger('overtime_after_minutes')
                ->default(0);

            $table->boolean('allow_overtime')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->unique(
                ['company_id', 'code'],
                'work_schedules_company_code_unique'
            );

            $table->index(
                ['company_id', 'is_active'],
                'work_schedules_company_active_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};