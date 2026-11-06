<?php

namespace App\Services;

use App\Models\StatutoryRuleSet;

class StatutoryRuleSetValidator
{
    private const SDL_EXEMPT_CATEGORIES = [
        'public_service',
        'local_authority',
        'approved_public_benefit_organisation',
        'income_tax_exempt_organisation',
        'approved_donor',
    ];

    /**
     * Validate a statutory rule set before activation.
     */
    public function validate(StatutoryRuleSet $ruleSet): array
    {
        $ruleSet->loadMissing('rules.taxBands');

        $errors = [];

        $rules = $ruleSet->rules->keyBy('code');

        $this->validatePaye($rules->get('PAYE'), $errors);

        $this->validateNapsa($rules, $errors);

        $this->validateNhima($rules, $errors);

        $this->validateSdl($rules->get('SDL'), $errors);

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    private function validatePaye($rule, array &$errors): void
    {
        if (!$rule || !$rule->is_active) {
            $errors[] = 'PAYE must exist and be enabled.';
            return;
        }

        $bands = $rule->taxBands
            ->sortBy('sort_order')
            ->values();

        if ($bands->isEmpty()) {
            $errors[] = 'PAYE must have at least one tax band.';
            return;
        }

        if ((float) $bands->first()->lower_limit !== 0.0) {
            $errors[] = 'The first PAYE tax band must start at zero.';
        }

        foreach ($bands as $index => $band) {
            if (
                !is_numeric($band->lower_limit) ||
                (float) $band->lower_limit < 0
            ) {
                $errors[] = 'PAYE tax band ' . ($index + 1) .
                    ' has an invalid lower limit.';
                continue;
            }

            if (
                !is_numeric($band->tax_rate) ||
                (float) $band->tax_rate < 0 ||
                (float) $band->tax_rate > 100
            ) {
                $errors[] = 'PAYE tax band ' . ($index + 1) .
                    ' has an invalid tax rate.';
            }

            if (
                !is_numeric($band->base_tax) ||
                (float) $band->base_tax < 0
            ) {
                $errors[] = 'PAYE tax band ' . ($index + 1) .
                    ' has an invalid base tax.';
            }

            if ($index < $bands->count() - 1) {
                $nextBand = $bands[$index + 1];

                if ($band->upper_limit === null) {
                    $errors[] = 'Only the final PAYE tax band may have no upper limit.';
                } elseif (
                    abs(
                        (float) $band->upper_limit -
                        (float) $nextBand->lower_limit
                    ) > 0.01
                ) {
                    $errors[] = 'PAYE tax bands must be continuous without gaps or overlaps.';
                }
            } else {
                if ($band->upper_limit !== null) {
                    $errors[] = 'The final PAYE tax band must have an open-ended upper limit.';
                }
            }

            if (
                $band->upper_limit !== null &&
                (float) $band->upper_limit <= (float) $band->lower_limit
            ) {
                $errors[] = 'PAYE tax band ' . ($index + 1) .
                    ' must have an upper limit greater than its lower limit.';
            }
        }
    }

    private function validateNapsa($rules, array &$errors): void
    {
        foreach (['NAPSA_EMPLOYEE', 'NAPSA_EMPLOYER'] as $code) {
            $rule = $rules->get($code);

            if (!$rule || !$rule->is_active) {
                $errors[] = "{$code} must exist and be enabled.";
                continue;
            }

            if (
                !is_numeric($rule->rate) ||
                (float) $rule->rate < 0 ||
                (float) $rule->rate > 100
            ) {
                $errors[] = "{$code} has an invalid contribution rate.";
            }

            if (
                !is_numeric($rule->maximum_earnings) ||
                (float) $rule->maximum_earnings <= 0
            ) {
                $errors[] = "{$code} must have a positive maximum earnings ceiling.";
            }

            $settings = $rule->settings ?? [];

            if (
                !isset($settings['maximum_contribution']) ||
                !is_numeric($settings['maximum_contribution']) ||
                (float) $settings['maximum_contribution'] < 0
            ) {
                $errors[] = "{$code} has an invalid maximum contribution.";
            } elseif (
                is_numeric($rule->rate) &&
                is_numeric($rule->maximum_earnings)
            ) {
                $expected = round(
                    (float) $rule->maximum_earnings *
                    (float) $rule->rate / 100,
                    2
                );

                if (
                    abs(
                        $expected -
                        (float) $settings['maximum_contribution']
                    ) > 0.01
                ) {
                    $errors[] = "{$code} maximum contribution does not match its rate and ceiling.";
                }
            }
        }
    }

    private function validateNhima($rules, array &$errors): void
    {
        $expectedBases = [
            'NHIMA_EMPLOYEE' => 'nhima_employee_income',
            'NHIMA_EMPLOYER' => 'basic_salary',
        ];

        foreach ($expectedBases as $code => $expectedBasis) {
            $rule = $rules->get($code);

            if (!$rule || !$rule->is_active) {
                $errors[] = "{$code} must exist and be enabled.";
                continue;
            }

            if (
                !is_numeric($rule->rate) ||
                (float) $rule->rate < 0 ||
                (float) $rule->rate > 100
            ) {
                $errors[] = "{$code} has an invalid contribution rate.";
            }

            if ($rule->earnings_basis !== $expectedBasis) {
                $errors[] = "{$code} has an unsupported earnings basis.";
            }
        }
    }

    private function validateSdl($rule, array &$errors): void
    {
        if (!$rule || !$rule->is_active) {
            $errors[] = 'SDL must exist and be enabled.';
            return;
        }

        if (
            !is_numeric($rule->rate) ||
            (float) $rule->rate < 0 ||
            (float) $rule->rate > 100
        ) {
            $errors[] = 'SDL has an invalid levy rate.';
        }

        if ($rule->earnings_basis !== 'sdl_chargeable_emoluments') {
            $errors[] = 'SDL has an unsupported earnings basis.';
        }

        $settings = $rule->settings ?? [];

        if (
            !isset($settings['annual_turnover_exemption_threshold']) ||
            !is_numeric($settings['annual_turnover_exemption_threshold']) ||
            (float) $settings['annual_turnover_exemption_threshold'] < 0
        ) {
            $errors[] = 'SDL annual turnover exemption threshold is invalid.';
        }

        $categories = $settings['exempt_employer_categories'] ?? [];

        if (!is_array($categories) || empty($categories)) {
            $errors[] = 'SDL must have at least one exempt employer category.';
        } elseif (array_diff($categories, self::SDL_EXEMPT_CATEGORIES)) {
            $errors[] = 'SDL contains an unsupported exempt employer category.';
        }
    }
}
