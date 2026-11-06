<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\StatutoryRuleAudit;
use App\Models\StatutoryRuleSet;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::query()
            ->withCount('users')
            ->latest()
            ->paginate(15);

        return Inertia::render('Companies/Index', [
            'companies' => $companies,
        ]);
    }

    public function create()
    {
        return Inertia::render('Companies/Create');
    }

   
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'legal_name' => ['nullable', 'string', 'max:255'],
        'registration_number' => ['nullable', 'string', 'max:100'],
        'tax_number' => ['nullable', 'string', 'max:100'],
        'email' => ['nullable', 'email'],
        'phone' => ['nullable', 'string', 'max:50'],
        'address' => ['nullable', 'string'],
        'city' => ['nullable', 'string', 'max:100'],
        'country' => ['required', 'string', 'max:100'],
        'currency' => ['required', 'string', 'size:3'],

        'organisation_type' => ['nullable', 'string', 'max:100'],
        'annual_turnover' => ['nullable', 'numeric', 'min:0'],
        'is_sdl_exempt' => ['sometimes', 'boolean'],
        'sdl_exemption_reason' => ['nullable', 'string', 'max:1000'],
        'sdl_exemption_reference' => ['nullable', 'string', 'max:255'],

        'logo' => [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:2048',
        ],
    ]);

    // Always store the checkbox value.
    $validated['is_sdl_exempt'] = $request->boolean('is_sdl_exempt');

    // Upload company logo.
    if ($request->hasFile('logo')) {
        $validated['logo'] = $request
            ->file('logo')
            ->store('companies/logos', 'public');
    }

    $validated['slug'] = Str::slug($validated['name']);

    DB::transaction(function () use ($validated) {

        $company = Company::create($validated);

        // Record the initial SDL exemption declaration.
        if ($company->is_sdl_exempt) {

            $activeRuleSet = StatutoryRuleSet::query()
                ->where('country_code', 'ZM')
                ->where('status', StatutoryRuleSet::STATUS_ACTIVE)
                ->orderByDesc('effective_from')
                ->orderByDesc('version')
                ->first();

            StatutoryRuleAudit::create([
                'statutory_rule_set_id' => $activeRuleSet?->id,
                'user_id' => auth()->id(),
                'auditable_type' => Company::class,
                'auditable_id' => $company->id,
                'action' => 'company_sdl_exemption_declared',
                'old_values' => [
                    'is_sdl_exempt' => false,
                    'sdl_exemption_reason' => null,
                    'sdl_exemption_reference' => null,
                ],
                'new_values' => [
                    'is_sdl_exempt' => $company->is_sdl_exempt,
                    'sdl_exemption_reason' => $company->sdl_exemption_reason,
                    'sdl_exemption_reference' => $company->sdl_exemption_reference,
                ],
                'description' => 'Initial SDL exemption declaration recorded during company registration.',
            ]);
        }
    });

    return redirect()
        ->route('companies.index')
        ->with('success', 'Company created successfully.');
}


    public function edit(Company $company)
    {
        return Inertia::render('Companies/Edit', [
            'company' => $company,
        ]);
    }

   public function update(Request $request, Company $company)
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'legal_name' => ['nullable', 'string', 'max:255'],
        'registration_number' => ['nullable', 'string', 'max:100'],
        'tax_number' => ['nullable', 'string', 'max:100'],
        'email' => ['nullable', 'email'],
        'phone' => ['nullable', 'string', 'max:50'],
        'address' => ['nullable', 'string'],
        'city' => ['nullable', 'string', 'max:100'],
        'country' => ['required', 'string', 'max:100'],
        'currency' => ['required', 'string', 'size:3'],

        'organisation_type' => [
            'nullable',
            'string',
            'max:100',
        ],
        'annual_turnover' => [
            'nullable',
            'numeric',
            'min:0',
        ],
        'is_sdl_exempt' => [
            'sometimes',
            'boolean',
        ],
        'sdl_exemption_reason' => [
            'nullable',
            'string',
            'max:1000',
        ],
        'sdl_exemption_reference' => [
            'nullable',
            'string',
            'max:255',
        ],

        'logo' => [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:2048',
        ],
    ]);

    if ($request->hasFile('logo')) {
        $validated['logo'] = $request
            ->file('logo')
            ->store('companies/logos', 'public');
    }

    $sdlFields = [
        'organisation_type',
        'annual_turnover',
        'is_sdl_exempt',
        'sdl_exemption_reason',
        'sdl_exemption_reference',
    ];

    $oldSdlValues = [];

    foreach ($sdlFields as $field) {
        $oldSdlValues[$field] = $company->getAttribute($field);
    }

    $newSdlValues = array_intersect_key(
        $validated,
        array_flip($sdlFields)
    );

    DB::transaction(function () use (
        $company,
        $validated,
        $oldSdlValues,
        $newSdlValues,
        $sdlFields
    ) {
        $company->update($validated);

        $changedSdlValues = [];

        foreach ($newSdlValues as $field => $value) {
            $oldValue = $oldSdlValues[$field];

            $newValue = $company->getAttribute($field);

            if ($oldValue != $newValue) {
                $changedSdlValues[$field] = $newValue;
            }
        }

        if (!empty($changedSdlValues)) {
            $activeRuleSet = StatutoryRuleSet::query()
                ->where('country_code', 'ZM')
                ->where('status', StatutoryRuleSet::STATUS_ACTIVE)
                ->orderByDesc('effective_from')
                ->orderByDesc('version')
                ->first();

            StatutoryRuleAudit::create([
                'statutory_rule_set_id' => $activeRuleSet?->id,
                'user_id' => auth()->id(),
                'auditable_type' => Company::class,
                'auditable_id' => $company->id,
                'action' => 'company_sdl_exemption_updated',
                'old_values' => array_intersect_key(
                    $oldSdlValues,
                    $changedSdlValues
                ),
                'new_values' => $changedSdlValues,
                'description' => 'Company SDL exemption and eligibility details updated.',
            ]);
        }
    });

    return redirect()
        ->route('companies.index')
        ->with('success', 'Company updated successfully.');
}
}