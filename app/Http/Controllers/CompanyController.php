<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function store(Request $request){
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

    $validated['slug'] = Str::slug($validated['name']);

    $company = Company::create($validated);

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

    public function update(Request $request, Company $company){
        
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

            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $company->update($validated);

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company updated successfully.');
    }
}