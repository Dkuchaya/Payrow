<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionReminder extends Model
{
    protected $fillable = [
        'company_subscription_id',
        'days_before',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'days_before' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(
            CompanySubscription::class,
            'company_subscription_id'
        );
    }
}