<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Support\CompanyContext;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(
    Request $request,
    CompanyContext $companyContext
): Response {
    $company = $companyContext->get();

    abort_unless(
        $company,
        403,
        'No active company selected.'
    );

    abort_unless(
        $request->user()->is_super_admin ||
        $request->user()->can('employees.view'),
        403,
        'You do not have permission to view employees.'
    );

    $search = trim($request->string('search')->toString());
    $employment_status = $request->string('employment_status')->toString();
    $departmentId = $request->integer('department_id');
    $positionId = $request->integer('position_id');
    $employmentType = $request->string('employment_type')->toString();

    /*
    |--------------------------------------------------------------------------
    | Employee List
    |--------------------------------------------------------------------------
    */

    $employees = Employee::query()
        ->where('company_id', $company->id)
        ->with([
            'department:id,name',
            'position:id,name',
            'supervisor:id,first_name,last_name',
        ])
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        })
        ->when(
            in_array($employment_status, [
                'active',
                'on_leave',
                'suspended',
                'separated',
            ], true),
            fn ($query) => $query->where(
                'status',
                $employment_status
            )
        )
        ->when(
            $departmentId,
            fn ($query) => $query->where(
                'department_id',
                $departmentId
            )
        )
        ->when(
            $positionId,
            fn ($query) => $query->where(
                'position_id',
                $positionId
            )
        )
        ->when(
            in_array($employmentType, [
                'permanent',
                'contract',
                'temporary',
                'casual',
                'intern',
                'part_time',
            ], true),
            fn ($query) => $query->where(
                'employment_type',
                $employmentType
            )
        )
        ->orderBy('first_name')
        ->orderBy('last_name')
        ->paginate(20)
        ->withQueryString();

    /*
    |--------------------------------------------------------------------------
    | Workforce Statistics
    |--------------------------------------------------------------------------
    */

    $statsQuery = Employee::query()
        ->where('company_id', $company->id);

    $stats = [
        'total' => (clone $statsQuery)->count(),

        'active' => (clone $statsQuery)
            ->where('employment_status', 'active')
            ->count(),

        'on_leave' => (clone $statsQuery)
            ->where('employment_status', 'on_leave')
            ->count(),

        'suspended' => (clone $statsQuery)
            ->where('employment_status', 'suspended')
            ->count(),

        'separated' => (clone $statsQuery)
            ->where('employment_status', 'separated')
            ->count(),
    ];

    /*
    |--------------------------------------------------------------------------
    | Employees By Department
    |--------------------------------------------------------------------------
    */

    $departmentStats = Employee::query()
        ->where('employees.company_id', $company->id)
        ->whereNotNull('employees.department_id')
        ->join(
            'departments',
            'departments.id',
            '=',
            'employees.department_id'
        )
        ->selectRaw(
            'departments.id,
             departments.name,
             COUNT(employees.id) as employee_count'
        )
        ->groupBy(
            'departments.id',
            'departments.name'
        )
        ->orderByDesc('employee_count')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Employees By Employment Type
    |--------------------------------------------------------------------------
    */

    $employmentTypeStats = Employee::query()
        ->where('company_id', $company->id)
        ->selectRaw(
            'employment_type,
             COUNT(*) as employee_count'
        )
        ->groupBy('employment_type')
        ->orderByDesc('employee_count')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Filter Options
    |--------------------------------------------------------------------------
    */

    $departments = Department::query()
        ->where('company_id', $company->id)
        ->where('is_active', true)
        ->orderBy('name')
        ->get([
            'id',
            'name',
        ]);

    $positions = Position::query()
        ->where('company_id', $company->id)
        ->where('is_active', true)
        ->orderBy('name')
        ->get([
            'id',
            'department_id',
            'name',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Subscription Usage
    |--------------------------------------------------------------------------
    */

    $subscriptionService = app(
        SubscriptionService::class
    );

    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    return Inertia::render('Employees/Index', [
        'employees' => $employees,

        'filters' => [
            'search' => $search,
            'employment_status' => $employment_status,
            'department_id' => $departmentId,
            'position_id' => $positionId,
            'employment_type' => $employmentType,
        ],

        'stats' => $stats,

        'departmentStats' => $departmentStats,

        'employmentTypeStats' => $employmentTypeStats,

        'departments' => $departments,

        'positions' => $positions,

        'usage' => $subscriptionService->getUsage($company),
    ]);
}
    public function create(
        Request $request,
        CompanyContext $companyContext,
        SubscriptionService $subscriptionService
    ): Response {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $request->user()->is_super_admin ||
            $request->user()->can('employees.create'),
            403,
            'You do not have permission to create employees.'
        );

        if (! $request->user()->is_super_admin) {
            abort_unless(
                $subscriptionService->canUseSystem($company),
                403,
                'The company does not have an active subscription.'
            );
        }

        abort_unless(
            $subscriptionService->canAddEmployee($company),
            403,
            'The employee limit for the current subscription plan has been reached.'
        );

        $departments = Department::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $positions = Position::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'department_id',
                'name',
            ]);

        $supervisors = Employee::query()
            ->where('company_id', $company->id)
            ->whereIn('employment_employment_status', [
                'active',
                'on_leave',
                'suspended',
            ])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get([
                'id',
                'employee_number',
                'first_name',
                'last_name',
            ]);

        return Inertia::render('Employees/Create', [
            'departments' => $departments,
            'positions' => $positions,
            'supervisors' => $supervisors,
            'usage' => $subscriptionService->getUsage($company),
        ]);
    }

    public function store(
        Request $request,
        CompanyContext $companyContext,
        SubscriptionService $subscriptionService
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $request->user()->is_super_admin ||
            $request->user()->can('employees.create'),
            403,
            'You do not have permission to create employees.'
        );

        abort_unless(
            $subscriptionService->canUseSystem($company),
            403,
            'The company does not have an active subscription.'
        );

        abort_unless(
            $subscriptionService->canAddEmployee($company),
            403,
            'The employee limit for the current subscription plan has been reached.'
        );

        $validated = $request->validate([
            'employee_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('employees', 'employee_number')
                    ->where('company_id', $company->id),
            ],

            'national_id' => [
                'nullable',
                'string',
                'max:100',
            ],

            'first_name' => [
                'required',
                'string',
                'max:100',
            ],

            'middle_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'last_name' => [
                'required',
                'string',
                'max:100',
            ],

            'preferred_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'gender' => [
                'nullable',
                Rule::in(['male', 'female', 'other']),
            ],

            'date_of_birth' => [
                'nullable',
                'date',
            ],

            'marital_employment_status' => [
                'nullable',
                'string',
                'max:50',
            ],

            'nationality' => [
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
                'max:50',
            ],

            'alternative_phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'country' => [
                'nullable',
                'string',
                'max:100',
            ],

            'hire_date' => [
                'nullable',
                'date',
            ],

            'probation_end_date' => [
                'nullable',
                'date',
            ],

            'confirmation_date' => [
                'nullable',
                'date',
            ],

            'contract_start_date' => [
                'nullable',
                'date',
            ],

            'contract_end_date' => [
                'nullable',
                'date',
                'after_or_equal:contract_start_date',
            ],

            'employment_type' => [
                'required',
                Rule::in([
                    'permanent',
                    'contract',
                    'temporary',
                    'casual',
                    'intern',
                    'part_time',
                ]),
            ],

            'employment_employment_status' => [
                'required',
                Rule::in([
                    'active',
                    'on_leave',
                    'suspended',
                    'separated',
                ]),
            ],

            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')
                    ->where('company_id', $company->id),
            ],

            'position_id' => [
                'nullable',
                'integer',
                Rule::exists('positions', 'id')
                    ->where('company_id', $company->id),
            ],

            'supervisor_id' => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')
                    ->where('company_id', $company->id),
            ],

            'basic_salary' => [
                'required',
                'numeric',
                'min:0',
            ],

            'salary_currency' => [
                'required',
                'string',
                'size:3',
            ],

            'pay_frequency' => [
                'required',
                Rule::in([
                    'monthly',
                    'weekly',
                    'bi_weekly',
                    'daily',
                ]),
            ],

            'bank_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'bank_branch' => [
                'nullable',
                'string',
                'max:150',
            ],

            'bank_account_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'bank_account_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'bank_branch_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            'tpin' => [
                'nullable',
                'string',
                'max:100',
            ],

            'napsa_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'nhima_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'emergency_contact_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'emergency_contact_relationship' => [
                'nullable',
                'string',
                'max:100',
            ],

            'emergency_contact_phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'emergency_contact_address' => [
                'nullable',
                'string',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        $employee = Employee::create([
            ...$validated,
            'company_id' => $company->id,
            'salary_currency' => strtoupper(
                $validated['salary_currency']
            ),
            'is_active' => true,
        ]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee)
    {
        $company = app(CompanyContext::class)->get();

        abort_unless(
            $company,
            403,
            'No active company selected.'
        );

        /*
        |--------------------------------------------------------------------------
        | Strict Company Isolation
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $employee->company_id === $company->id,
            403,
            'You do not have access to this employee.'
        );

        /*
        |--------------------------------------------------------------------------
        | Load Employee Relationships
        |--------------------------------------------------------------------------
        */

        $employee->load([
            'department',
            'position',
            'supervisor',
            'user',
        ]);

        return Inertia::render('Employees/Show', [
            'employee' => $employee,
        ]);
    }

    public function edit(Employee $employee)
        {
            $company = app(CompanyContext::class)->get();

            abort_unless(
                $company,
                403,
                'No active company selected.'
            );

            abort_unless(
                $employee->company_id === $company->id,
                403,
                'You do not have access to this employee.'
            );

            $employee->load([
                'department',
                'position',
                'supervisor',
            ]);

            $departments = Department::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);

            $positions = Position::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get([
                    'id',
                    'department_id',
                    'name',
                ]);

            $supervisors = Employee::query()
                ->where('company_id', $company->id)
                ->where('id', '!=', $employee->id)
                ->whereIn('employment_employment_status', [
                    'active',
                    'on_leave',
                ])
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get([
                    'id',
                    'employee_number',
                    'first_name',
                    'last_name',
                ]);

            return Inertia::render('Employees/Edit', [
                'employee' => $employee,
                'departments' => $departments,
                'positions' => $positions,
                'supervisors' => $supervisors,
            ]);
        }


        public function update(
            Request $request,
            Employee $employee
        ) {
            $company = app(CompanyContext::class)->get();

            abort_unless(
                $company,
                403,
                'No active company selected.'
            );

            abort_unless(
                $employee->company_id === $company->id,
                403,
                'You do not have access to this employee.'
            );

            $validated = $request->validate([
                'first_name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'middle_name' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'last_name' => [
                    'required',
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

                'gender' => [
                    'nullable',
                    'string',
                    'max:30',
                ],

                'date_of_birth' => [
                    'nullable',
                    'date',
                ],

                'national_id' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'address' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'city' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'country' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'department_id' => [
                    'nullable',
                    'integer',
                    'exists:departments,id',
                ],

                'position_id' => [
                    'nullable',
                    'integer',
                    'exists:positions,id',
                ],

                'supervisor_id' => [
                    'nullable',
                    'integer',
                    'exists:employees,id',
                ],

                'employment_type' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'hire_date' => [
                    'nullable',
                    'date',
                ],

                'contract_start_date' => [
                    'nullable',
                    'date',
                ],

                'contract_end_date' => [
                    'nullable',
                    'date',
                ],

                'salary' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'salary_frequency' => [
                    'nullable',
                    'string',
                    'max:30',
                ],

                'bank_name' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'bank_account_number' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'bank_branch' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'emergency_contact_name' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'emergency_contact_phone' => [
                    'nullable',
                    'string',
                    'max:30',
                ],

                'emergency_contact_relationship' => [
                    'nullable',
                    'string',
                    'max:100',
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Prevent Cross-Company Relationships
            |--------------------------------------------------------------------------
            */

            if (!empty($validated['department_id'])) {
                abort_unless(
                    Department::where('company_id', $company->id)
                        ->whereKey($validated['department_id'])
                        ->exists(),
                    403,
                    'Invalid department.'
                );
            }

            if (!empty($validated['position_id'])) {
                abort_unless(
                    Position::where('company_id', $company->id)
                        ->whereKey($validated['position_id'])
                        ->exists(),
                    403,
                    'Invalid position.'
                );
            }

            if (!empty($validated['supervisor_id'])) {
                abort_unless(
                    Employee::where('company_id', $company->id)
                        ->whereKey($validated['supervisor_id'])
                        ->where('id', '!=', $employee->id)
                        ->exists(),
                    403,
                    'Invalid supervisor.'
                );
            }

            $employee->update($validated);

            return redirect()
                ->route('employees.show', $employee)
                ->with(
                    'success',
                    "{$employee->fullName} was updated successfully."
                );
        }
}