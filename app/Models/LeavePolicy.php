<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeavePolicy extends Model
{
    protected $fillable = [
        'company_id',
        'leave_type_id',
        'effective_from',
        'effective_to',
        'entitlement_days',
        'accrual_method',
        'accrual_days_per_period',
        'carry_forward_allowed',
        'max_carry_forward_days',
        'min_notice_days',
        'max_consecutive_days',
        'allow_negative_balance',
        'requires_attachment',
        'is_active',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'entitlement_days' => 'decimal:2',
        'accrual_days_per_period' => 'decimal:2',
        'max_carry_forward_days' => 'decimal:2',
        'max_consecutive_days' => 'decimal:2',
        'carry_forward_allowed' => 'boolean',
        'allow_negative_balance' => 'boolean',
        'requires_attachment' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}