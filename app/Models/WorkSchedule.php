<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'default_start_time',
        'default_end_time',
        'default_break_minutes',
        'grace_period_minutes',
        'early_departure_grace_minutes',
        'overtime_after_minutes',
        'allow_overtime',
        'is_active',
    ];

    protected $casts = [
        'default_break_minutes' => 'integer',
        'grace_period_minutes' => 'integer',
        'early_departure_grace_minutes' => 'integer',
        'overtime_after_minutes' => 'integer',
        'allow_overtime' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(WorkScheduleDay::class);
    }

    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeWorkSchedule::class);
    }
}