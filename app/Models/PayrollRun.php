<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'payroll_period_id',
        'run_number',
        'status',
        'employee_count',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'created_by',
        'approved_by',
        'approved_at',
        'paid_at',
        'notes',
        'apply_attendance_rules',
        'deduct_unpaid_absence',
        'deduct_unpaid_leave',
        'deduct_late_time',
        'deduct_early_departure',
        'pay_overtime',
    ];

    protected $casts = [
        'employee_count' => 'integer',
        'gross_pay' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'apply_attendance_rules' => 'boolean',
        'deduct_unpaid_absence' => 'boolean',
        'deduct_unpaid_leave' => 'boolean',
        'deduct_late_time' => 'boolean',
        'deduct_early_departure' => 'boolean',
        'pay_overtime' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollRunItem::class);
    }
}
