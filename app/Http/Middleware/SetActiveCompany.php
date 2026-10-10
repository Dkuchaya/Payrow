<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class SetActiveCompany
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (!$request->user()) {
            return $next($request);
        }

        $user = $request->user();

        $companyId = session('active_company_id');

        $company = null;

        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        */

        if ($user->is_super_admin) {

            if ($companyId) {
                $company = Company::query()
                    ->where('id', $companyId)
                    ->where('is_active', true)
                    ->first();
            }

            if (!$company) {
                $company = Company::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->first();

                if ($company) {
                    session([
                        'active_company_id' => $company->id,
                    ]);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Normal Company User
        |--------------------------------------------------------------------------
        */

        else {

            if ($companyId) {
                $company = $user->companies()
                    ->wherePivot('is_active', true)
                    ->where('companies.is_active', true)
                    ->where('companies.id', $companyId)
                    ->first();
            }

            if (!$company) {
                $company = $user->companies()
                    ->wherePivot('is_active', true)
                    ->where('companies.is_active', true)
                    ->orderBy('companies.name')
                    ->first();

                if ($company) {
                    session([
                        'active_company_id' => $company->id,
                    ]);
                } else {
                    session()->forget('active_company_id');
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Set Active Company + Spatie Team
        |--------------------------------------------------------------------------
        */

        if ($company) {

            app(CompanyContext::class)->set($company);

            app(PermissionRegistrar::class)
                ->setPermissionsTeamId($company->id);

        } else {

            app(CompanyContext::class)->clear();

            app(PermissionRegistrar::class)
                ->setPermissionsTeamId(null);
        }

        return $next($request);
    }
}