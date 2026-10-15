<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveBalanceTransaction extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'leave_balance_id',
        'transaction_type',
        'days',
        'balance_before',
        'balance_after',
        'source_type',
        'source_id',
        'description',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'days' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
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

    public function leaveBalance(): BelongsTo
    {
        return $this->belongsTo(LeaveBalance::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LeaveBalanceTransaction::class);
    }
}