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
        'employer_napsa',
        'employer_nhima',
        'skills_development_levy',
        'employer_statutory_contributions',
        'employer_total_cost',
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
        'employer_napsa' => 'decimal:2',
        'employer_nhima' => 'decimal:2',
        'skills_development_levy' => 'decimal:2',
        'employer_statutory_contributions' => 'decimal:2',
        'employer_total_cost' => 'decimal:2',
    ];

   protected static function booted(): void
{
    static::deleting(function (PayrollRun $payrollRun) {
        if (
            $payrollRun->status !== 'draft' ||
            $payrollRun->payrollPeriod?->status !== 'open' ||           
            $payrollRun->approved_by !== null ||
            $payrollRun->approved_at !== null ||
            $payrollRun->paid_at !== null
        ) {
            throw new \RuntimeException(
                'Approved or paid payroll runs cannot be deleted.'
            );
        }

        // Explicitly delete employee payroll records first.
        // Their component snapshots are removed by the existing cascade.
        $payrollRun->items()->delete();
    });
}

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
