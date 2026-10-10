<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    /**
     * Display users for the current company.
     */
    public function index(Request $request, CompanyContext $companyContext)
    {
        $user = $request->user();

        if (!$user->is_super_admin) {
            $company = $companyContext->get();

            abort_unless(
                $company,
                403,
                'No active company selected.'
            );

            app(PermissionRegistrar::class)
                ->setPermissionsTeamId($company->id);

            abort_unless(
                $user->can('users.view'),
                403,
                'You do not have permission to view users.'
            );
        }

        $query = User::query()
            ->with([
                'companies:id,name',
                'roles:id,name',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        */

        if (!$user->is_super_admin) {
            $company = $companyContext->get();

            abort_unless(
                $company,
                403,
                'No active company selected.'
            );

            $query->whereHas('companies', function ($q) use ($company) {
                $q->where('companies.id', $company->id)
                    ->where('company_user.is_active', true)
                    ->where('companies.is_active', true);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Status filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where(
                'is_active',
                $request->string('status')->toString() === 'active'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Role filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where(
                    'name',
                    $request->string('role')->toString()
                );
            });
        }

        $users = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'role' => $request->role,
            ],
            'roles' => [
                'company_admin',
                'hr_manager',
                'hr_officer',
                'payroll_manager',
                'accountant',
                'supervisor',
                'employee',
                'auditor',
            ],
        ]);
    }

    /**
     * Show create user form.
     */
    public function create(
        Request $request,
        CompanyContext $companyContext
    ) {
        $user = $request->user();

        if (!$user->is_super_admin) {
            $company = $companyContext->get();

            abort_unless(
                $company,
                403,
                'No active company selected.'
            );

            app(PermissionRegistrar::class)
                ->setPermissionsTeamId($company->id);

            abort_unless(
                $user->can('users.create'),
                403,
                'You do not have permission to create users.'
            );
        }

        if ($user->is_super_admin) {
            $companies = Company::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ]);
        } else {
            $company = $companyContext->get();

            abort_unless(
                $company,
                403,
                'No active company selected.'
            );

            $companies = collect([
                [
                    'id' => $company->id,
                    'name' => $company->name,
                ],
            ]);
        }

        return Inertia::render('Users/Create', [
            'companies' => $companies,
            'roles' => [
                'company_admin',
                'hr_manager',
                'hr_officer',
                'payroll_manager',
                'accountant',
                'supervisor',
                'employee',
                'auditor',
            ],
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(
        Request $request,
        CompanyContext $companyContext
    ) {
        $currentUser = $request->user();

        if (!$currentUser->is_super_admin) {
            $company = $companyContext->get();

            abort_unless(
                $company,
                403,
                'No active company selected.'
            );

            app(PermissionRegistrar::class)
                ->setPermissionsTeamId($company->id);

            abort_unless(
                $currentUser->can('users.create'),
                403,
                'You do not have permission to create users.'
            );
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'company_id' => [
                'required',
                'integer',
                'exists:companies,id',
            ],

            'role' => [
                'required',
                'string',
                Rule::in([
                    'company_admin',
                    'hr_manager',
                    'hr_officer',
                    'payroll_manager',
                    'accountant',
                    'supervisor',
                    'employee',
                    'auditor',
                ]),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Determine Company
        |--------------------------------------------------------------------------
        */

        if ($currentUser->is_super_admin) {

            $company = Company::query()
                ->where('id', $validated['company_id'])
                ->where('is_active', true)
                ->firstOrFail();

        } else {

            $company = $companyContext->get();

            abort_unless(
                $company,
                403,
                'No active company selected.'
            );

            /*
            | Never trust company_id submitted by a normal user.
            */

            abort_unless(
                (int) $validated['company_id'] === $company->id,
                403,
                'You cannot assign a user to another company.'
            );

            /*
            | Verify current user actually belongs to company.
            */

            abort_unless(
                $currentUser->companies()
                    ->where('companies.id', $company->id)
                    ->where('companies.is_active', true)
                    ->wherePivot('is_active', true)
                    ->exists(),
                403,
                'You do not have access to this company.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create User + Company Membership + Role
        |--------------------------------------------------------------------------
        */

        $newUser = DB::transaction(function () use (
            $validated,
            $company
        ) {
            $newUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            $newUser->companies()->attach(
                $company->id,
                [
                    'is_active' => true,
                    'joined_at' => now(),
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Set Spatie Team
            |--------------------------------------------------------------------------
            */

            app(PermissionRegistrar::class)
                ->setPermissionsTeamId($company->id);

            /*
            |--------------------------------------------------------------------------
            | Assign Company Role
            |--------------------------------------------------------------------------
            */

            $newUser->assignRole($validated['role']);

            /*
            |--------------------------------------------------------------------------
            | Store Phone
            |--------------------------------------------------------------------------
            */

           $newUser->settings()->updateOrCreate(
                ['user_id' => $newUser->id],
                [
                    'phone' => $validated['phone'] ?? null,
                ]
            );

            return $newUser;
        });

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                "{$newUser->name} was created successfully."
            );
    }

    /**
     * Display a user.
     */
    public function show(
        Request $request,
        User $user,
        CompanyContext $companyContext
    ) {
        $this->authorizeUserAccess(
            $request,
            $user,
            $companyContext,
            'users.view'
        );

        $user->load([
            'companies:id,name',
            'settings',
            'roles:id,name',
        ]);

        return Inertia::render('Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'is_super_admin' => $user->is_super_admin,
                'phone' => $user->settings?->phone,
                'companies' => $user->companies
                    ->map(fn ($company) => [
                        'id' => $company->id,
                        'name' => $company->name,
                    ])
                    ->values(),
                'roles' => $user->roles
                    ->pluck('name')
                    ->values(),
            ],
        ]);
    }

    /**
     * Show edit user form.
     */
    public function edit(
        Request $request,
        User $user,
        CompanyContext $companyContext
    ) {
        $this->authorizeUserAccess(
            $request,
            $user,
            $companyContext,
            'users.update'
        );

        $user->load([
            'companies:id,name',
            'settings',
            'roles:id,name',
        ]);

        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'phone' => $user->settings?->phone,
                'company_id' => $user->companies->first()?->id,
                'role' => $user->roles->first()?->name,
            ],
            'companies' => $request->user()->is_super_admin
                ? Company::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : collect([
                    [
                        'id' => $companyContext->id(),
                        'name' => $companyContext->get()?->name,
                    ],
                ]),
            'roles' => [
                'company_admin',
                'hr_manager',
                'hr_officer',
                'payroll_manager',
                'accountant',
                'supervisor',
                'employee',
                'auditor',
            ],
        ]);
    }

    /**
     * Update user.
     */
   public function update(
    Request $request,
    User $user,
    CompanyContext $companyContext
) {
    $this->authorizeUserAccess(
        $request,
        $user,
        $companyContext,
        'users.update'
    );

    $currentUser = $request->user();
    $company = $companyContext->get();

    abort_unless(
        $company,
        403,
        'No active company selected.'
    );

    $validated = $request->validate([
        'name' => [
            'required',
            'string',
            'max:255',
        ],

        'email' => [
            'required',
            'email',
            Rule::unique('users', 'email')
                ->ignore($user->id),
        ],

        'phone' => [
            'nullable',
            'string',
            'max:30',
        ],

        'is_active' => [
            'required',
            'boolean',
        ],

        'role' => [
            'required',
            'string',
            Rule::in([
                'company_admin',
                'hr_manager',
                'hr_officer',
                'payroll_manager',
                'accountant',
                'supervisor',
                'employee',
                'auditor',
            ]),
        ],
    ]);

    DB::transaction(function () use (
        $user,
        $validated,
        $company
    ) {

        /*
        |--------------------------------------------------------------------------
        | Update User Profile
        |--------------------------------------------------------------------------
        */

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $validated['is_active'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Phone
        |--------------------------------------------------------------------------
        */

        $user->settings()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone' => $validated['phone'] ?? null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Set Spatie Team
        |--------------------------------------------------------------------------
        */

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($company->id);

        /*
        |--------------------------------------------------------------------------
        | Clear Previously Loaded Role Relationships
        |--------------------------------------------------------------------------
        */

        $user->unsetRelation('roles')
             ->unsetRelation('permissions');

        /*
        |--------------------------------------------------------------------------
        | Update Company Role
        |--------------------------------------------------------------------------
        */

        $user->syncRoles([
            $validated['role'],
        ]);
    });

    return redirect()
        ->route('users.index')
        ->with(
            'success',
            "{$user->name} was updated successfully."
        );
}
    /**
     * Toggle user status.
     */
    public function toggleStatus(
        Request $request,
        User $user,
        CompanyContext $companyContext
    ) {
        $this->authorizeUserAccess(
            $request,
            $user,
            $companyContext,
            'users.update'
        );

        /*
        |--------------------------------------------------------------------------
        | Prevent Super Admin from being disabled accidentally.
        |--------------------------------------------------------------------------
        */

        abort_if(
            $user->is_super_admin,
            403,
            'The Super Admin account cannot be disabled here.'
        );

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return back()->with(
            'success',
            $user->is_active
                ? "{$user->name} has been activated."
                : "{$user->name} has been deactivated."
        );
    }

    /**
     * Remove user from current company.
     */
    public function removeCompanyAccess(
        Request $request,
        User $user,
        CompanyContext $companyContext
    ) {
        $currentUser = $request->user();

        $company = $companyContext->get();

        abort_unless(
            $company,
            403,
            'No active company selected.'
        );

        $this->authorizeUserAccess(
            $request,
            $user,
            $companyContext
        );

        abort_if(
            $user->is_super_admin,
            403,
            'Super Admin access cannot be removed here.'
        );

        DB::transaction(function () use (
            $user,
            $company,
            $currentUser
        ) {
            /*
            |--------------------------------------------------------------------------
            | Remove Company Membership
            |--------------------------------------------------------------------------
            */

            $user->companies()->detach($company->id);

            /*
            |--------------------------------------------------------------------------
            | Remove Company Role
            |--------------------------------------------------------------------------
            */

            app(PermissionRegistrar::class)
                ->setPermissionsTeamId($company->id);

            $user->syncRoles([]);
        });

        return back()->with(
            'success',
            "{$user->name} has been removed from {$company->name}."
        );
    }

    /**
     * Authorize access to a target user.
     */
    protected function authorizeUserAccess(
    Request $request,
    User $user,
    CompanyContext $companyContext,
    ?string $permission = null
): void {
    $currentUser = $request->user();

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    */

    if ($currentUser->is_super_admin) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Active Company
    |--------------------------------------------------------------------------
    */

    $company = $companyContext->get();

    abort_unless(
        $company,
        403,
        'No active company selected.'
    );

    /*
    |--------------------------------------------------------------------------
    | Set Spatie Team
    |--------------------------------------------------------------------------
    */

    app(PermissionRegistrar::class)
        ->setPermissionsTeamId($company->id);

    /*
    |--------------------------------------------------------------------------
    | Current User Must Have Access
    |--------------------------------------------------------------------------
    */

    abort_unless(
        $currentUser->companies()
            ->where('companies.id', $company->id)
            ->where('companies.is_active', true)
            ->wherePivot('is_active', true)
            ->exists(),
        403,
        'You do not have access to this company.'
    );

    /*
    |--------------------------------------------------------------------------
    | Permission
    |--------------------------------------------------------------------------
    */

    if ($permission) {
        abort_unless(
            $currentUser->can($permission),
            403,
            'You do not have permission to perform this action.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Target User Must Belong To Active Company
    |--------------------------------------------------------------------------
    */

    abort_unless(
        $user->companies()
            ->where('companies.id', $company->id)
            ->where('companies.is_active', true)
            ->wherePivot('is_active', true)
            ->exists(),
        403,
        'This user does not belong to the active company.'
    );
  }
}