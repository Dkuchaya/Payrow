<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'employee_limit',
        'is_active',
    ];

    protected $casts = [
        'employee_limit' => 'integer',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(
            CompanySubscription::class,
            'subscription_plan_id'
        );
    }

    public function prices(): HasMany
    {
        return $this->hasMany(
            SubscriptionPlanPrice::class,
            'subscription_plan_id'
        );
    }

    public function isUnlimited(): bool
    {
        return $this->employee_limit === null;
    }

    public function priceFor(string $billingCycle): ?SubscriptionPlanPrice
    {
        return $this->prices()
            ->where('billing_cycle', $billingCycle)
            ->where('is_active', true)
            ->first();
    }
}