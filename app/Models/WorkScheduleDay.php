<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class WorkScheduleDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_schedule_id',
        'day_of_week',
        'is_working_day',
        'start_time',
        'end_time',
        'break_minutes',
        'grace_period_minutes',
        'early_departure_grace_minutes',
        'allow_overtime',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_working_day' => 'boolean',
        'break_minutes' => 'integer',
        'grace_period_minutes' => 'integer',
        'early_departure_grace_minutes' => 'integer',
        'allow_overtime' => 'boolean',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(
            WorkSchedule::class,
            'work_schedule_id'
        );
    }

      public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(
            WorkSchedule::class,
            'work_schedule_id'
        );
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_by'
        );
    }
}