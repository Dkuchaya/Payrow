<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use App\Models\StatutoryRule;
use App\Models\StatutoryRuleSet;
use Illuminate\Validation\ValidationException;

class ZambiaStatutoryCalculator
{
    /**
     * Calculate employee statutory deductions and employer contributions.
     *
     * The gross pay and overtime pay must be calculated by
     * PayrollCalculationService before calling this method.
     */
    public function calculate(
        PayrollRunItem $item,
        PayrollPeriod $period,
        Company $company,
        float $grossPay,
        float $overtimePay
    ): array {
        $ruleSet = $this->getApplicableRuleSet($period);

        $rules = $ruleSet->rules()
            ->where('is_active', true)
            ->with('taxBands')
            ->get()
            ->keyBy('code');

        $this->validateRequiredRules($rules);

        $components = $item->components;

        /*
         * PAYE taxable emoluments.
         *
         * Basic salary + taxable allowances + overtime.
         * NAPSA contributions are not deducted before PAYE.
         */
        $taxableAllowances = (float) $components
            ->where('type', 'allowance')
            ->filter(fn ($component) => $component->is_paye_taxable)
            ->sum('calculated_amount');

        $taxableEmoluments = round(
            (float) $item->basic_salary
            + $taxableAllowances
            + $overtimePay,
            2
        );

        /*
         * NAPSA pensionable earnings.
         *
         * Uses the component classifications and includes overtime.
         */
        $pensionableAllowances = (float) $components
            ->where('type', 'allowance')
            ->filter(fn ($component) => $component->is_napsa_pensionable)
            ->sum('calculated_amount');

        $pensionableEarnings = round(
            (float) $item->basic_salary
            + $pensionableAllowances
            + $overtimePay,
            2
        );

        /*
         * NHIMA employee declared income.
         *
         * Uses the configured employee-eligible components.
         */
        $nhimaAllowances = (float) $components
            ->where('type', 'allowance')
            ->filter(fn ($component) => $component->is_nhima_liable)
            ->sum('calculated_amount');

        $nhimaEmployeeIncome = round(
            (float) $item->basic_salary
            + $nhimaAllowances
            + $overtimePay,
            2
        );

        /*
         * NHIMA employer contribution is based on basic salary.
         */
        $nhimaEmployerIncome = round(
            (float) $item->basic_salary,
            2
        );

        /*
         * SDL is based on gross emoluments.
         * Gross pay already includes basic salary, allowances and overtime.
         */
        /**
             * SDL chargeable emoluments.
             *
             * Basic salary + SDL-liable allowances + overtime.
             */
            $sdlAllowances = (float) $components
                ->where('type', 'allowance')
                ->filter(fn ($component) => $component->is_sdl_liable)
                ->sum('calculated_amount');

            $sdlEmoluments = round(
                (float) $item->basic_salary
                + $sdlAllowances
                + $overtimePay,
                2
            );

        /*
         * Employee statutory deductions.
         */
        $paye = $this->calculatePaye(
            $rules->get('PAYE'),
            $taxableEmoluments
        );

        $employeeNapsa = $this->calculatePercentage(
            $rules->get('NAPSA_EMPLOYEE'),
            $pensionableEarnings
        );

        $employeeNhima = $this->calculatePercentage(
            $rules->get('NHIMA_EMPLOYEE'),
            $nhimaEmployeeIncome
        );

        /*
         * Employer statutory contributions.
         */
        $employerNapsa = $this->calculatePercentage(
            $rules->get('NAPSA_EMPLOYER'),
            $pensionableEarnings
        );

        $employerNhima = $this->calculatePercentage(
            $rules->get('NHIMA_EMPLOYER'),
            $nhimaEmployerIncome
        );

        $sdlExemption = $this->getSdlExemption(
            $company,
            $rules->get('SDL')
        );

        $sdl = $sdlExemption['is_exempt']
            ? 0.00
            : $this->calculatePercentage(
                $rules->get('SDL'),
                $sdlEmoluments
            );

        $employeeStatutoryDeductions = round(
            $paye + $employeeNapsa + $employeeNhima,
            2
        );

        $employerStatutoryContributions = round(
            $employerNapsa + $employerNhima + $sdl,
            2
        );

        $employerTotalCost = round(
            $grossPay + $employerStatutoryContributions,
            2
        );

        /*
         * Detailed statutory audit information.
         */
        $breakdown = [
            'country_code' => $ruleSet->country_code,
            'rule_set_id' => $ruleSet->id,
            'rule_set_name' => $ruleSet->name,
            'tax_year' => (int) $ruleSet->tax_year,
            'version' => (int) $ruleSet->version,
            'effective_from' => $ruleSet->effective_from?->toDateString(),
            'effective_to' => $ruleSet->effective_to?->toDateString(),

            'earnings_bases' => [
                'basic_salary' => round((float) $item->basic_salary, 2),
                'taxable_allowances' => round($taxableAllowances, 2),
                'pensionable_allowances' => round($pensionableAllowances, 2),
                'nhima_allowances' => round($nhimaAllowances, 2),
                'overtime_pay' => round($overtimePay, 2),
                'gross_pay' => round($grossPay, 2),
                'taxable_emoluments' => $taxableEmoluments,
                'pensionable_earnings' => $pensionableEarnings,
                'nhima_employee_income' => $nhimaEmployeeIncome,
                'nhima_employer_income' => $nhimaEmployerIncome,
                'sdl_emoluments' => $sdlEmoluments,
            ],

            'employee_contributions' => [
                'paye' => $paye,
                'napsa' => $employeeNapsa,
                'nhima' => $employeeNhima,
                'total' => $employeeStatutoryDeductions,
            ],

            'employer_contributions' => [
                'napsa' => $employerNapsa,
                'nhima' => $employerNhima,
                'sdl' => $sdl,
                'total' => $employerStatutoryContributions,
            ],

            'sdl_exemption' => $sdlExemption,

            'applied_rules' => [
                'paye' => $this->ruleDetails($rules->get('PAYE')),
                'employee_napsa' => $this->ruleDetails($rules->get('NAPSA_EMPLOYEE')),
                'employer_napsa' => $this->ruleDetails($rules->get('NAPSA_EMPLOYER')),
                'employee_nhima' => $this->ruleDetails($rules->get('NHIMA_EMPLOYEE')),
                'employer_nhima' => $this->ruleDetails($rules->get('NHIMA_EMPLOYER')),
                'sdl' => $this->ruleDetails($rules->get('SDL')),
            ],

            'employer_total_cost' => $employerTotalCost,
        ];

        return [
            'rule_set_id' => $ruleSet->id,

            'paye' => $paye,
            'employee_napsa' => $employeeNapsa,
            'employee_nhima' => $employeeNhima,
            'employee_statutory_deductions' => $employeeStatutoryDeductions,

            'employer_napsa' => $employerNapsa,
            'employer_nhima' => $employerNhima,
            'skills_development_levy' => $sdl,
            'employer_statutory_contributions' => $employerStatutoryContributions,
            'employer_total_cost' => $employerTotalCost,

            'statutory_breakdown' => $breakdown,
        ];
    }

    /**
     * Get a rule set that covers the entire payroll period.
     */
    private function getApplicableRuleSet(
        PayrollPeriod $period
    ): StatutoryRuleSet {
        $ruleSet = StatutoryRuleSet::query()
            ->where('country_code', 'ZM')
            ->where('status', StatutoryRuleSet::STATUS_ACTIVE)
            ->whereDate('effective_from', '<=', $period->start_date)
            ->where(function ($query) use ($period) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $period->end_date);
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('version')
            ->first();

        if (!$ruleSet) {
            throw ValidationException::withMessages([
                'statutory_rules' =>
                    'No single active Zambia statutory rule set covers the entire payroll period. Check the rule-set effective dates.',
            ]);
        }

        return $ruleSet;
    }

    /**
     * Validate that all required statutory rules exist.
     */
    private function validateRequiredRules($rules): void
    {
        $requiredRules = [
            'PAYE',
            'NAPSA_EMPLOYEE',
            'NAPSA_EMPLOYER',
            'NHIMA_EMPLOYEE',
            'NHIMA_EMPLOYER',
            'SDL',
        ];

        $missing = [];

        foreach ($requiredRules as $code) {
            if (!$rules->has($code)) {
                $missing[] = $code;
            }
        }

        if (!empty($missing)) {
            throw ValidationException::withMessages([
                'statutory_rules' =>
                    'Missing or inactive statutory rules: '.implode(', ', $missing),
            ]);
        }
    }

   
/**
 * Calculate PAYE using configured progressive tax bands.
 */
private function calculatePaye(
    ?StatutoryRule $rule,
    float $income
): float {
    if (!$rule || !$rule->is_active) {
        throw ValidationException::withMessages([
            'paye' => 'An active PAYE rule is required.',
        ]);
    }

    $bands = $rule->taxBands->sortBy('sort_order')->values();

    if ($bands->isEmpty()) {
        throw ValidationException::withMessages([
            'paye' => 'PAYE tax bands have not been configured.',
        ]);
    }

    if ($income <= 0) {
        return 0.00;
    }

    $selectedBand = null;

    foreach ($bands as $band) {
        $lower = (float) $band->lower_limit;

        $upper = $band->upper_limit !== null
            ? (float) $band->upper_limit
            : null;

        if (
            $income >= $lower &&
            ($upper === null || $income < $upper)
        ) {
            $selectedBand = $band;
            break;
        }
    }

    if (!$selectedBand) {
        throw ValidationException::withMessages([
            'paye' => 'No PAYE tax band covers the employee taxable income. Check your statutory tax band configuration.',
        ]);
    }

    $lower = (float) $selectedBand->lower_limit;
    $rate = (float) $selectedBand->tax_rate;
    $baseTax = (float) $selectedBand->base_tax;

    return round(
        $baseTax + (($income - $lower) * ($rate / 100)),
        2
    );
}

    /**
     * Calculate percentage-based statutory contributions.
     */
    private function calculatePercentage(
        ?StatutoryRule $rule,
        float $earnings
    ): float {
        if (!$rule || !$rule->is_active) {
            throw ValidationException::withMessages([
                'statutory_rules' => 'A required statutory contribution rule is inactive.',
            ]);
        }

        $rate = (float) $rule->rate;

        $maximum = $rule->maximum_earnings !== null
            ? (float) $rule->maximum_earnings
            : $earnings;

        $minimum = $rule->minimum_earnings !== null
            ? (float) $rule->minimum_earnings
            : 0;

        $eligibleEarnings = min($earnings, $maximum);

        if ($eligibleEarnings < $minimum) {
            return 0.00;
        }

        return round(
            $eligibleEarnings * ($rate / 100),
            2
        );
    }

    /**
     * Determine whether the company qualifies for SDL exemption.
     *
     * Automatic statutory exemptions:
     * - Public service
     * - Local authority
     * - Annual turnover below K800,000
     *
     * Other approved exemptions must be verified and recorded
     * using the company's is_sdl_exempt field.
     */
   private function getSdlExemption(
    Company $company,
    ?StatutoryRule $rule
): array {
    $organisationType = strtolower(
        trim((string) $company->organisation_type)
    );

    $turnover = $company->annual_turnover !== null
        ? (float) $company->annual_turnover
        : null;

    // Read exemption settings from the active SDL rule.
    $settings = $rule?->settings ?? [];

    $turnoverThreshold = isset($settings['annual_turnover_exemption_threshold'])
        ? (float) $settings['annual_turnover_exemption_threshold']
        : null;

    $exemptCategories = array_map(
        'strtolower',
        $settings['exempt_employer_categories'] ?? []
    );

    $reason = null;

    // Check configured employer categories.
    if (
        $organisationType !== '' &&
        in_array($organisationType, $exemptCategories, true)
    ) {
        $reason = 'Eligible employer category: '
            . str_replace('_', ' ', $organisationType);

    // Check configured annual turnover threshold.
    } elseif (
        $turnover !== null &&
        $turnoverThreshold !== null &&
        $turnover < $turnoverThreshold
    ) {
        $reason = 'Annual turnover below K'
            . number_format($turnoverThreshold, 2);

    // Check manually verified exemptions.
    } elseif ($company->is_sdl_exempt) {
        $reason = $company->sdl_exemption_reason
            ?: 'Verified statutory exemption';
    }

    return [
        'is_exempt' => $reason !== null,
        'reason' => $reason,
        'organisation_type' => $organisationType ?: null,
        'annual_turnover' => $turnover,
        'turnover_exemption_threshold' => $turnoverThreshold,
        'exemption_reference' => $company->sdl_exemption_reference,
    ];
}

    /**
     * Store the applied statutory rule details.
     */
    private function ruleDetails(?StatutoryRule $rule): ?array
    {
        if (!$rule) {
            return null;
        }

        return [
            'code' => $rule->code,
            'name' => $rule->name,
            'calculation_method' => $rule->calculation_method,
            'earnings_basis' => $rule->earnings_basis,
            'rate' => $rule->rate !== null
                ? (float) $rule->rate
                : null,
            'maximum_earnings' => $rule->maximum_earnings !== null
                ? (float) $rule->maximum_earnings
                : null,
            'settings' => $rule->settings,
        ];
    }
}
