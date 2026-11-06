<?php

namespace Database\Seeders;

use App\Models\StatutoryRule;
use App\Models\StatutoryRuleSet;
use App\Models\StatutoryTaxBand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Zambia2026StatutoryRulesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // 1. Create or update the Zambia 2026 rule set.
            $ruleSet = StatutoryRuleSet::updateOrCreate(
                [
                    'country_code' => 'ZM',
                    'tax_year' => 2026,
                    'version' => 1,
                ],
                [
                    'country_name' => 'Zambia',
                    'name' => 'Zambia Statutory Rules 2026',
                    'effective_from' => '2026-01-01',
                    'effective_to' => null,
                    'status' => StatutoryRuleSet::STATUS_ACTIVE,
                    'activated_at' => now(),
                    'notes' => 'Initial Zambia statutory configuration for the 2026 tax year.',
                ]
            );

            // 2. PAYE
            $paye = $this->saveRule($ruleSet, [
                'code' => 'PAYE',
                'name' => 'Pay As You Earn',
                'description' => 'Progressive employee income tax.',
                'rule_type' => 'tax',
                'applies_to' => 'employee',
                'calculation_method' => 'progressive',
                'earnings_basis' => 'taxable_emoluments',
                'settings' => [
                    'period' => 'monthly',
                    'rate_unit' => 'percentage',
                    'calculation_method' => 'marginal',
                ],
                'sort_order' => 1,
            ]);

            // PAYE monthly tax bands.
            $bands = [
                [
                    'name' => 'Tax-free band',
                    'lower_limit' => 0,
                    'upper_limit' => 5100,
                    'tax_rate' => 0,
                    'base_tax' => 0,
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Second band',
                    'lower_limit' => 5100,
                    'upper_limit' => 7100,
                    'tax_rate' => 20,
                    'base_tax' => 0,
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Third band',
                    'lower_limit' => 7100,
                    'upper_limit' => 9200,
                    'tax_rate' => 30,
                    'base_tax' => 400,
                    'sort_order' => 3,
                ],
                [
                    'name' => 'Top band',
                    'lower_limit' => 9200,
                    'upper_limit' => null,
                    'tax_rate' => 37,
                    'base_tax' => 1030,
                    'sort_order' => 4,
                ],
            ];

            foreach ($bands as $band) {
                StatutoryTaxBand::updateOrCreate(
                    [
                        'statutory_rule_id' => $paye->id,
                        'sort_order' => $band['sort_order'],
                    ],
                    $band
                );
            }

            // 3. NAPSA - Employee contribution.
            $this->saveRule($ruleSet, [
                'code' => 'NAPSA_EMPLOYEE',
                'name' => 'Employee NAPSA Contribution',
                'description' => 'Employee pension contribution.',
                'rule_type' => 'pension',
                'applies_to' => 'employee',
                'calculation_method' => 'percentage',
                'earnings_basis' => 'pensionable_earnings',
                'rate' => 5,
                'maximum_earnings' => 37236,
                'settings' => [
                    'period' => 'monthly',
                    'rate_unit' => 'percentage',
                    'earnings_ceiling' => 37236,
                    'maximum_contribution' => 1861.80,
                ],
                'sort_order' => 2,
            ]);

            // 4. NAPSA - Employer contribution.
            $this->saveRule($ruleSet, [
                'code' => 'NAPSA_EMPLOYER',
                'name' => 'Employer NAPSA Contribution',
                'description' => 'Employer pension contribution.',
                'rule_type' => 'pension',
                'applies_to' => 'employer',
                'calculation_method' => 'percentage',
                'earnings_basis' => 'pensionable_earnings',
                'rate' => 5,
                'maximum_earnings' => 37236,
                'settings' => [
                    'period' => 'monthly',
                    'rate_unit' => 'percentage',
                    'earnings_ceiling' => 37236,
                    'maximum_contribution' => 1861.80,
                ],
                'sort_order' => 3,
            ]);

            // 5. NHIMA - Employee contribution.
            $this->saveRule($ruleSet, [
                'code' => 'NHIMA_EMPLOYEE',
                'name' => 'Employee NHIMA Contribution',
                'description' => 'Employee National Health Insurance contribution.',
                'rule_type' => 'health',
                'applies_to' => 'employee',
                'calculation_method' => 'percentage',
                'earnings_basis' => 'nhima_employee_income',
                'rate' => 1,
                'settings' => [
                    'period' => 'monthly',
                    'rate_unit' => 'percentage',
                ],
                'sort_order' => 4,
            ]);

            // 6. NHIMA - Employer contribution.
            $this->saveRule($ruleSet, [
                'code' => 'NHIMA_EMPLOYER',
                'name' => 'Employer NHIMA Contribution',
                'description' => 'Employer National Health Insurance contribution.',
                'rule_type' => 'health',
                'applies_to' => 'employer',
                'calculation_method' => 'percentage',
                'earnings_basis' => 'basic_salary',
                'rate' => 1,
                'settings' => [
                    'period' => 'monthly',
                    'rate_unit' => 'percentage',
                ],
                'sort_order' => 5,
            ]);

            // 7. Skills Development Levy.
            $this->saveRule($ruleSet, [
                'code' => 'SDL',
                'name' => 'Skills Development Levy',
                'description' => 'Employer Skills Development Levy.',
                'rule_type' => 'levy',
                'applies_to' => 'employer',
                'calculation_method' => 'percentage',
                'earnings_basis' => 'sdl_chargeable_emoluments',
                'rate' => 0.5,
                'settings' => [
                    'period' => 'monthly',
                    'rate_unit' => 'percentage',
                    'annual_turnover_exemption_threshold' => 800000,
                   'exempt_employer_categories' => [
                        'public_service',
                        'local_authority',
                        'low_annual_turnover',
                        'approved_public_benefit_organisation',
                        'income_tax_exempt_organisation',
                        'approved_donor',
                        'diplomatic_immunities',
                    ],
                ],
                'sort_order' => 6,
            ]);
        });
    }

    private function saveRule(
        StatutoryRuleSet $ruleSet,
        array $data
    ): StatutoryRule {
        $data['statutory_rule_set_id'] = $ruleSet->id;

        return StatutoryRule::updateOrCreate(
            [
                'statutory_rule_set_id' => $ruleSet->id,
                'code' => $data['code'],
            ],
            $data
        );
    }
}
