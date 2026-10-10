<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeFinalSettlement extends Model
{
    protected $fillable = [
        'company_id',
        'employee_separation_id',
        'employee_id',

        'last_working_date',
        'settlement_date',

        'basic_salary',
        'salary_due',
        'leave_pay',
        'allowances',
        'overtime',
        'bonus',
        'commission',
        'notice_pay',
        'gratuity',
        'severance_pay',
        'other_earnings',

        'loan_deduction',
        'advance_deduction',
        'notice_deduction',
        'statutory_deductions',
        'other_deductions',

        'gross_amount',
        'total_deductions',
        'net_amount',

        'status',

        'prepared_by',
        'reviewed_by',
        'reviewed_at',

        'approved_by',
        'approved_at',

        'paid_by',
        'paid_at',

        'payment_reference',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'last_working_date' => 'date',
        'settlement_date' => 'date',

        'basic_salary' => 'decimal:2',
        'salary_due' => 'decimal:2',
        'leave_pay' => 'decimal:2',
        'allowances' => 'decimal:2',
        'overtime' => 'decimal:2',
        'bonus' => 'decimal:2',
        'commission' => 'decimal:2',
        'notice_pay' => 'decimal:2',
        'gratuity' => 'decimal:2',
        'severance_pay' => 'decimal:2',
        'other_earnings' => 'decimal:2',

        'loan_deduction' => 'decimal:2',
        'advance_deduction' => 'decimal:2',
        'notice_deduction' => 'decimal:2',
        'statutory_deductions' => 'decimal:2',
        'other_deductions' => 'decimal:2',

        'gross_amount' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_amount' => 'decimal:2',

        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function separation(): BelongsTo
    {
        return $this->belongsTo(
            EmployeeSeparation::class,
            'employee_separation_id'
        );
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}