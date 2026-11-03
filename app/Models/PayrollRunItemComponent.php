<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRunItemComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_item_id',
        'payroll_component_id',
        'name',
        'type',
        'calculation_type',
        'amount',
        'percentage',
        'calculated_amount',
        'is_taxable',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'calculated_amount' => 'decimal:2',
        'is_taxable' => 'boolean',
    ];

    public function payrollRunItem(): BelongsTo
    {
        return $this->belongsTo(PayrollRunItem::class);
    }

    public function payrollComponent(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class);
    }
}