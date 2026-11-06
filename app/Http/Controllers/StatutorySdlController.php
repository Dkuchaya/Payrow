<?php

namespace App\Http\Controllers;

use App\Models\StatutoryRule;
use App\Models\StatutoryRuleAudit;
use App\Models\StatutoryRuleSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StatutorySdlController extends Controller
{
    private const EXEMPT_CATEGORIES = [
        'public_service',
        'local_authority',
        'approved_public_benefit_organisation',
        'income_tax_exempt_organisation',
        'approved_donor',
    ];

    public function index(StatutoryRuleSet $statutoryRuleSet)
    {
        $rule = $statutoryRuleSet->rules()
            ->where('code', 'SDL')
            ->first();

        return Inertia::render('Statutory/Sdl/Index', [
            'ruleSet' => $statutoryRuleSet,
            'rule' => $rule,
            'exemptCategories' => self::EXEMPT_CATEGORIES,
        ]);
    }

    public function save(
        Request $request,
        StatutoryRuleSet $statutoryRuleSet
    ) {
        $this->ensureDraft($statutoryRuleSet);

        $validated = $request->validate([
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'annual_turnover_exemption_threshold' => [
                'required',
                'numeric',
                'min:0',
            ],
            'exempt_employer_categories' => ['required', 'array'],
            'exempt_employer_categories.*' => [
                'required',
                'string',
                'in:' . implode(',', self::EXEMPT_CATEGORIES),
            ],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use (
            $statutoryRuleSet,
            $validated
        ) {
            $rule = $statutoryRuleSet->rules()
                ->where('code', 'SDL')
                ->first();

            $oldValues = $rule?->only([
                'rate',
                'earnings_basis',
                'settings',
                'is_active',
                'notes',
            ]);

            $settings = array_merge($rule?->settings ?? [], [
                'period' => 'monthly',
                'rate_unit' => 'percentage',
                'annual_turnover_exemption_threshold' =>
                    (float) $validated['annual_turnover_exemption_threshold'],
                'exempt_employer_categories' =>
                    $validated['exempt_employer_categories'],
            ]);

            if (!$rule) {
                $rule = new StatutoryRule();
                $rule->statutory_rule_set_id = $statutoryRuleSet->id;
                $rule->code = 'SDL';
                $rule->name = 'Skills Development Levy';
                $rule->rule_type = StatutoryRule::TYPE_LEVY;
                $rule->applies_to = 'employer';
                $rule->calculation_method =
                    StatutoryRule::METHOD_PERCENTAGE;
            }

            $rule->rate = $validated['rate'];
            $rule->earnings_basis = 'sdl_chargeable_emoluments';
            $rule->settings = $settings;
            $rule->is_active = $validated['is_active'];
            $rule->notes = $validated['notes'] ?? null;
            $rule->save();

            $this->audit(
                $statutoryRuleSet,
                $rule,
                $oldValues,
                $rule->only([
                    'rate',
                    'earnings_basis',
                    'settings',
                    'is_active',
                    'notes',
                ])
            );

            return back()->with(
                'success',
                'SDL settings saved successfully.'
            );
        });
    }

    private function ensureDraft(StatutoryRuleSet $ruleSet): void
    {
        if ($ruleSet->status !== StatutoryRuleSet::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'rule_set' => 'Only draft statutory rule sets can be edited.',
            ]);
        }
    }

    private function audit(
        StatutoryRuleSet $ruleSet,
        StatutoryRule $rule,
        ?array $oldValues,
        array $newValues
    ): void {
        StatutoryRuleAudit::create([
            'statutory_rule_set_id' => $ruleSet->id,
            'user_id' => Auth::id(),
            'auditable_type' => StatutoryRule::class,
            'auditable_id' => $rule->id,
            'action' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => 'Updated Skills Development Levy settings.',
        ]);
    }
}
