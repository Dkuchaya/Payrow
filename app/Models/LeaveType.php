<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'is_paid',
        'requires_balance',
        'requires_attachment',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'requires_balance' => 'boolean',
        'requires_attachment' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(LeavePolicy::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}