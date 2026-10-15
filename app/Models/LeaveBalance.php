<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\LeaveBalanceTransaction;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\LeavePolicy;

class LeaveBalance extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'leave_policy_id',
        'year',
        'entitled_days',
        'accrued_days',
        'carried_forward_days',
        'used_days',
        'adjusted_days',
        'pending_days',
        'adjustment_reason',
    ];

    protected $casts = [
        'year' => 'integer',
        'entitled_days' => 'decimal:2',
        'accrued_days' => 'decimal:2',
        'carried_forward_days' => 'decimal:2',
        'used_days' => 'decimal:2',
        'adjusted_days' => 'decimal:2',
        'pending_days' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leavePolicy(): BelongsTo
    {
        return $this->belongsTo(LeavePolicy::class);
    }

    public function getAvailableDaysAttribute(): float
    {
        return max(0, (
            (float) $this->entitled_days
            + (float) $this->accrued_days
            + (float) $this->carried_forward_days
            + (float) $this->adjusted_days
            - (float) $this->used_days
            - (float) $this->pending_days
        ));
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LeaveBalanceTransaction::class, 'leave_balance_id');
    }
}