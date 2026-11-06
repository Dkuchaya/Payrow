<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class StatutoryTaxBand extends Model
{
    use HasFactory;

    protected $fillable = [
        'statutory_rule_id',
        'name',
        'lower_limit',
        'upper_limit',
        'tax_rate',
        'base_tax',
        'sort_order',
    ];

    protected $casts = [
        'lower_limit' => 'decimal:2',
        'upper_limit' => 'decimal:2',
        'tax_rate' => 'decimal:6',
        'base_tax' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Parent statutory rule.
     */
    public function statutoryRule(): BelongsTo
    {
        return $this->belongsTo(StatutoryRule::class);
    }

    /**
     * Audit history for this tax band.
     */
    public function audits(): MorphMany
    {
        return $this->morphMany(StatutoryRuleAudit::class, 'auditable');
    }
}
