<?php

namespace Tests\Feature;

use App\Models\StatutoryRule;
use App\Models\StatutoryRuleAudit;
use App\Models\StatutoryRuleSet;
use App\Models\StatutoryTaxBand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatutoryRuleSetActivationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_super_admin' => true,
        ]);
    }

   private function createValidRuleSet(
    string $status = 'draft',
    string $effectiveFrom = '2027-01-01',
    int $version = 1
): StatutoryRuleSet {
        $ruleSet = StatutoryRuleSet::create([
            'country_code' => 'ZM',
            'country_name' => 'Zambia',
            'name' => 'Zambia Statutory Rules 2027',
            'tax_year' => 2027,
            'version' => $version,
            'effective_from' => $effectiveFrom,
            'effective_to' => null,
            'status' => $status,
            'created_by' => $this->admin->id,
        ]);

        $this->createRule($ruleSet, [
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

        $paye = $ruleSet->rules()->where('code', 'PAYE')->firstOrFail();

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

    public function test_valid_draft_can_be_activated_and_previous_rule_set_closed(): void
    {
        $previous = $this->createValidRuleSet(
            'active',
            '2026-01-01'
        );

        $previous->update([
            'name' => 'Zambia Statutory Rules 2026',
            'tax_year' => 2026,
        ]);

        $draft = $this->createValidRuleSet();

        $this->actingAs($this->admin)
            ->post(route('statutory-rule-sets.activate', $draft))
            ->assertRedirect(route('statutory-rule-sets.show', $draft));

        $this->assertDatabaseHas('statutory_rule_sets', [
            'id' => $previous->id,
            'status' => 'active',
            'effective_to' => '2026-12-31',
        ]);

        $this->assertDatabaseHas('statutory_rule_sets', [
            'id' => $draft->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('statutory_rule_audits', [
            'statutory_rule_set_id' => $previous->id,
            'action' => 'statutory_rule_set_period_adjusted',
        ]);

        $this->assertDatabaseHas('statutory_rule_audits', [
            'statutory_rule_set_id' => $draft->id,
            'action' => 'statutory_rule_set_activated',
        ]);
    }

    public function test_incomplete_rule_set_cannot_be_activated(): void
    {
        $draft = StatutoryRuleSet::create([
            'country_code' => 'ZM',
            'country_name' => 'Zambia',
            'name' => 'Incomplete 2027 Rules',
            'tax_year' => 2027,
            'version' => 1,
            'effective_from' => '2027-01-01',
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('statutory-rule-sets.activate', $draft))
            ->assertSessionHasErrors('activation');

        $this->assertDatabaseHas('statutory_rule_sets', [
            'id' => $draft->id,
            'status' => 'draft',
        ]);
    }

    public function test_already_active_rule_set_cannot_be_activated_again(): void
    {
        $active = $this->createValidRuleSet('active');

        $this->actingAs($this->admin)
            ->post(route('statutory-rule-sets.activate', $active))
            ->assertSessionHasErrors('activation');

        $this->assertDatabaseHas('statutory_rule_sets', [
            'id' => $active->id,
            'status' => 'active',
        ]);
    }

    public function test_overlapping_active_rule_set_is_rejected(): void
    {
        $previous = $this->createValidRuleSet(
            'active',
            '2027-01-01'
        );

        $draft = $this->createValidRuleSet(
            'draft',
            '2027-01-01',
            2
        );

        $this->actingAs($this->admin)
            ->post(route('statutory-rule-sets.activate', $draft))
            ->assertSessionHasErrors('activation');

        $this->assertDatabaseHas('statutory_rule_sets', [
            'id' => $previous->id,
            'status' => 'active',
            'effective_to' => null,
        ]);

        $this->assertDatabaseHas('statutory_rule_sets', [
            'id' => $draft->id,
            'status' => 'draft',
        ]);
    }
}
