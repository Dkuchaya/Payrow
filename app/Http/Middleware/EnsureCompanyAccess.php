<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyAccess
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        // Auth middleware should handle unauthenticated users.
        if (!$user) {
            abort(401);
        }

        // Super Admin can access any active company.
        if ($user->is_super_admin) {
            return $next($request);
        }

        $activeCompany = app(CompanyContext::class)->get();

        if (!$activeCompany) {
            abort(403, 'No active company selected.');
        }

        $routeCompany = $request->route('company');

        if ($routeCompany instanceof Company) {
            if ($routeCompany->id !== $activeCompany->id) {
                abort(
                    403,
                    'The selected company does not match the active company.'
                );
            }
        }

        $hasAccess = $user->companies()
            ->where('companies.id', $activeCompany->id)
            ->where('companies.is_active', true)
            ->wherePivot('is_active', true)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'You do not have access to this company.');
        }

        return $next($request);
    }
}