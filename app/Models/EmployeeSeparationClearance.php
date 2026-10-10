<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSeparationClearance extends Model
{
    protected $fillable = [
        'company_id',
        'employee_separation_id',
        'category',
        'title',
        'description',
        'department_id',
        'responsible_user_id',
        'is_required',
        'status',
        'comments',
        'cleared_by',
        'cleared_at',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'cleared_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function separation(): BelongsTo
    {
        return $this->belongsTo(
            EmployeeSeparation::class,
            'employee_separation_id'
        );
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function clearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }
}