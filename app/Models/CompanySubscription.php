<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class CompanySubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'subscription_plan_id',
        'status',
        'billing_cycle',
        'amount',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'payment_reference',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            SubscriptionPlan::class,
            'subscription_plan_id'
        );
    }

   public function isActive(): bool
{
    if (! in_array($this->status, ['trial', 'active'], true)) {
        return false;
    }

    // A normal subscription has expired.
    if (
        $this->status === 'active' &&
        $this->ends_at &&
        $this->ends_at->isPast()
    ) {
        return false;
    }

    // A trial has expired.
    if (
        $this->status === 'trial' &&
        $this->trial_ends_at &&
        $this->trial_ends_at->isPast()
    ) {
        return false;
    }

    return true;
}

    public function billingCycleMonths(): int
    {
        return match ($this->billing_cycle) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
            default => 1,
        };
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function reminders(): HasMany
{
    return $this->hasMany(
        SubscriptionReminder::class,
        'company_subscription_id'
    );
}
}