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

        // Existing tax configuration
        'is_taxable',

        // Statutory classifications
        'is_paye_taxable',
        'is_napsa_pensionable',
        'is_nhima_liable',
        'is_sdl_liable',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'calculated_amount' => 'decimal:2',

        'is_taxable' => 'boolean',
        'is_paye_taxable' => 'boolean',
        'is_napsa_pensionable' => 'boolean',
        'is_nhima_liable' => 'boolean',
        'is_sdl_liable' => 'boolean',
    ];

    protected static function booted(): void
{
    static::updating(function (PayrollRunItemComponent $component) {
        $item = $component->payrollRunItem()
            ->with(['payrollRun.payrollPeriod'])
            ->firstOrFail();

        $run = $item->payrollRun;
        $period = $run->payrollPeriod;

        if (
            in_array($run->status, ['approved', 'paid'], true) ||
            $run->approved_by !== null ||
            $run->approved_at !== null ||
            $run->paid_at !== null ||
            !in_array($period->status, ['open', 'processing'], true)
        ) {
            throw new \RuntimeException(
                'Payroll component snapshots cannot be modified after payroll finalization.'
            );
        }
    });

    static::deleting(function (PayrollRunItemComponent $component) {
        $item = $component->payrollRunItem()
            ->with(['payrollRun.payrollPeriod'])
            ->firstOrFail();

        $run = $item->payrollRun;
        $period = $run->payrollPeriod;

        if (
            $run->status !== 'draft' ||
            $run->approved_by !== null ||
            $run->approved_at !== null ||
            $run->paid_at !== null ||
            $period->status !== 'open'
        ) {
            throw new \RuntimeException(
                'Payroll component snapshots can only be deleted from draft payroll in an open period.'
            );
        }
    });
}

    public function payrollRunItem(): BelongsTo
    {
        return $this->belongsTo(PayrollRunItem::class);
    }

    public function payrollComponent(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class);
    }
}
