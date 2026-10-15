<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use App\Models\LeaveBalanceTransaction;
use Illuminate\Support\Facades\Auth;

class LeaveBalanceService
{
    /**
     * Create a balance for an employee/year/policy.
     */
    public function initializeBalance(
        Employee $employee,
        LeaveType $leaveType,
        LeavePolicy $policy,
        int $year
    ): LeaveBalance {
        $this->validateTenantOwnership($employee, $leaveType, $policy);

        return LeaveBalance::firstOrCreate(
            [
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
            ],
            [
                'leave_policy_id' => $policy->id,
                'entitled_days' => $this->initialEntitlement($policy),
                'accrued_days' => 0,
                'carried_forward_days' => 0,
                'used_days' => 0,
                'adjusted_days' => 0,
                'pending_days' => 0,
            ]
        );
    }

    /**
     * Generate balances for all active employees for a company/year.
     */
    public function generateCompanyBalances(
        Company $company,
        int $year
    ): int {
        $created = 0;

        $employees = Employee::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->get();

        $policies = LeavePolicy::query()
            ->with('leaveType')
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', "{$year}-12-31")
            ->where(function ($query) use ($year) {
                $query
                    ->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', "{$year}-01-01");
            })
            ->get();

        DB::transaction(function () use (
            $employees,
            $policies,
            $year,
            &$created
        ) {
            foreach ($employees as $employee) {
                foreach ($policies as $policy) {
                    $leaveType = $policy->leaveType;

                    if (!$leaveType || !$leaveType->is_active) {
                        continue;
                    }

                    $balance = LeaveBalance::firstOrCreate(
                        [
                            'company_id' => $employee->company_id,
                            'employee_id' => $employee->id,
                            'leave_type_id' => $leaveType->id,
                            'year' => $year,
                        ],
                        [
                            'leave_policy_id' => $policy->id,
                            'entitled_days' => $this->initialEntitlement($policy),
                            'accrued_days' => 0,
                            'carried_forward_days' => 0,
                            'used_days' => 0,
                            'adjusted_days' => 0,
                            'pending_days' => 0,
                        ]
                    );

                    if ($balance->wasRecentlyCreated) {
                        $created++;
                    } elseif (
                        !$balance->leave_policy_id ||
                        $balance->leave_policy_id !== $policy->id
                    ) {
                        $balance->update([
                            'leave_policy_id' => $policy->id,
                        ]);
                    }
                }
            }
        });

        return $created;
    }

    /**
     * Annual/none policies receive their entitlement immediately.
     *
     * Accrued policies start at zero and are earned progressively.
     */
    protected function initialEntitlement(LeavePolicy $policy): float
    {
        return in_array(
            $policy->accrual_method,
            ['annual', 'none'],
            true
        )
            ? (float) $policy->entitlement_days
            : 0.0;
    }

    /**
     * Calculate how many days should have accrued by a particular date.
     *
     * This method is intentionally idempotent:
     * running it multiple times will not keep adding the same accrual.
     */
    public function calculateAccruedDays(
        LeavePolicy $policy,
        int $year,
        ?Carbon $asOf = null
    ): float {
        $asOf ??= now();

        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->endOfDay();

        if ($asOf->lt($yearStart)) {
            return 0.0;
        }

        if ($asOf->gt($yearEnd)) {
            $asOf = $yearEnd;
        }

        /*
         * Policy has not started yet.
         */
        if ($policy->effective_from) {
            $effectiveFrom = Carbon::parse($policy->effective_from);

            if ($effectiveFrom->gt($asOf)) {
                return 0.0;
            }

            if ($effectiveFrom->year > $year) {
                return 0.0;
            }
        }

        $entitlement = (float) $policy->entitlement_days;

        if ($entitlement <= 0) {
            return 0.0;
        }

        /*
         * Annual:
         * Full entitlement is already stored in entitled_days.
         */
        if ($policy->accrual_method === 'annual') {
            return 0.0;
        }

        /*
         * None:
         * No progressive accrual.
         */
        if ($policy->accrual_method === 'none') {
            return 0.0;
        }

        /*
         * Monthly / Quarterly require an accrual amount.
         */
        if (
            in_array(
                $policy->accrual_method,
                ['monthly', 'quarterly'],
                true
            )
        ) {
            $perPeriod = $policy->accrual_days_per_period !== null
                ? (float) $policy->accrual_days_per_period
                : $this->deriveAccrualPerPeriod($policy);

            if ($perPeriod <= 0) {
                return 0.0;
            }

            $start = Carbon::create($year, 1, 1);

            /*
             * Count completed/current accrual periods.
             *
             * January = 1 month
             * February = 2 months
             * ...
             */
            $monthsElapsed = $start->diffInMonths(
                $asOf->copy()->startOfMonth()
            ) + 1;

            if ($policy->accrual_method === 'monthly') {
                $periods = $monthsElapsed;
            } else {
                $periods = (int) ceil($monthsElapsed / 3);
            }

            return min(
                $entitlement,
                round($periods * $perPeriod, 2)
            );
        }

        /*
         * Per payroll is deliberately not automatically calculated here.
         *
         * The Payroll Engine will call accrueForPayroll().
         */
        if ($policy->accrual_method === 'per_payroll') {
            return 0.0;
        }

        return 0.0;
    }

    /**
     * Apply the calculated accrual to one balance.
     */
    public function accrue(
    LeaveBalance $balance,
    ?Carbon $asOf = null
): LeaveBalance {
    $asOf ??= now();

    $balance->loadMissing('leavePolicy');

    $policy = $balance->leavePolicy;

    if (!$policy || !$policy->is_active) {
        return $balance;
    }

    $oldAccrued = (float) $balance->accrued_days;

    $newAccrued = $this->calculateAccruedDays(
        $policy,
        (int) $balance->year,
        $asOf
    );

    // Nothing changed.
    if (round($newAccrued, 2) === round($oldAccrued, 2)) {
        return $balance;
    }

    /*
     * Available balance before the accrual.
     *
     * This deliberately calculates the balance using the
     * existing values before changing accrued_days.
     */
    $before = $this->calculateAvailable($balance);

    $balance->accrued_days = $newAccrued;
    $balance->save();

    /*
     * Calculate available balance after the accrual.
     */
    $after = $this->calculateAvailable($balance);

    $difference = round($after - $before, 2);

    if ($difference != 0) {
        $this->recordTransaction(
            balance: $balance,
            type: 'accrual',
            days: $difference,
            before: $before,
            after: $after,
            description: 'Leave balance accrual',
            reason: 'Automatic leave accrual'
        );
    }

    return $balance->refresh();
}

    /**
     * Accrue all applicable balances for a company.
     */
public function accrueCompanyBalances(
    Company $company,
    int $year,
    ?Carbon $asOf = null
): int {
    $asOf ??= now();

    $updated = 0;

    DB::transaction(function () use (
        $company,
        $year,
        $asOf,
        &$updated
    ) {
        $balances = LeaveBalance::query()
            ->with('leavePolicy')
            ->where('company_id', $company->id)
            ->where('year', $year)
            ->lockForUpdate()
            ->get();

        foreach ($balances as $balance) {
            $this->accrue($balance, $asOf);
            $updated++;
        }
    });

    return $updated;
}

    /**
     * Payroll Engine integration.
     *
     * Called after a payroll period is successfully processed.
     */
    public function accrueForPayroll(
        LeaveBalance $balance,
        ?float $days = null
    ): LeaveBalance {
        $balance->loadMissing('leavePolicy');

        $policy = $balance->leavePolicy;

        if (!$policy) {
            throw new InvalidArgumentException(
                'This leave balance has no leave policy.'
            );
        }

        if ($policy->accrual_method !== 'per_payroll') {
            return $balance;
        }

        $accrual = $days !== null
            ? $days
            : (float) ($policy->accrual_days_per_period ?? 0);

        if ($accrual <= 0) {
            return $balance;
        }

        $newAccrued = min(
            (float) $policy->entitlement_days,
            (float) $balance->accrued_days + $accrual
        );

        $balance->update([
            'entitled_days' => 0,
            'accrued_days' => round($newAccrued, 2),
        ]);

        return $balance->fresh();
    }

    /**
     * Carry forward unused leave into a new year.
     */
public function carryForward(
    LeaveBalance $previousBalance,
    LeaveBalance $newBalance
): float {
    $previousBalance->loadMissing('leavePolicy');

    $policy = $previousBalance->leavePolicy;

    if (!$policy || !$policy->carry_forward_allowed) {
        $newBalance->carried_forward_days = 0;
        $newBalance->save();

        return 0;
    }

    $available = $this->calculateAvailable($previousBalance);

    $carryForward = $available;

    if ($policy->max_carry_forward_days !== null) {
        $carryForward = min(
            $carryForward,
            (float) $policy->max_carry_forward_days
        );
    }

    $carryForward = max(0, $carryForward);

    $newBalance->carried_forward_days = $carryForward;
    $newBalance->save();

    return $carryForward;
}


public function carryForwardCompanyBalances(
    Company $company,
    int $fromYear
): int {
    $toYear = $fromYear + 1;

    $processed = 0;

    DB::transaction(function () use (
        $company,
        $fromYear,
        $toYear,
        &$processed
    ) {
        $previousBalances = LeaveBalance::query()
            ->with([
                'leavePolicy',
                'leaveType',
            ])
            ->where('company_id', $company->id)
            ->where('year', $fromYear)
            ->lockForUpdate()
            ->get();

        foreach ($previousBalances as $previousBalance) {
            $newBalance = LeaveBalance::query()
                ->where('company_id', $company->id)
                ->where('employee_id', $previousBalance->employee_id)
                ->where('leave_type_id', $previousBalance->leave_type_id)
                ->where('year', $toYear)
                ->lockForUpdate()
                ->first();

            if (!$newBalance) {
                $newBalance = LeaveBalance::create([
                    'company_id' => $company->id,
                    'employee_id' => $previousBalance->employee_id,
                    'leave_type_id' => $previousBalance->leave_type_id,
                    'leave_policy_id' => $previousBalance->leave_policy_id,
                    'year' => $toYear,
                    'entitled_days' => 0,
                    'accrued_days' => 0,
                    'carried_forward_days' => 0,
                    'used_days' => 0,
                    'adjusted_days' => 0,
                    'pending_days' => 0,
                ]);
            }

            $newBalance->leave_policy_id =
                $previousBalance->leave_policy_id;

            $this->carryForward(
                $previousBalance,
                $newBalance
            );

            $processed++;
        }
    });

    return $processed;
}

    /**
     * Calculate current available leave.
     */
    public function calculateAvailable(LeaveBalance $balance): float
    {
        $balance->loadMissing('leavePolicy');

        $policy = $balance->leavePolicy;

        if (
            $policy &&
            in_array(
                $policy->accrual_method,
                ['monthly', 'quarterly', 'per_payroll'],
                true
            )
        ) {
            $base = (float) $balance->accrued_days;
        } else {
            $base = (float) $balance->entitled_days;
        }

        return round(
            max(
                0,
                $base
                + (float) $balance->carried_forward_days
                + (float) $balance->adjusted_days
                - (float) $balance->used_days
                - (float) $balance->pending_days
            ),
            2
        );
    }

    /**
     * Manually adjust a balance.
     */
   public function adjust(
    LeaveBalance $balance,
    float $days,
    string $reason
): LeaveBalance {
    if (trim($reason) === '') {
        throw new InvalidArgumentException(
            'An adjustment reason is required.'
        );
    }

    $before = $this->calculateAvailable($balance);

    $balance->increment('adjusted_days', $days);

    $balance->update([
        'adjustment_reason' => $reason,
    ]);

    $balance->refresh();

    $after = $this->calculateAvailable($balance);

    $difference = round($after - $before, 2);

    if ($difference != 0) {
        $this->recordTransaction(
            balance: $balance,
            type: 'adjustment',
            days: $difference,
            before: $before,
            after: $after,
            description: 'Manual leave balance adjustment',
            reason: $reason
        );
    }

    return $balance->fresh();
}
    /**
     * Reserve pending leave.
     */
    public function addPendingDays(
        LeaveBalance $balance,
        float $days
    ): LeaveBalance {
        $balance->increment('pending_days', $days);

        return $balance->fresh();
    }

    /**
     * Remove pending leave.
     */
    public function removePendingDays(
        LeaveBalance $balance,
        float $days
    ): LeaveBalance {
        $balance->decrement(
            'pending_days',
            min($days, (float) $balance->pending_days)
        );

        return $balance->fresh();
    }

    /**
     * Add used leave.
     */
    public function addUsedDays(
        LeaveBalance $balance,
        float $days
    ): LeaveBalance {
        $balance->increment('used_days', $days);

        return $balance->fresh();
    }

    /**
     * Remove used leave when an approved request is cancelled.
     */
    public function removeUsedDays(
        LeaveBalance $balance,
        float $days
    ): LeaveBalance {
        $balance->decrement(
            'used_days',
            min($days, (float) $balance->used_days)
        );

        return $balance->fresh();
    }

    /**
     * Calculate available days.
     */
    public function availableDays(LeaveBalance $balance): float
    {
        return $this->calculateAvailable($balance);
    }

    /**
     * Derive monthly/quarterly accrual when the policy
     * does not explicitly provide accrual_days_per_period.
     */
    protected function deriveAccrualPerPeriod(
        LeavePolicy $policy
    ): float {
        $entitlement = (float) $policy->entitlement_days;

        if ($policy->accrual_method === 'monthly') {
            return round($entitlement / 12, 2);
        }

        if ($policy->accrual_method === 'quarterly') {
            return round($entitlement / 4, 2);
        }

        return 0.0;
    }

    /**
     * Ensure all records belong to the same company.
     */
    protected function validateTenantOwnership(
        Employee $employee,
        LeaveType $leaveType,
        LeavePolicy $policy
    ): void {
        if (
            $employee->company_id !== $leaveType->company_id ||
            $employee->company_id !== $policy->company_id ||
            $leaveType->company_id !== $policy->company_id
        ) {
            throw new InvalidArgumentException(
                'Employee, leave type and leave policy must belong to the same company.'
            );
        }

        if ($policy->leave_type_id !== $leaveType->id) {
            throw new InvalidArgumentException(
                'The leave policy does not belong to the selected leave type.'
            );
        }
    }

    public function recordTransaction(
    LeaveBalance $balance,
    string $type,
    float $days,
    float $before,
    float $after,
    ?string $description = null,
    ?string $reason = null,
    ?string $sourceType = null,
    ?int $sourceId = null,
    ?int $createdBy = null
): LeaveBalanceTransaction {
        return LeaveBalanceTransaction::create([
            'company_id' => $balance->company_id,
            'employee_id' => $balance->employee_id,
            'leave_type_id' => $balance->leave_type_id,
            'leave_balance_id' => $balance->id,
            'transaction_type' => $type,
            'days' => $days,
            'balance_before' => $before,
            'balance_after' => $after,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'description' => $description,
            'reason' => $reason,
            'created_by' => $createdBy ?? Auth::id(),
        ]);
    }


    
}