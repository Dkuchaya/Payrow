<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class SubscriptionPayment extends Model
{
    protected $fillable = [
        'company_subscription_id',
        'amount',
        'currency',
        'payment_date',
        'payment_reference',
        'payment_method',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(
            CompanySubscription::class,
            'company_subscription_id'
        );
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}