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

    'scheduled_days',
    'worked_days',
    'paid_leave_days',
    'unpaid_leave_days',
    'absent_days',
    'late_minutes',
    'early_departure_minutes',
    'overtime_hours',

    'total_allowances',
    'gross_pay',
    'total_deductions',
    'net_pay',
    'status',
    'calculation_notes',
];

   protected $casts = [
    'basic_salary' => 'decimal:2',

    'scheduled_days' => 'decimal:2',
    'worked_days' => 'decimal:2',
    'paid_leave_days' => 'decimal:2',
    'unpaid_leave_days' => 'decimal:2',
    'absent_days' => 'decimal:2',
    'late_minutes' => 'integer',
    'early_departure_minutes' => 'integer',
    'overtime_hours' => 'decimal:2',

    'total_allowances' => 'decimal:2',
    'gross_pay' => 'decimal:2',
    'total_deductions' => 'decimal:2',
    'net_pay' => 'decimal:2',
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