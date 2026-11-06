<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRunItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'basic_salary',

        // Attendance summary
        'scheduled_days',
        'worked_days',
        'paid_leave_days',
        'unpaid_leave_days',
        'absent_days',
        'late_minutes',
        'early_departure_minutes',
        'overtime_hours',

        // Payroll totals
        'total_allowances',
        'gross_pay',
        'total_deductions',
        'net_pay',

        // Employee statutory deductions
        'paye',
        'employee_napsa',
        'employee_nhima',
        'employee_statutory_deductions',

        // Employer statutory contributions
        'employer_napsa',
        'employer_nhima',
        'skills_development_levy',
        'employer_statutory_contributions',
        'employer_total_cost',

        // Calculation details
        'calculation_notes',
        'statutory_breakdown',

        'status',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',

        // Attendance
        'scheduled_days' => 'decimal:2',
        'worked_days' => 'decimal:2',
        'paid_leave_days' => 'decimal:2',
        'unpaid_leave_days' => 'decimal:2',
        'absent_days' => 'decimal:2',
        'late_minutes' => 'integer',
        'early_departure_minutes' => 'integer',
        'overtime_hours' => 'decimal:2',

        // Payroll totals
        'total_allowances' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',

        // Employee statutory deductions
        'paye' => 'decimal:2',
        'employee_napsa' => 'decimal:2',
        'employee_nhima' => 'decimal:2',
        'employee_statutory_deductions' => 'decimal:2',

        // Employer statutory contributions
        'employer_napsa' => 'decimal:2',
        'employer_nhima' => 'decimal:2',
        'skills_development_levy' => 'decimal:2',
        'employer_statutory_contributions' => 'decimal:2',
        'employer_total_cost' => 'decimal:2',

        // Calculation details
        'calculation_notes' => 'array',
        'statutory_breakdown' => 'array',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(PayrollRunItemComponent::class);
    }
}
