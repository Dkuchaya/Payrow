<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePunch extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'attendance_device_id',
        'punch_date',
        'punched_at',
        'punch_type',
        'source',
        'external_id',
        'device_user_id',
        'raw_data',
        'is_processed',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'punch_date' => 'date',
        'punched_at' => 'datetime',
        'raw_data' => 'array',
        'is_processed' => 'boolean',
        'processed_at' => 'datetime',
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
}