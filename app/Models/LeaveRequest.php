<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\LeavePolicy;

class LeaveRequest extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'leave_policy_id',
        'start_date',
        'end_date',
        'days',
        'reason',
        'attachment_path',
        'status',
        'submitted_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_at',
        'return_date',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'decimal:2',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'return_date' => 'date',
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

    public function policy(): BelongsTo
    {
        return $this->belongsTo(
            LeavePolicy::class,
            'leave_policy_id'
        );
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

        public function leavePolicy()
    {
        return $this->belongsTo(LeavePolicy::class);
    }
}