<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StatutoryRuleAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'statutory_rule_set_id',
        'user_id',
        'auditable_type',
        'auditable_id',
        'action',
        'old_values',
        'new_values',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * The record whose changes are being audited.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Rule set associated with this audit entry.
     */
    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(
            StatutoryRuleSet::class,
            'statutory_rule_set_id'
        );
    }

    /**
     * Administrator who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
