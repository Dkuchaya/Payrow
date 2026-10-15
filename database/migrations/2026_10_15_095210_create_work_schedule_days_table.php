<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedule_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('work_schedule_id')
                ->constrained('work_schedules')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | ISO weekday
            |--------------------------------------------------------------------------
            | 1 = Monday
            | 2 = Tuesday
            | ...
            | 7 = Sunday
            */

            $table->unsignedTinyInteger('day_of_week');

            $table->boolean('is_working_day')
                ->default(true);

            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->unsignedInteger('break_minutes')
                ->default(0);

            $table->unsignedInteger('grace_period_minutes')
                ->nullable();

            $table->unsignedInteger('early_departure_grace_minutes')
                ->nullable();

            $table->boolean('allow_overtime')
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['work_schedule_id', 'day_of_week'],
                'work_schedule_days_schedule_day_unique'
            );

            $table->index(
                ['work_schedule_id', 'is_working_day'],
                'work_schedule_days_schedule_working_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedule_days');
    }
};