<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatutoryRuleSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_code',
        'country_name',
        'name',
        'tax_year',
        'version',
        'effective_from',
        'effective_to',
        'status',
        'created_by',
        'activated_at',
        'notes',
    ];

    protected $casts = [
        'tax_year' => 'integer',
        'version' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'activated_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    public function rules(): HasMany
    {
        return $this->hasMany(StatutoryRule::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(StatutoryRuleAudit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
