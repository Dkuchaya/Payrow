<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\CompanySubscription;
use App\Models\Employee;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'legal_name',
        'slug',
        'registration_number',
        'tax_number',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'logo',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot([
                'is_active',
                'joined_at',
            ])
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
{
    return $this->hasMany(CompanySubscription::class);
}

public function activeSubscription()
{
    return $this->hasOne(CompanySubscription::class)
        ->whereIn('status', ['trial', 'active'])
        ->where(function ($query) {
            $query
                ->where(function ($q) {
                    $q->where('status', 'active')
                        ->where(function ($q) {
                            $q->whereNull('ends_at')
                                ->orWhere('ends_at', '>', now());
                        });
                })
                ->orWhere(function ($q) {
                    $q->where('status', 'trial')
                        ->where(function ($q) {
                            $q->whereNull('trial_ends_at')
                                ->orWhere('trial_ends_at', '>', now());
                        });
                });
        })
        ->latestOfMany();
}

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }   

    public function leaveTypes(): HasMany
    {
        return $this->hasMany(LeaveType::class);
    }

    public function leavePolicies(): HasMany
    {
        return $this->hasMany(LeavePolicy::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function attendanceDevices(): HasMany
{
    return $this->hasMany(
        AttendanceDevice::class,
        'company_id'
    );
}

public function attendancePunches(): HasMany
{
    return $this->hasMany(
        AttendancePunch::class,
        'company_id'
    );
}

public function attendanceRecords(): HasMany
{
    return $this->hasMany(
        AttendanceRecord::class,
        'company_id'
    );
}

public function workSchedules(): HasMany
{
    return $this->hasMany(
        WorkSchedule::class,
        'company_id'
    );
}

public function employeeWorkSchedules(): HasMany
{
    return $this->hasMany(
        EmployeeWorkSchedule::class,
        'company_id'
    );
}

}