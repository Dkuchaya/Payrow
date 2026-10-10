<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\CompanyMemberService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;

class UserCompanyAccessController extends Controller
{
    public function __construct(
        protected CompanyMemberService $memberService,
        protected CompanyContext $companyContext
    ) {}

    public function index(User $user)
    {
        $currentUser = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Authorize access
        |--------------------------------------------------------------------------
        */

        if (!$currentUser->is_super_admin) {
            $company = $this->companyContext->get();

            abort_unless(
                $company,
                403,
                'No active company selected.'
            );

            $this->setTeam($company);

            abort_unless(
                $currentUser->can('users.assign_role'),
                403,
                'You do not have permission to manage company access.'
            );

            abort_unless(
                $user->companies()
                    ->where('companies.id', $company->id)
                    ->wherePivot('is_active', true)
                    ->exists(),
                403,
                'User does not belong to the active company.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | User's company access
        |--------------------------------------------------------------------------
        */

        $companies = $user->companies()
            ->orderBy('name')
            ->get()
            ->map(function (Company $company) use ($user) {

                /*
                 * IMPORTANT:
                 * Set Spatie's team before reading this user's roles.
                 */
                $this->setTeam($company);

                $role = $user->roles()
                    ->where('roles.team_id', $company->id)
                    ->first();

                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'is_active' => (bool) $company->pivot->is_active,
                    'joined_at' => $company->pivot->joined_at,
                    'role' => $role?->name,
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | Companies user does NOT belong to
        |--------------------------------------------------------------------------
        */

        $existingCompanyIds = $user->companies()
            ->pluck('companies.id');

        $availableCompanies = Company::query()
            ->where('is_active', true)
            ->whereNotIn('id', $existingCompanyIds)
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        return Inertia::render('Users/CompanyAccess', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_super_admin' => (bool) $user->is_super_admin,
            ],

            'companies' => $companies,

            'availableCompanies' => $availableCompanies,

            'roles' => $this->roles(),
        ]);
    }

    public function store(Request $request, User $user)
    {
        $currentUser = auth()->user();

        $validated = $request->validate([
            'company_id' => [
                'required',
                'integer',
                'exists:companies,id',
            ],

            'role' => [
                'required',
                'string',
                'in:' . implode(',', $this->roles()),
            ],
        ]);

        $company = Company::findOrFail(
            $validated['company_id']
        );

        /*
        |--------------------------------------------------------------------------
        | Authorization
        |--------------------------------------------------------------------------
        */

        $this->authorizeCompanyManagement(
            $currentUser,
            $company,
            $user
        );

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate membership
        |--------------------------------------------------------------------------
        */

        abort_if(
            $user->companies()
                ->where('companies.id', $company->id)
                ->exists(),
            422,
            'User already has access to this company.'
        );

        /*
        |--------------------------------------------------------------------------
        | Add company membership + role
        |--------------------------------------------------------------------------
        */

        $this->memberService->addUser(
            $user,
            $company,
            $validated['role']
        );

        return back()->with(
            'success',
            'Company access added successfully.'
        );
    }

    public function update(
        Request $request,
        User $user,
        Company $company
    ) {
        $currentUser = auth()->user();

        $validated = $request->validate([
            'role' => [
                'required',
                'string',
                'in:' . implode(',', $this->roles()),
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Authorization
        |--------------------------------------------------------------------------
        */

        $this->authorizeCompanyManagement(
            $currentUser,
            $company,
            $user
        );

        /*
        |--------------------------------------------------------------------------
        | Verify membership
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $user->companies()
                ->where('companies.id', $company->id)
                ->exists(),
            404,
            'User does not belong to this company.'
        );

        /*
        |--------------------------------------------------------------------------
        | Update role
        |--------------------------------------------------------------------------
        */

        $this->memberService->updateRole(
            $user,
            $company,
            $validated['role']
        );

        /*
        |--------------------------------------------------------------------------
        | Update membership status
        |--------------------------------------------------------------------------
        */

        if ($validated['is_active']) {
            $this->memberService->activate(
                $user,
                $company
            );
        } else {
            $this->memberService->deactivate(
                $user,
                $company
            );
        }

        return back()->with(
            'success',
            'Company access updated successfully.'
        );
    }

    public function destroy(
        User $user,
        Company $company
    ) {
        $currentUser = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Super Admin protection
        |--------------------------------------------------------------------------
        */

        abort_if(
            $user->is_super_admin,
            422,
            'Super Admin access cannot be removed.'
        );

        /*
        |--------------------------------------------------------------------------
        | Authorization
        |--------------------------------------------------------------------------
        */

        $this->authorizeCompanyManagement(
            $currentUser,
            $company,
            $user
        );

        /*
        |--------------------------------------------------------------------------
        | Verify membership
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $user->companies()
                ->where('companies.id', $company->id)
                ->exists(),
            404,
            'User does not belong to this company.'
        );

        /*
        |--------------------------------------------------------------------------
        | Remove company access
        |--------------------------------------------------------------------------
        */

        $this->memberService->remove(
            $user,
            $company
        );

        return back()->with(
            'success',
            'Company access removed successfully.'
        );
    }

    protected function authorizeCompanyManagement(
        User $currentUser,
        Company $company,
        User $targetUser
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Company must be active
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $company->is_active,
            422,
            'Company is inactive.'
        );

        /*
        |--------------------------------------------------------------------------
        | Super Admin bypass
        |--------------------------------------------------------------------------
        */

        if ($currentUser->is_super_admin) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Normal users can only manage active company
        |--------------------------------------------------------------------------
        */

        $activeCompany = $this->companyContext->get();

        abort_unless(
            $activeCompany &&
            $activeCompany->id === $company->id,
            403,
            'You can only manage access for your active company.'
        );

        /*
        |--------------------------------------------------------------------------
        | Set Spatie team
        |--------------------------------------------------------------------------
        */

        $this->setTeam($company);

        /*
        |--------------------------------------------------------------------------
        | Permission
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $currentUser->can('users.assign_role'),
            403,
            'You do not have permission to manage company access.'
        );

        /*
        |--------------------------------------------------------------------------
        | Current user must belong to company
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $currentUser->companies()
                ->where('companies.id', $company->id)
                ->wherePivot('is_active', true)
                ->exists(),
            403,
            'You do not belong to this company.'
        );

        /*
        |--------------------------------------------------------------------------
        | Target user must belong to company
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $targetUser->companies()
                ->where('companies.id', $company->id)
                ->exists(),
            403,
            'User does not belong to this company.'
        );
    }

    protected function setTeam(Company $company): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($company->id);
    }

    protected function roles(): array
    {
        return [
            'company_admin',
            'hr_manager',
            'hr_officer',
            'payroll_manager',
            'accountant',
            'supervisor',
            'employee',
            'auditor',
        ];
    }
}