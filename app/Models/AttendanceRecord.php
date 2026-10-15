<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'attendance_date',
        'status',
        'clock_in_at',
        'clock_out_at',
        'worked_hours',
        'scheduled_hours',
        'overtime_hours',
        'late_minutes',
        'early_departure_minutes',
        'break_minutes',
        'source',
        'attendance_device_id',
        'is_manual_adjustment',
        'adjusted_by',
        'adjusted_at',
        'adjustment_reason',
        'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',

        'worked_hours' => 'decimal:2',
        'scheduled_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',

        'late_minutes' => 'integer',
        'early_departure_minutes' => 'integer',
        'break_minutes' => 'integer',

        'is_manual_adjustment' => 'boolean',

        'adjusted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceDevice::class,
            'attendance_device_id'
        );
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'adjusted_by'
        );
    }

    public function punches(): HasMany
    {
        return $this->hasMany(
            AttendancePunch::class,
            'employee_id',
            'employee_id'
        );
    }
}