<?php

namespace App\Http\Controllers;

use App\Models\StatutoryRule;
use App\Models\StatutoryRuleAudit;
use App\Models\StatutoryRuleSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatutoryNhimaController extends Controller
{
    /**
     * Display NHIMA configuration.
     */
    public function index(StatutoryRuleSet $statutoryRuleSet)
    {
        $rules = $statutoryRuleSet->rules()
            ->whereIn('code', [
                'NHIMA_EMPLOYEE',
                'NHIMA_EMPLOYER',
            ])
            ->get()
            ->keyBy('code');

        return inertia('Statutory/Nhima/Index', [
            'ruleSet' => $statutoryRuleSet,
            'employeeRule' => $rules->get('NHIMA_EMPLOYEE'),
            'employerRule' => $rules->get('NHIMA_EMPLOYER'),
        ]);
    }

    /**
     * Save employee and employer NHIMA settings.
     */
    public function save(
        Request $request,
        StatutoryRuleSet $statutoryRuleSet
    ) {
        $this->ensureDraft($statutoryRuleSet);

        $validated = $request->validate([
            'employee_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'employer_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'period' => [
                'required',
                'in:monthly',
            ],

            'employee_is_active' => [
                'required',
                'boolean',
            ],

            'employer_is_active' => [
                'required',
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        DB::transaction(function () use (
            $statutoryRuleSet,
            $validated
        ) {
            $this->saveRule(
                $statutoryRuleSet,
                'NHIMA_EMPLOYEE',
                'Employee NHIMA Contribution',
                $validated['employee_rate'],
                'employee',
                'nhima_employee_income',
                $validated['period'],
                $validated['employee_is_active'],
                $validated['notes']
            );

            $this->saveRule(
                $statutoryRuleSet,
                'NHIMA_EMPLOYER',
                'Employer NHIMA Contribution',
                $validated['employer_rate'],
                'employer',
                'basic_salary',
                $validated['period'],
                $validated['employer_is_active'],
                $validated['notes']
            );
        });

        return back()->with(
            'success',
            'NHIMA configuration saved successfully.'
        );
    }

    /**
     * Create or update an individual NHIMA rule.
     */
    private function saveRule(
        StatutoryRuleSet $ruleSet,
        string $code,
        string $name,
        float $rate,
        string $appliesTo,
        string $earningsBasis,
        string $period,
        bool $isActive,
        ?string $notes
    ): void {
        $rule = $ruleSet->rules()->firstOrNew([
            'code' => $code,
        ]);

        $oldValues = $rule->exists
            ? $rule->only([
                'name',
                'rate',
                'earnings_basis',
                'settings',
                'is_active',
                'notes',
            ])
            : null;

        $rule->fill([
            'name' => $name,
            'rule_type' => StatutoryRule::TYPE_HEALTH,
            'applies_to' => $appliesTo,
            'calculation_method' => StatutoryRule::METHOD_PERCENTAGE,
            'earnings_basis' => $earningsBasis,
            'rate' => $rate,
            'settings' => [
                'period' => $period,
                'rate_unit' => 'percentage',
            ],
            'is_active' => $isActive,
            'notes' => $notes,
            'sort_order' => $code === 'NHIMA_EMPLOYEE' ? 4 : 5,
        ]);

        $rule->save();

        $this->audit(
            $ruleSet,
            $rule,
            $oldValues,
            $rule->only([
                'name',
                'rate',
                'earnings_basis',
                'settings',
                'is_active',
                'notes',
            ]),
            $oldValues === null
                ? 'NHIMA rule created.'
                : 'NHIMA rule updated.'
        );
    }

    /**
     * Prevent modifications to active or archived rule sets.
     */
    private function ensureDraft(StatutoryRuleSet $ruleSet): void
    {
        abort_unless(
            $ruleSet->status === StatutoryRuleSet::STATUS_DRAFT,
            403,
            'Only draft statutory rule sets can be modified.'
        );
    }

    /**
     * Record statutory configuration changes.
     */
    private function audit(
        StatutoryRuleSet $ruleSet,
        StatutoryRule $rule,
        ?array $oldValues,
        array $newValues,
        string $description
    ): void {
        StatutoryRuleAudit::create([
            'statutory_rule_set_id' => $ruleSet->id,
            'user_id' => Auth::id(),
            'auditable_type' => get_class($rule),
            'auditable_id' => $rule->id,
            'action' => $oldValues === null ? 'created' : 'updated',
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
        ]);
    }
}
