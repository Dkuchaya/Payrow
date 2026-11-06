<?php

namespace App\Http\Controllers;

use App\Models\StatutoryRuleAudit;
use App\Models\StatutoryRuleSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\StatutoryRuleSetValidator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StatutoryRuleSetController extends Controller
{
    /**
     * Display all statutory rule sets.
     */
    public function index()
    {
        $ruleSets = StatutoryRuleSet::query()
            ->with('creator:id,name')
            ->withCount('rules')
            ->orderByDesc('tax_year')
            ->orderByDesc('version')
            ->paginate(15);

        return Inertia::render('Statutory/RuleSets/Index', [
            'ruleSets' => $ruleSets,
        ]);
    }

    /**
     * Show the create form.
     */
    public function create()
    {
        return Inertia::render('Statutory/RuleSets/Create');
    }

    /**
     * Create a new draft rule set.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'country_code' => ['required', 'string', 'size:2'],
            'country_name' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'tax_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            $version = StatutoryRuleSet::where('country_code', $validated['country_code'])
                ->where('tax_year', $validated['tax_year'])
                ->lockForUpdate()
                ->max('version') ?? 0;

            $ruleSet = StatutoryRuleSet::create([
                ...$validated,
                'version' => $version + 1,
                'status' => StatutoryRuleSet::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            StatutoryRuleAudit::create([
                'statutory_rule_set_id' => $ruleSet->id,
                'user_id' => Auth::id(),
                'auditable_type' => StatutoryRuleSet::class,
                'auditable_id' => $ruleSet->id,
                'action' => 'statutory_rule_set_created',
                'old_values' => null,
                'new_values' => $ruleSet->only([
                    'country_code',
                    'country_name',
                    'name',
                    'tax_year',
                    'version',
                    'effective_from',
                    'effective_to',
                    'status',
                ]),
                'description' => 'New statutory rule set created.',
            ]);
        });

        return redirect()
            ->route('statutory-rule-sets.index')
            ->with('success', 'Statutory rule set created successfully.');
    }

    /**
     * Display a statutory rule set.
     */
   
/**
 * Display a statutory rule set.
 */
public function show(
    StatutoryRuleSet $statutoryRuleSet,
    StatutoryRuleSetValidator $validator
) {
    $statutoryRuleSet->load([
        'creator:id,name',
        'rules.taxBands',
    ]);

    $activationValidation = $validator->validate($statutoryRuleSet);

    return Inertia::render('Statutory/RuleSets/Show', [
        'ruleSet' => $statutoryRuleSet,
        'activationValidation' => $activationValidation,
    ]);
}


    /**
     * Show the edit form.
     */
    public function edit(StatutoryRuleSet $statutoryRuleSet)
    {
        abort_unless(
            $statutoryRuleSet->status === StatutoryRuleSet::STATUS_DRAFT,
            403,
            'Only draft statutory rule sets can be edited.'
        );

        return Inertia::render('Statutory/RuleSets/Edit', [
            'ruleSet' => $statutoryRuleSet,
        ]);
    }

    /**
     * Update a draft statutory rule set.
     */
    public function update(
        Request $request,
        StatutoryRuleSet $statutoryRuleSet
    ) {
        abort_unless(
            $statutoryRuleSet->status === StatutoryRuleSet::STATUS_DRAFT,
            403,
            'Only draft statutory rule sets can be updated.'
        );

        $validated = $request->validate([
            'country_name' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($statutoryRuleSet, $validated) {
            $oldValues = $statutoryRuleSet->only(array_keys($validated));

            $statutoryRuleSet->update($validated);

            $newValues = $statutoryRuleSet->only(array_keys($validated));

            if ($oldValues != $newValues) {
                StatutoryRuleAudit::create([
                    'statutory_rule_set_id' => $statutoryRuleSet->id,
                    'user_id' => Auth::id(),
                    'auditable_type' => StatutoryRuleSet::class,
                    'auditable_id' => $statutoryRuleSet->id,
                    'action' => 'statutory_rule_set_updated',
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                    'description' => 'Draft statutory rule set updated.',
                ]);
            }
        });

        return redirect()
            ->route('statutory-rule-sets.show', $statutoryRuleSet)
            ->with('success', 'Statutory rule set updated successfully.');
    }

    
/**
 * Validate and activate a statutory rule set.
 */
public function activate(
    StatutoryRuleSet $statutoryRuleSet,
    StatutoryRuleSetValidator $validator
) {
    DB::transaction(function () use ($statutoryRuleSet, $validator) {

        $ruleSet = StatutoryRuleSet::whereKey($statutoryRuleSet->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($ruleSet->status !== StatutoryRuleSet::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'activation' => 'Only draft statutory rule sets can be activated.',
            ]);
        }

        if (
            !$ruleSet->effective_from ||
            (
                $ruleSet->effective_to &&
                $ruleSet->effective_to->lt($ruleSet->effective_from)
            )
        ) {
            throw ValidationException::withMessages([
                'activation' => 'The rule set has invalid effective dates.',
            ]);
        }

        // Validate all statutory rules.
        $validation = $validator->validate($ruleSet);

        if (!$validation['valid']) {
            throw ValidationException::withMessages([
                'activation' => $validation['errors'],
            ]);
        }

        $otherActiveSets = StatutoryRuleSet::where('country_code', $ruleSet->country_code)
            ->where('status', StatutoryRuleSet::STATUS_ACTIVE)
            ->where('id', '!=', $ruleSet->id)
            ->lockForUpdate()
            ->get();

        foreach ($otherActiveSets as $activeSet) {

            $activeEnd = $activeSet->effective_to;

            // No overlap exists if the existing set ends before
            // the new rule set begins.
            if (
                $activeEnd &&
                $activeEnd->lt($ruleSet->effective_from)
            ) {
                continue;
            }

            // A later active rule set cannot be silently shortened.
            if (
                $activeSet->effective_from->gte($ruleSet->effective_from)
            ) {
                throw ValidationException::withMessages([
                    'activation' =>
                        'Another active statutory rule set has an effective period that conflicts with this rule set.',
                ]);
            }

            // End the earlier active rule set the day before
            // the new rule set begins.
            $oldValues = $activeSet->only([
                'effective_from',
                'effective_to',
            ]);

            $activeSet->effective_to = $ruleSet->effective_from
                ->copy()
                ->subDay();

            if ($activeSet->effective_to->lt($activeSet->effective_from)) {
                throw ValidationException::withMessages([
                    'activation' =>
                        'The activation would create an invalid effective period.',
                ]);
            }

            $activeSet->save();

            StatutoryRuleAudit::create([
                'statutory_rule_set_id' => $activeSet->id,
                'user_id' => Auth::id(),
                'auditable_type' => StatutoryRuleSet::class,
                'auditable_id' => $activeSet->id,
                'action' => 'statutory_rule_set_period_adjusted',
                'old_values' => $oldValues,
                'new_values' => $activeSet->only([
                    'effective_from',
                    'effective_to',
                ]),
                'description' =>
                    'Effective period adjusted following activation of a newer statutory rule set.',
            ]);
        }

        $oldValues = $ruleSet->only([
            'status',
            'activated_at',
        ]);

        $ruleSet->status = StatutoryRuleSet::STATUS_ACTIVE;
        $ruleSet->activated_at = now();
        $ruleSet->save();

        StatutoryRuleAudit::create([
            'statutory_rule_set_id' => $ruleSet->id,
            'user_id' => Auth::id(),
            'auditable_type' => StatutoryRuleSet::class,
            'auditable_id' => $ruleSet->id,
            'action' => 'statutory_rule_set_activated',
            'old_values' => $oldValues,
            'new_values' => $ruleSet->only([
                'status',
                'activated_at',
            ]),
            'description' => 'Statutory rule set successfully activated.',
        ]);
    });

    return redirect()
        ->route('statutory-rule-sets.show', $statutoryRuleSet)
        ->with('success', 'Statutory rule set activated successfully.');
}

}
