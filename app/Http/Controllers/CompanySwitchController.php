<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanySwitchController extends Controller
{
    public function __invoke(Request $request, Company $company)
    {
        $user = $request->user();

        abort_unless(
            $company->is_active,
            403,
            'This company is inactive.'
        );

        // Super Admin can switch to ANY active company.
        if ($user->is_super_admin) {
            session([
                'active_company_id' => $company->id,
            ]);

            return back()->with(
                'success',
                "Switched to {$company->name}."
            );
        }

        // Normal users must belong to the company.
        $hasAccess = $user->companies()
            ->where('companies.id', $company->id)
            ->where('companies.is_active', true)
            ->wherePivot('is_active', true)
            ->exists();

        abort_unless(
            $hasAccess,
            403,
            'You do not have access to this company.'
        );

        session([
            'active_company_id' => $company->id,
        ]);

        return back()->with(
            'success',
            "Switched to {$company->name}."
        );
    }
}