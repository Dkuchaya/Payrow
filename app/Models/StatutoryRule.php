<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class StatutoryRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'statutory_rule_set_id',
        'code',
        'name',
        'description',
        'rule_type',
        'applies_to',
        'calculation_method',
        'earnings_basis',
        'rate',
        'fixed_amount',
        'minimum_earnings',
        'maximum_earnings',
        'contribution_ceiling',
        'settings',
        'is_active',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'fixed_amount' => 'decimal:2',
        'minimum_earnings' => 'decimal:2',
        'maximum_earnings' => 'decimal:2',
        'contribution_ceiling' => 'decimal:2',
        'settings' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Rule types
    public const TYPE_TAX = 'tax';
    public const TYPE_PENSION = 'pension';
    public const TYPE_HEALTH = 'health';
    public const TYPE_LEVY = 'levy';

    // Calculation methods
    public const METHOD_PERCENTAGE = 'percentage';
    public const METHOD_PROGRESSIVE = 'progressive';
    public const METHOD_FIXED_AMOUNT = 'fixed_amount';

    /**
     * Parent statutory rule set.
     */
    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(StatutoryRuleSet::class, 'statutory_rule_set_id');
    }

    /**
     * Progressive tax bands belonging to this rule.
     */
    public function taxBands(): HasMany
    {
        return $this->hasMany(StatutoryTaxBand::class);
    }

    /**
     * Audit history for this rule.
     */
    public function audits(): MorphMany
    {
        return $this->morphMany(StatutoryRuleAudit::class, 'auditable');
    }
}
