<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CompanySettingsController extends Controller
{
    /**
     * Display company settings.
     */
    public function index(CompanyContext $companyContext)
    {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        $this->authorizeCompanySettings($company);

        return Inertia::render('Settings/Company', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'legal_name' => $company->legal_name,
                'slug' => $company->slug,
                'registration_number' => $company->registration_number,
                'tax_number' => $company->tax_number,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'city' => $company->city,
                'country' => $company->country,
                'currency' => $company->currency,
                'logo' => $company->logo
                    ? asset('storage/' . $company->logo)
                    : null,
                'is_active' => $company->is_active,
            ],
        ]);
    }

    /**
     * Update company information.
     */
    public function update(
        Request $request,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        $this->authorizeCompanySettings($company);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'legal_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'registration_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'tax_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'address' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'country' => [
                'required',
                'string',
                'max:100',
            ],

            'currency' => [
                'required',
                'string',
                'size:3',
            ],
        ]);

        $company->update($validated);

        return back()->with(
            'success',
            'Company information updated successfully.'
        );
    }

    /**
     * Upload company logo.
     */
    public function updateLogo(
        Request $request,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        $this->authorizeCompanySettings($company);

        $validated = $request->validate([
            'logo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $path = $validated['logo']->store(
            'companies/logos',
            'public'
        );

        $company->update([
            'logo' => $path,
        ]);

        return back()->with(
            'success',
            'Company logo updated successfully.'
        );
    }

    /**
     * Remove company logo.
     */
    public function removeLogo(
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        $this->authorizeCompanySettings($company);

        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);

            $company->update([
                'logo' => null,
            ]);
        }

        return back()->with(
            'success',
            'Company logo removed successfully.'
        );
    }

    /**
     * Ensure the current user can manage company settings.
     */
    protected function authorizeCompanySettings(Company $company): void
    {
        $user = request()->user();

        if ($user?->is_super_admin) {
            return;
        }

        abort_unless(
            $user &&
            $user->companies()
                ->where('companies.id', $company->id)
                ->where('companies.is_active', true)
                ->wherePivot('is_active', true)
                ->exists(),
            403,
            'You do not have access to this company.'
        );

        // Company settings are restricted to company admins.
        abort_unless(
            $user->can('companies.update'),
            403,
            'You do not have permission to manage company settings.'
        );
    }
}