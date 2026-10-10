<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'timezone',
        'date_format',
        'language',
        'email_notifications',
        'system_notifications',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'system_notifications' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}