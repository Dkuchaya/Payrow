<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'employee_number',
        'national_id',

        'first_name',
        'middle_name',
        'last_name',
        'preferred_name',
        'gender',
        'date_of_birth',
        'marital_status',
        'nationality',

        'email',
        'phone',
        'alternative_phone',
        'address',
        'city',
        'country',

        'hire_date',
        'probation_end_date',
        'confirmation_date',
        'contract_start_date',
        'contract_end_date',

        'employment_type',
        'employment_status',

        'department_id',
        'position_id',
        'supervisor_id',

        'basic_salary',
        'salary_currency',
        'pay_frequency',

        'bank_name',
        'bank_branch',
        'bank_account_name',
        'bank_account_number',
        'bank_branch_code',

        'tpin',
        'napsa_number',
        'nhima_number',

        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'emergency_contact_address',

        'separation_date',
        'separation_type',
        'separation_reason',

        'notes',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'probation_end_date' => 'date',
        'confirmation_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'separation_date' => 'date',

        'basic_salary' => 'decimal:2',

        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Company
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /*
    |--------------------------------------------------------------------------
    | User Account
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Organization
    |--------------------------------------------------------------------------
    */

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'supervisor_id'
        );
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(
            Employee::class,
            'supervisor_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute(): string
    {
        return trim(
            $this->first_name . ' ' .
            ($this->middle_name ? $this->middle_name . ' ' : '') .
            $this->last_name
        );
    }

    public function isSeparated(): bool
    {
        return $this->employment_status === 'separated';
    }

    public function isActiveEmployee(): bool
    {
        return in_array(
            $this->employment_status,
            [
                'active',
                'on_leave',
                'suspended',
            ],
            true
        );
    }

    public function separations()
        {
            return $this->hasMany(EmployeeSeparation::class);
        }

        public function activeSeparation()
        {
            return $this->hasOne(EmployeeSeparation::class)
                ->whereIn('status', [
                    'pending',
                    'approved',
                    'clearance',
                    'final_payroll',
                ])
                ->latestOfMany();
        }
}