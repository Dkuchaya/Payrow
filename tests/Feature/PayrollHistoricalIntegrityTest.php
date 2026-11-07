<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\User;
use App\Models\StatutoryRule;
use App\Models\StatutoryRuleSet;
use App\Services\PayrollRunService;
use App\Models\StatutoryTaxBand;
use App\Services\PayrollCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PayrollHistoricalIntegrityTest extends TestCase
{
    use RefreshDatabase;


    public function test_calculated_payroll_in_open_period_can_be_recalculated(): void
{
    [$run, $company] = $this->createPayroll();

    $run->update([
        'status' => 'calculated',
    ]);

    $result = app(PayrollCalculationService::class)
        ->calculateRun($run, $company);

    $this->assertEquals('calculated', $result->status);
}

    public function test_approved_payroll_cannot_be_recalculated(): void
    {
        [$run, $company] = $this->createPayroll();

        $user = User::factory()->create();

        $run->update([
            'status' => 'calculated',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        app(PayrollCalculationService::class)
            ->calculateRun($run, $company);
    }

    public function test_paid_payroll_cannot_be_recalculated(): void
    {
        [$run, $company] = $this->createPayroll();

        $run->update([
            'status' => 'calculated',
            'paid_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        app(PayrollCalculationService::class)
            ->calculateRun($run, $company);
    }

    public function test_payroll_in_processed_period_cannot_be_recalculated(): void
    {
        [$run, $company, $period] = $this->createPayroll();

        $period->update([
            'status' => 'processed',
        ]);

        $run->update([
            'status' => 'calculated',
        ]);

        $this->expectException(ValidationException::class);

        app(PayrollCalculationService::class)
            ->calculateRun($run, $company);
    }

    public function test_employees_cannot_be_added_when_payroll_period_is_processed(): void
{
    [$run, $company, $period] = $this->createPayroll();

    $run->update(['status' => 'draft']);
    $period->update(['status' => 'processed']);

    $this->expectException(ValidationException::class);

    app(PayrollRunService::class)->addEmployees($run, $company);
}

public function test_employees_cannot_be_removed_when_payroll_period_is_processed(): void
{
    [$run, $company, $period] = $this->createPayroll();

    $run->update(['status' => 'draft']);
    $period->update(['status' => 'processed']);

    $item = $run->items()->firstOrFail();

    $this->expectException(ValidationException::class);

    app(PayrollRunService::class)->removeEmployee(
        $run,
        $item,
        $company
    );
}

public function test_employee_can_be_removed_when_payroll_period_is_open(): void
{
    [$run, $company, $period] = $this->createPayroll();

    $run->update(['status' => 'draft']);

    $item = $run->items()->firstOrFail();

    app(PayrollRunService::class)->removeEmployee(
        $run,
        $item,
        $company
    );

    $this->assertDatabaseMissing('payroll_run_items', [
        'id' => $item->id,
    ]);

    $this->assertDatabaseHas('payroll_runs', [
        'id' => $run->id,
        'employee_count' => 0,
    ]);
}

public function test_draft_payroll_run_can_be_deleted(): void
{
    [$run] = $this->createPayroll();

    $run->update(['status' => 'draft']);

    $run->delete();

    $this->assertDatabaseMissing('payroll_runs', [
        'id' => $run->id,
    ]);
}

public function test_approved_payroll_run_cannot_be_deleted(): void
{
    [$run] = $this->createPayroll();

    $run->update(['status' => 'approved']);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(
        'Approved or paid payroll runs cannot be deleted.'
    );

    $run->delete();
}

public function test_paid_payroll_run_cannot_be_deleted(): void
{
    [$run] = $this->createPayroll();

    $run->update([
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(
        'Approved or paid payroll runs cannot be deleted.'
    );

    $run->delete();
}

public function test_calculated_payroll_run_cannot_be_deleted(): void
{
    [$run] = $this->createPayroll();

    $run->update(['status' => 'calculated']);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(
        'Approved or paid payroll runs cannot be deleted.'
    );

    $run->delete();
}

public function test_draft_payroll_run_cannot_be_deleted_when_period_is_processed(): void
{
    [$run, $company, $period] = $this->createPayroll();

    $run->update(['status' => 'draft']);
    $period->update(['status' => 'processed']);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(
        'Approved or paid payroll runs cannot be deleted.'
    );

    $run->delete();
}


public function test_approved_payroll_item_cannot_be_modified(): void
{
    [$run] = $this->createPayroll();

    $run->update([
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    $item = $run->items()->firstOrFail();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(
        'Employee payroll records cannot be modified after payroll finalization.'
    );

    $item->update(['basic_salary' => 12000]);
}

public function test_paid_payroll_item_cannot_be_modified(): void
{
    [$run] = $this->createPayroll();

    $run->update([
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $item = $run->items()->firstOrFail();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(
        'Employee payroll records cannot be modified after payroll finalization.'
    );

    $item->update(['basic_salary' => 12000]);
}

public function test_calculated_payroll_item_cannot_be_deleted(): void
{
    [$run] = $this->createPayroll();

    $run->update(['status' => 'calculated']);

    $item = $run->items()->firstOrFail();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(
        'Employee payroll records can only be deleted from draft payroll in an open period.'
    );

    $item->delete();
}

public function test_approved_component_snapshot_cannot_be_modified(): void
{
    [$run] = $this->createPayroll();

    $run->update([
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    $component = $this->createPayrollComponent($run);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(
        'Payroll component snapshots cannot be modified after payroll finalization.'
    );

    $component->update(['calculated_amount' => 500]);
}

public function test_paid_component_snapshot_cannot_be_modified(): void
{
    [$run] = $this->createPayroll();

    $run->update([
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $component = $this->createPayrollComponent($run);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(
        'Payroll component snapshots cannot be modified after payroll finalization.'
    );

    $component->update(['calculated_amount' => 500]);
}

public function test_calculated_component_snapshot_cannot_be_deleted(): void
{
    [$run] = $this->createPayroll();

    $run->update(['status' => 'calculated']);

    $component = $this->createPayrollComponent($run);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(
        'Payroll component snapshots can only be deleted from draft payroll in an open period.'
    );

    $component->delete();
}

public function test_draft_component_snapshot_can_be_modified_and_deleted(): void
{
    [$run] = $this->createPayroll();

    $run->update(['status' => 'draft']);

    $component = $this->createPayrollComponent($run);

    $component->update(['calculated_amount' => 500]);

    $this->assertDatabaseHas('payroll_run_item_components', [
        'id' => $component->id,
        'calculated_amount' => 500,
    ]);

    $component->delete();

    $this->assertDatabaseMissing('payroll_run_item_components', [
        'id' => $component->id,
    ]);
}


private function createPayrollComponent(
    PayrollRun $run
): \App\Models\PayrollRunItemComponent {
    $item = $run->items()->firstOrFail();

    return $item->components()->create([
        'name' => 'Transport Allowance',
        'type' => 'allowance',
        'calculation_type' => 'fixed',
        'amount' => 500,
        'percentage' => 0,
        'calculated_amount' => 0,
        'is_taxable' => true,
        'is_paye_taxable' => true,
        'is_napsa_pensionable' => false,
        'is_nhima_liable' => false,
        'is_sdl_liable' => true,
    ]);
}


    private function createPayroll(): array
    {
        $user = User::factory()->create();

        $company = Company::factory()->create();
        $this->createActiveRuleSet($user);

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

        $run = PayrollRun::create([
            'company_id' => $company->id,
            'payroll_period_id' => $period->id,
            'run_number' => 'TEST-2027-001',
            'status' => 'calculated',
            'created_by' => $user->id,
        ]);

        PayrollRunItem::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'basic_salary' => 10000,
            'status' => 'calculated',
        ]);

        return [$run, $company, $period];
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
