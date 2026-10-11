<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use App\Support\CompanyContext;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
{
    $companyContext = app(CompanyContext::class);

    $activeCompany = $companyContext->get();

    $user = $request->user();

    $availableCompanies = collect();

    if ($user) {

        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        | Super Admin can see every active company.
        | They do not need company_user membership.
        |--------------------------------------------------------------------------
        */

        if ($user->is_super_admin) {

            $availableCompanies = \App\Models\Company::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'logo',
                    'currency',
                ]);

        /*
        |--------------------------------------------------------------------------
        | Normal Company User
        |--------------------------------------------------------------------------
        | Only show companies the user belongs to.
        |--------------------------------------------------------------------------
        */

        } else {

            $availableCompanies = $user->companies()
                ->wherePivot('is_active', true)
                ->where('companies.is_active', true)
                ->orderBy('companies.name')
                ->get([
                    'companies.id',
                    'companies.name',
                    'companies.logo',
                    'companies.currency',
                ]);
        }
    }

    return [
        ...parent::share($request),

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        'auth' => [
            'user' => $user,
            'permissions' => $user
                ? $user->getAllPermissions()
                    ->pluck('name')
                    ->values()
                : [],
        ],

        /*
        |--------------------------------------------------------------------------
        | Flash Messages
        |--------------------------------------------------------------------------
        */

        'flash' => [
            'success' => fn () => $request->session()->get('success'),
            'error' => fn () => $request->session()->get('error'),
            'warning' => fn () => $request->session()->get('warning'),
            'info' => fn () => $request->session()->get('info'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Company Context
        |--------------------------------------------------------------------------
        */

        'company' => [

            /*
            |--------------------------------------------------------------------------
            | Currently Active Company
            |--------------------------------------------------------------------------
            */

            'active' => $activeCompany ? [
                'id' => $activeCompany->id,
                'name' => $activeCompany->name,
                'legal_name' => $activeCompany->legal_name,
                'logo' => $activeCompany->logo
                    ? asset('storage/' . $activeCompany->logo)
                    : null,
                'currency' => $activeCompany->currency,
            ] : null,

            /*
            |--------------------------------------------------------------------------
            | Available Companies
            |--------------------------------------------------------------------------
            */

            'available' => $availableCompanies
                ->map(fn ($company) => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'logo' => $company->logo
                        ? asset('storage/' . $company->logo)
                        : null,
                    'currency' => $company->currency,
                ])
                ->values(),
        ],
    ];
}
}
