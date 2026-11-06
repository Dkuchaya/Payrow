<?php

namespace App\Http\Controllers;

use App\Models\StatutoryRule;
use App\Models\StatutoryRuleAudit;
use App\Models\StatutoryRuleSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatutoryNapsaController extends Controller
{
    /**
     * Display NAPSA configuration.
     */
    public function index(StatutoryRuleSet $statutoryRuleSet)
    {
        $rules = $statutoryRuleSet->rules()
            ->whereIn('code', [
                'NAPSA_EMPLOYEE',
                'NAPSA_EMPLOYER',
            ])
            ->get()
            ->keyBy('code');

        return inertia('Statutory/Napsa/Index', [
            'ruleSet' => $statutoryRuleSet,
            'employeeRule' => $rules->get('NAPSA_EMPLOYEE'),
            'employerRule' => $rules->get('NAPSA_EMPLOYER'),
        ]);
    }

    /**
     * Save employee and employer NAPSA settings.
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

            'maximum_earnings' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'earnings_basis' => [
                'required',
                'string',
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

        $maximumContribution = round(
            ((float) $validated['maximum_earnings'])
            * ((float) $validated['employee_rate'] / 100),
            2
        );

        DB::transaction(function () use (
            $statutoryRuleSet,
            $validated,
            $maximumContribution
        ) {
            $this->saveRule(
                $statutoryRuleSet,
                'NAPSA_EMPLOYEE',
                'Employee NAPSA Contribution',
                $validated['employee_rate'],
                $validated['maximum_earnings'],
                $validated['earnings_basis'],
                $validated['period'],
                $maximumContribution,
                $validated['employee_is_active'],
                $validated['notes']
            );

            $employerMaximumContribution = round(
                ((float) $validated['maximum_earnings'])
                * ((float) $validated['employer_rate'] / 100),
                2
            );

            $this->saveRule(
                $statutoryRuleSet,
                'NAPSA_EMPLOYER',
                'Employer NAPSA Contribution',
                $validated['employer_rate'],
                $validated['maximum_earnings'],
                $validated['earnings_basis'],
                $validated['period'],
                $employerMaximumContribution,
                $validated['employer_is_active'],
                $validated['notes']
            );
        });

        return back()->with(
            'success',
            'NAPSA configuration saved successfully.'
        );
    }

    /**
     * Create or update an individual NAPSA rule.
     */
    private function saveRule(
        StatutoryRuleSet $ruleSet,
        string $code,
        string $name,
        float $rate,
        float $maximumEarnings,
        string $earningsBasis,
        string $period,
        float $maximumContribution,
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
                'maximum_earnings',
                'earnings_basis',
                'settings',
                'is_active',
                'notes',
            ])
            : null;

        $rule->fill([
            'name' => $name,
            'rule_type' => StatutoryRule::TYPE_PENSION,
            'applies_to' => $code === 'NAPSA_EMPLOYEE'
                ? 'employee'
                : 'employer',
            'calculation_method' => StatutoryRule::METHOD_PERCENTAGE,
            'earnings_basis' => $earningsBasis,
            'rate' => $rate,
            'maximum_earnings' => $maximumEarnings,
            'settings' => [
                'period' => $period,
                'rate_unit' => 'percentage',
                'earnings_ceiling' => $maximumEarnings,
                'maximum_contribution' => $maximumContribution,
            ],
            'is_active' => $isActive,
            'notes' => $notes,
            'sort_order' => $code === 'NAPSA_EMPLOYEE' ? 2 : 3,
        ]);

        $rule->save();

        $this->audit(
            $ruleSet,
            $rule,
            $oldValues,
            $rule->only([
                'name',
                'rate',
                'maximum_earnings',
                'earnings_basis',
                'settings',
                'is_active',
                'notes',
            ]),
            $oldValues === null
                ? 'NAPSA rule created.'
                : 'NAPSA rule updated.'
        );
    }

    /**
     * Prevent changes to active or archived rule sets.
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
