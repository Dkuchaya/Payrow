<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'device_type',
        'manufacturer',
        'model',
        'serial_number',
        'connection_type',
        'ip_address',
        'port',
        'api_url',
        'username',
        'password',
        'timezone',
        'sync_method',
        'last_synced_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'port' => 'integer',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
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

    public function punches(): HasMany
    {
        return $this->hasMany(
            AttendancePunch::class,
            'attendance_device_id'
        );
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(
            AttendanceRecord::class,
            'attendance_device_id'
        );
    }
}