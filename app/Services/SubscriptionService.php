<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySubscription;

class SubscriptionService
{
    /**
     * Get the company's current active subscription.
     */
    public function getActiveSubscription(
        Company $company
    ): ?CompanySubscription {
        return $company->activeSubscription()
            ->with('plan.prices')
            ->first();
    }

    /**
     * Get the company's current subscription plan.
     */
    public function getPlan(
        Company $company
    ) {
        return $this->getActiveSubscription($company)?->plan;
    }

    /**
     * Get employee limit.
     *
     * NULL means unlimited.
     */
    public function getEmployeeLimit(
        Company $company
    ): ?int {
        return $this->getPlan($company)?->employee_limit;
    }

    /**
     * Determine whether the company has unlimited employees.
     */
    public function hasUnlimitedEmployees(
        Company $company
    ): bool {
        return $this->getEmployeeLimit($company) === null;
    }

    /**
     * Get the number of billable employees.
     *
     * Active, on leave and suspended employees count.
     * Separated employees do not count.
     */
    public function getEmployeeCount(
        Company $company
    ): int {
        return $company->employees()
           ->whereIn('employment_status', [
                'active',
                'on_leave',
                'suspended',
            ])
            ->count();
    }

    /**
     * Determine whether another employee can be added.
     */
    public function canAddEmployee(
        Company $company
    ): bool {
        if (!$this->canUseSystem($company)) {
            return false;
        }

        $limit = $this->getEmployeeLimit($company);

        if ($limit === null) {
            return true;
        }

        return $this->getEmployeeCount($company) < $limit;
    }

    public function canUseSystem(
            Company $company
        ): bool {
            $subscription = $this->getActiveSubscription($company);

            return $subscription !== null
                && $subscription->isActive();
        }

    /**
     * Get remaining employee seats.
     *
     * NULL = unlimited.
     */
    public function remainingEmployeeSeats(
        Company $company
    ): ?int {
        $limit = $this->getEmployeeLimit($company);

        if ($limit === null) {
            return null;
        }

        return max(
            0,
            $limit - $this->getEmployeeCount($company)
        );
    }

    /**
     * Determine whether the employee limit has been reached.
     */
    public function employeeLimitReached(
        Company $company
    ): bool {
        return !$this->canAddEmployee($company);
    }

    /**
     * Get subscription usage information.
     */
    public function getUsage(
        Company $company
    ): array {
        $subscription = $this->getActiveSubscription($company);
        $plan = $subscription?->plan;

        $employeeCount = $this->getEmployeeCount($company);
        $limit = $plan?->employee_limit;

        return [
            'subscription' => $subscription,
            'plan' => $plan,
            'employee_count' => $employeeCount,
            'employee_limit' => $limit,
            'remaining_seats' => $limit === null
                ? null
                : max(0, $limit - $employeeCount),
            'unlimited' => $limit === null,
            'limit_reached' => $limit !== null
                && $employeeCount >= $limit,
        ];
    }
}