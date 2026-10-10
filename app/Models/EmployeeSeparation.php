<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
class EmployeeSeparation extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'separation_type',
        'notice_date',
        'effective_date',
        'last_working_date',
        'reason',
        'notes',
        'status',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'notice_date' => 'date',
        'effective_date' => 'date',
        'last_working_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function clearances(): HasMany
{
    return $this->hasMany(
        EmployeeSeparationClearance::class,
        'employee_separation_id'
    );
}

public function requiredClearances(): HasMany
{
    return $this->hasMany(
        EmployeeSeparationClearance::class,
        'employee_separation_id'
    )->where('is_required', true);
}

public function pendingRequiredClearances(): HasMany
{
    return $this->requiredClearances()
        ->where('status', '!=', 'cleared');
}

public function clearanceComplete(): bool
{
    return ! $this->requiredClearances()
        ->where('status', '!=', 'cleared')
        ->exists();
}

public function finalSettlement(): HasOne
{
    return $this->hasOne(
        EmployeeFinalSettlement::class,
        'employee_separation_id'
    );
}



    
}