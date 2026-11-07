<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\StatutoryRule;
use App\Models\StatutoryRuleSet;
use App\Models\StatutoryTaxBand;
use App\Models\User;
use App\Services\PayrollCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatutoryPayrollSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_calculation_preserves_historical_statutory_snapshot(): void
    {
        $user = User::factory()->create();

        $company = Company::factory()->create();

        $employee = Employee::create([
            'company_id' => $company->id,
            'employee_number' => 'EMP-001',
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'basic_salary' => 10000,
            'employment_status' => 'active',
            'is_active' => true,
        ]);

        $period = PayrollPeriod::create([
            'company_id' => $company->id,
            'name' => 'January 2027',
            'start_date' => '2027-01-01',
            'end_date' => '2027-01-31',
            'pay_date' => '2027-01-31',
            'frequency' => 'monthly',
            'status' => 'open',
        ]);

        $ruleSet = $this->createActiveRuleSet($user);

        $run = PayrollRun::create([
            'company_id' => $company->id,
            'payroll_period_id' => $period->id,
            'run_number' => 'TEST-2027-001',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $item = PayrollRunItem::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'basic_salary' => 10000,
            'total_allowances' => 0,
            'gross_pay' => 0,
            'total_deductions' => 0,
            'net_pay' => 0,
            'status' => 'pending',
        ]);

        app(PayrollCalculationService::class)
            ->calculateRun($run, $company);

        $savedItem = PayrollRunItem::findOrFail($item->id);

        $snapshot = $savedItem->statutory_breakdown;

        // Confirm the statutory snapshot was saved.
        $this->assertIsArray($snapshot);

        // Confirm the historical rule-set identity.
        $this->assertEquals($ruleSet->id, $snapshot['rule_set_id']);
        $this->assertEquals(2027, $snapshot['tax_year']);
        $this->assertEquals(1, $snapshot['version']);

        // Confirm PAYE tax bands were preserved.
        $payeBands = $snapshot['applied_rules']['paye']['tax_bands'];

        $this->assertCount(5, $payeBands);

        $this->assertEquals(0, $payeBands[0]['lower_limit']);
        $this->assertEquals(5100, $payeBands[0]['upper_limit']);
        $this->assertEquals(0, $payeBands[0]['tax_rate']);

        $this->assertEquals(37000, $payeBands[4]['lower_limit']);
        $this->assertNull($payeBands[4]['upper_limit']);
        $this->assertEquals(39.5, $payeBands[4]['tax_rate']);

        // Confirm statutory calculations.
        $this->assertEquals(1326, (float) $savedItem->paye);
        $this->assertEquals(500, (float) $savedItem->employee_napsa);
        $this->assertEquals(100, (float) $savedItem->employee_nhima);

        $this->assertEquals(500, (float) $savedItem->employer_napsa);
        $this->assertEquals(100, (float) $savedItem->employer_nhima);
        $this->assertEquals(50, (float) $savedItem->skills_development_levy);

        $this->assertEquals(1926, (float) $savedItem->employee_statutory_deductions);
        $this->assertEquals(650, (float) $savedItem->employer_statutory_contributions);
        $this->assertEquals(10650, (float) $savedItem->employer_total_cost);

        // Confirm payroll totals.
        $this->assertEquals(10000, (float) $savedItem->gross_pay);
        $this->assertEquals(8074, (float) $savedItem->net_pay);

        $this->assertDatabaseHas('payroll_run_items', [
            'id' => $item->id,
            'status' => 'calculated',
        ]);
    }

    private function createActiveRuleSet(User $user): StatutoryRuleSet
    {
        $ruleSet = StatutoryRuleSet::create([
            'country_code' => 'ZM',
            'country_name' => 'Zambia',
            'name' => 'Zambia Statutory Rules 2027',
            'tax_year' => 2027,
            'version' => 1,
            'effective_from' => '2027-01-01',
            'effective_to' => null,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $paye = $this->createRule($ruleSet, [
            'code' => 'PAYE',
            'name' => 'Pay As You Earn',
            'rule_type' => StatutoryRule::TYPE_TAX,
            'calculation_method' => StatutoryRule::METHOD_PROGRESSIVE,
            'earnings_basis' => 'taxable_emoluments',
            'settings' => [
                'period' => 'monthly',
                'calculation_method' => 'marginal',
            ],
        ]);

        $bands = [
            ['Tax Free', 0, 5100, 0, 0],
            ['Second', 5100, 7100, 20, 0],
            ['Third', 7100, 9200, 30, 400],
            ['Fourth', 9200, 37000, 37, 1030],
            ['Top', 37000, null, 39.5, 11316],
        ];

        foreach ($bands as $index => $band) {
            StatutoryTaxBand::create([
                'statutory_rule_id' => $paye->id,
                'name' => $band[0],
                'lower_limit' => $band[1],
                'upper_limit' => $band[2],
                'tax_rate' => $band[3],
                'base_tax' => $band[4],
                'sort_order' => $index + 1,
            ]);
        }

        foreach ([
            'NAPSA_EMPLOYEE' => 'Employee NAPSA Contribution',
            'NAPSA_EMPLOYER' => 'Employer NAPSA Contribution',
        ] as $code => $name) {
            $this->createRule($ruleSet, [
                'code' => $code,
                'name' => $name,
                'rule_type' => StatutoryRule::TYPE_PENSION,
                'calculation_method' => StatutoryRule::METHOD_PERCENTAGE,
                'earnings_basis' => 'pensionable_earnings',
                'rate' => 5,
                'maximum_earnings' => 37236,
                'settings' => [
                    'period' => 'monthly',
                    'rate_unit' => 'percentage',
                    'maximum_contribution' => 1861.80,
                ],
            ]);
        }

        $this->createRule($ruleSet, [
            'code' => 'NHIMA_EMPLOYEE',
            'name' => 'Employee NHIMA Contribution',
            'rule_type' => StatutoryRule::TYPE_HEALTH,
            'calculation_method' => StatutoryRule::METHOD_PERCENTAGE,
            'earnings_basis' => 'nhima_employee_income',
            'rate' => 1,
            'settings' => ['period' => 'monthly'],
        ]);

        $this->createRule($ruleSet, [
            'code' => 'NHIMA_EMPLOYER',
            'name' => 'Employer NHIMA Contribution',
            'rule_type' => StatutoryRule::TYPE_HEALTH,
            'calculation_method' => StatutoryRule::METHOD_PERCENTAGE,
            'earnings_basis' => 'basic_salary',
            'rate' => 1,
            'settings' => ['period' => 'monthly'],
        ]);

        $this->createRule($ruleSet, [
            'code' => 'SDL',
            'name' => 'Skills Development Levy',
            'rule_type' => StatutoryRule::TYPE_LEVY,
            'calculation_method' => StatutoryRule::METHOD_PERCENTAGE,
            'earnings_basis' => 'sdl_chargeable_emoluments',
            'rate' => 0.5,
            'settings' => [
                'period' => 'monthly',
                'annual_turnover_exemption_threshold' => 800000,
                'exempt_employer_categories' => [
                    'public_service',
                    'local_authority',
                    'approved_public_benefit_organisation',
                    'income_tax_exempt_organisation',
                    'approved_donor',
                ],
            ],
        ]);

        return $ruleSet;
    }

    private function createRule(
        StatutoryRuleSet $ruleSet,
        array $data
    ): StatutoryRule {
        return $ruleSet->rules()->create(array_merge([
            'is_active' => true,
            'sort_order' => 1,
        ], $data));
    }
}
