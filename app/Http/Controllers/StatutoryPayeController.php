<?php

namespace App\Http\Controllers;

use App\Models\StatutoryRule;
use App\Models\StatutoryRuleAudit;
use App\Models\StatutoryRuleSet;
use App\Models\StatutoryTaxBand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StatutoryPayeController extends Controller
{
    /**
     * Display PAYE configuration for a rule set.
     */
    public function index(StatutoryRuleSet $statutoryRuleSet)
    {
        $rule = $statutoryRuleSet->rules()
            ->where('code', 'PAYE')
            ->with('taxBands')
            ->first();

        return Inertia::render('Statutory/Paye/Index', [
            'ruleSet' => $statutoryRuleSet,
            'rule' => $rule,
            'taxBands' => $rule?->taxBands
                ->sortBy('sort_order')
                ->values() ?? collect(),
        ]);
    }

    /**
     * Create or update the PAYE rule.
     */
    public function saveRule(
        Request $request,
        StatutoryRuleSet $statutoryRuleSet
    ) {
        $this->ensureDraft($statutoryRuleSet);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'earnings_basis' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use (
            $statutoryRuleSet,
            $validated
        ) {
            $rule = $statutoryRuleSet->rules()
                ->where('code', 'PAYE')
                ->lockForUpdate()
                ->first();

            $oldValues = $rule?->only([
                'name',
                'description',
                'earnings_basis',
                'notes',
                'is_active',
            ]);

            if (!$rule) {
                $rule = new StatutoryRule([
                    'code' => 'PAYE',
                    'rule_type' => StatutoryRule::TYPE_TAX,
                    'applies_to' => 'employee',
                    'calculation_method' => StatutoryRule::METHOD_PROGRESSIVE,
                    'sort_order' => 1,
                ]);

                $rule->statutory_rule_set_id = $statutoryRuleSet->id;
            }

            $rule->fill($validated);
            $rule->save();

            $this->audit(
                $statutoryRuleSet,
                $rule,
                $oldValues,
                $rule->only([
                    'name',
                    'description',
                    'earnings_basis',
                    'notes',
                    'is_active',
                ]),
                $oldValues ? 'paye_rule_updated' : 'paye_rule_created',
                'PAYE rule configuration saved.'
            );
        });

        return back()->with('success', 'PAYE rule saved successfully.');
    }

    /**
     * Add a tax band.
     */
    public function storeBand(
        Request $request,
        StatutoryRuleSet $statutoryRuleSet
    ) {
        $this->ensureDraft($statutoryRuleSet);

        $rule = $this->getPayeRule($statutoryRuleSet);

        $validated = $this->validateBand($request, $rule);

        DB::transaction(function () use (
            $rule,
            $statutoryRuleSet,
            $validated
        ) {
            $band = $rule->taxBands()->create($validated);

            $this->audit(
                $statutoryRuleSet,
                $band,
                null,
                $band->only([
                    'name',
                    'lower_limit',
                    'upper_limit',
                    'tax_rate',
                    'base_tax',
                    'sort_order',
                ]),
                'paye_tax_band_created',
                'PAYE tax band created.'
            );
        });

        return back()->with('success', 'PAYE tax band added successfully.');
    }

    /**
     * Update an existing tax band.
     */
    public function updateBand(
        Request $request,
        StatutoryRuleSet $statutoryRuleSet,
        StatutoryTaxBand $band
    ) {
        $this->ensureDraft($statutoryRuleSet);

        $rule = $this->getPayeRule($statutoryRuleSet);

        abort_unless(
            $band->statutory_rule_id === $rule->id,
            404
        );

        $validated = $this->validateBand($request, $rule, $band);

        DB::transaction(function () use (
            $band,
            $statutoryRuleSet,
            $validated
        ) {
            $oldValues = $band->only([
                'name',
                'lower_limit',
                'upper_limit',
                'tax_rate',
                'base_tax',
                'sort_order',
            ]);

            $band->update($validated);

            $this->audit(
                $statutoryRuleSet,
                $band,
                $oldValues,
                $band->only(array_keys($oldValues)),
                'paye_tax_band_updated',
                'PAYE tax band updated.'
            );
        });

        return back()->with('success', 'PAYE tax band updated successfully.');
    }

    /**
     * Delete a tax band.
     */
    public function destroyBand(
        StatutoryRuleSet $statutoryRuleSet,
        StatutoryTaxBand $band
    ) {
        $this->ensureDraft($statutoryRuleSet);

        $rule = $this->getPayeRule($statutoryRuleSet);

        abort_unless(
            $band->statutory_rule_id === $rule->id,
            404
        );

        DB::transaction(function () use (
            $band,
            $statutoryRuleSet
        ) {
            $oldValues = $band->only([
                'name',
                'lower_limit',
                'upper_limit',
                'tax_rate',
                'base_tax',
                'sort_order',
            ]);

            $this->audit(
                $statutoryRuleSet,
                $band,
                $oldValues,
                null,
                'paye_tax_band_deleted',
                'PAYE tax band deleted.'
            );

            $band->delete();
        });

        return back()->with('success', 'PAYE tax band deleted successfully.');
    }

    /**
     * Validate tax band input and prevent overlapping ranges.
     */
    
private function validateBand(
    Request $request,
    StatutoryRule $rule,
    ?StatutoryTaxBand $currentBand = null
): array {
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:100'],
        'lower_limit' => ['required', 'numeric', 'min:0'],
        'upper_limit' => ['nullable', 'numeric', 'gt:lower_limit'],
        'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        'base_tax' => ['required', 'numeric', 'min:0'],
        'sort_order' => ['required', 'integer', 'min:1'],
    ]);

    $bands = $rule->taxBands()
        ->when(
            $currentBand,
            fn ($query) => $query->where('id', '!=', $currentBand->id)
        )
        ->get();

    $newLower = (float) $validated['lower_limit'];
    $newUpper = $validated['upper_limit'] !== null
        ? (float) $validated['upper_limit']
        : INF;

    foreach ($bands as $existing) {
        $oldLower = (float) $existing->lower_limit;
        $oldUpper = $existing->upper_limit !== null
            ? (float) $existing->upper_limit
            : INF;

        if ($newLower < $oldUpper && $oldLower < $newUpper) {
            throw ValidationException::withMessages([
                'lower_limit' => 'This income range overlaps an existing tax band.',
            ]);
        }

        if ((int) $existing->sort_order === (int) $validated['sort_order']) {
            throw ValidationException::withMessages([
                'sort_order' => 'This tax band position is already in use.',
            ]);
        }

        if (
            $validated['upper_limit'] === null &&
            $existing->upper_limit === null
        ) {
            throw ValidationException::withMessages([
                'upper_limit' => 'Only one open-ended tax band is allowed.',
            ]);
        }
    }

    return $validated;
}


    /**
     * Get the PAYE rule belonging to this rule set.
     */
    private function getPayeRule(
        StatutoryRuleSet $statutoryRuleSet
    ): StatutoryRule {
        return $statutoryRuleSet->rules()
            ->where('code', 'PAYE')
            ->firstOrFail();
    }

    /**
     * Ensure only draft rule sets can be modified.
     */
    private function ensureDraft(
        StatutoryRuleSet $statutoryRuleSet
    ): void {
        abort_unless(
            $statutoryRuleSet->status === StatutoryRuleSet::STATUS_DRAFT,
            403,
            'Only draft statutory rule sets can be modified.'
        );
    }

    /**
     * Record statutory configuration audit history.
     */
    private function audit(
        StatutoryRuleSet $ruleSet,
        $auditable,
        ?array $oldValues,
        ?array $newValues,
        string $action,
        string $description
    ): void {
        StatutoryRuleAudit::create([
            'statutory_rule_set_id' => $ruleSet->id,
            'user_id' => auth()->id(),
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
        ]);
    }
}
