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
use Illuminate\Support\Collection;

class UserController extends Controller
{
    /**
     * Display users for the current company.
     */
   public function index(
    Request $request,
    CompanyContext $companyContext
) {
    $user = $request->user();

    /*
    |--------------------------------------------------------------------------
    | Access check
    |--------------------------------------------------------------------------
    */

    if (! $user->is_super_admin) {
        $company = $companyContext->get();

        abort_unless(
            $company,
            403,
            'No active company selected.'
        );

        abort_unless(
            $user->can('users.view'),
            403,
            'You do not have permission to view users.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Base query
    |--------------------------------------------------------------------------
    */

    $query = User::query()
        ->with([
            'companies:id,name',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Company filtering
    |--------------------------------------------------------------------------
    */

    if (! $user->is_super_admin) {
        $company = $companyContext->get();

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
        $role = $request->string('role')->toString();

        $query->whereHas('roles', function ($q) use ($role, $user, $companyContext) {
            $q->where('roles.name', $role);

            /*
            | For normal users, make sure the role belongs
            | to the currently active company/team.
            */
            if (! $user->is_super_admin) {
                $company = $companyContext->get();

                $q->where('roles.team_id', $company->id);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    $users = $query
        ->latest()
        ->paginate(15)
        ->withQueryString();

    /*
    |--------------------------------------------------------------------------
    | Build company + role information
    |--------------------------------------------------------------------------
    */

    $users->getCollection()->transform(function ($user) {

        $companyAccess = $user->companies
            ->map(function ($company) use ($user) {

                $role = DB::table('model_has_roles')
                    ->join('roles', function ($join) {
                        $join->on(
                            'roles.id',
                            '=',
                            'model_has_roles.role_id'
                        )->on(
                            'roles.team_id',
                            '=',
                            'model_has_roles.team_id'
                        );
                    })
                    ->where(
                        'model_has_roles.model_type',
                        User::class
                    )
                    ->where(
                        'model_has_roles.model_id',
                        $user->id
                    )
                    ->where(
                        'model_has_roles.team_id',
                        $company->id
                    )
                    ->value('roles.name');

                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'role' => $role,
                ];
            })
            ->values();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'is_super_admin' => $user->is_super_admin,
            'company_access' => $companyAccess,
        ];
    });

    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

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
    ]);

    $companyAccess = $user->companies
        ->map(function ($company) use ($user) {

            $role = DB::table('model_has_roles')
                ->join('roles', function ($join) {
                    $join->on(
                        'roles.id',
                        '=',
                        'model_has_roles.role_id'
                    )->on(
                        'roles.team_id',
                        '=',
                        'model_has_roles.team_id'
                    );
                })
                ->where(
                    'model_has_roles.model_type',
                    User::class
                )
                ->where(
                    'model_has_roles.model_id',
                    $user->id
                )
                ->where(
                    'model_has_roles.team_id',
                    $company->id
                )
                ->value('roles.name');

            return [
                'id' => $company->id,
                'name' => $company->name,
                'role' => $role,
            ];
        })
        ->values();

    return Inertia::render('Users/Show', [
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'is_super_admin' => $user->is_super_admin,
            'phone' => $user->settings?->phone,

            'company_access' => $companyAccess,
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
    ]);

    /*
    |--------------------------------------------------------------------------
    | Get the user's role for each company
    |--------------------------------------------------------------------------
    */

    $companyAccess = $user->companies
        ->map(function ($company) use ($user) {

            $role = DB::table('model_has_roles')
                ->join('roles', function ($join) {
                    $join->on('roles.id', '=', 'model_has_roles.role_id')
                        ->on('roles.team_id', '=', 'model_has_roles.team_id');
                })
                ->where('model_has_roles.model_type', User::class)
                ->where('model_has_roles.model_id', $user->id)
                ->where('model_has_roles.team_id', $company->id)
                ->value('roles.name');

            return [
                'id' => $company->id,
                'name' => $company->name,
                'role' => $role,
            ];
        })
        ->values();

    /*
    |--------------------------------------------------------------------------
    | Available companies
    |--------------------------------------------------------------------------
    */

    if ($request->user()->is_super_admin) {
        $companies = Company::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    } else {
        $companies = collect([
            [
                'id' => $companyContext->id(),
                'name' => $companyContext->get()?->name,
            ],
        ]);
    }

    return Inertia::render('Users/Edit', [
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'is_super_admin' => $user->is_super_admin,
            'phone' => $user->settings?->phone,

            // NEW: company-specific roles
            'company_access' => $companyAccess,
        ],

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

        'company_access' => [
            'required',
            'array',
            'min:1',
        ],

        'company_access.*.company_id' => [
            'required',
            'integer',
            'exists:companies,id',
        ],

        'company_access.*.role' => [
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
    | Normal users may ONLY modify the active company
    |--------------------------------------------------------------------------
    */

    if (! $currentUser->is_super_admin) {
        $activeCompany = $companyContext->get();

        abort_unless(
            $activeCompany,
            403,
            'No active company selected.'
        );

        foreach ($validated['company_access'] as $access) {
            abort_unless(
                (int) $access['company_id'] === (int) $activeCompany->id,
                403,
                'You can only manage access for the active company.'
            );
        }

        abort_unless(
            $user->companies()
                ->where('companies.id', $activeCompany->id)
                ->where('companies.is_active', true)
                ->wherePivot('is_active', true)
                ->exists(),
            403,
            'This user does not belong to the active company.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Super Admin can manage all company memberships
    |--------------------------------------------------------------------------
    */

    $companyIds = collect($validated['company_access'])
        ->pluck('company_id')
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values();

    $companies = Company::query()
        ->whereIn('id', $companyIds)
        ->where('is_active', true)
        ->get()
        ->keyBy('id');

    abort_unless(
        $companies->count() === $companyIds->count(),
        422,
        'One or more selected companies are invalid.'
    );

    DB::transaction(function () use (
        $user,
        $validated,
        $companies
    ) {
        /*
        |--------------------------------------------------------------------------
        | Update basic user information
        |--------------------------------------------------------------------------
        */

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $validated['is_active'],
        ]);

        $user->settings()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone' => $validated['phone'] ?? null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Update company memberships + roles
        |--------------------------------------------------------------------------
        */

        foreach ($validated['company_access'] as $access) {
            $companyId = (int) $access['company_id'];
            $role = $access['role'];

            $company = $companies->get($companyId);

            /*
            |--------------------------------------------------------------------------
            | Make sure user belongs to the company
            |--------------------------------------------------------------------------
            */

            $user->companies()->syncWithoutDetaching([
                $company->id => [
                    'is_active' => true,
                    'joined_at' => now(),
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Set Spatie team
            |--------------------------------------------------------------------------
            */

            app(PermissionRegistrar::class)
                ->setPermissionsTeamId($company->id);

            /*
            |--------------------------------------------------------------------------
            | Refresh role/permission relationships
            |--------------------------------------------------------------------------
            */

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            /*
            |--------------------------------------------------------------------------
            | Assign the role for THIS company only
            |--------------------------------------------------------------------------
            */

            $user->syncRoles([
                $role,
            ]);

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        }
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