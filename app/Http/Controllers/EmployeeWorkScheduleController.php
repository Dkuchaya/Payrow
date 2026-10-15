<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\WorkSchedule;
use App\Services\WorkScheduleService;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;


class EmployeeWorkScheduleController extends Controller
{
    public function __construct(
        protected CompanyContext $companyContext,
        protected WorkScheduleService $workScheduleService
    ) {}

    /**
     * Show schedule assignments for an employee.
     */
    public function index(
        Request $request,
        Employee $employee
    ): Response {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess($request, $company);

        abort_unless(
            (int) $employee->company_id === (int) $company->id,
            404
        );

        $assignments = $employee->workScheduleAssignments()
            ->with('workSchedule')
            ->orderByDesc('effective_from')
            ->get()
           ->map(function ($assignment) {
            $today = now()->startOfDay();

            $effectiveFrom = $assignment->effective_from?->copy()->startOfDay();
            $effectiveTo = $assignment->effective_to?->copy()->startOfDay();

           if ($effectiveFrom && $effectiveFrom->gt($today)) {
                $status = 'Scheduled';
            } elseif (
                $effectiveTo &&
                $effectiveTo->lte($today)
            ) {
                $status = 'Ended';
            } else {
                $status = 'Active';
            }

            return [
                'id' => $assignment->id,
                'work_schedule_id' => $assignment->work_schedule_id,

                'work_schedule' => $assignment->workSchedule ? [
                    'id' => $assignment->workSchedule->id,
                    'name' => $assignment->workSchedule->name,
                    'code' => $assignment->workSchedule->code,
                ] : null,

                'effective_from' => $effectiveFrom?->format('Y-m-d'),
                'effective_to' => $effectiveTo?->format('Y-m-d'),

                'status' => $status,

                'is_primary' => (bool) $assignment->is_primary,

                'notes' => $assignment->notes,
            ];
        });
        $workSchedules = WorkSchedule::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
            ]);

        return Inertia::render(
            'Employees/WorkSchedules/Index',
            [
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                ],

                'employee' => [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'first_name' => $employee->first_name,
                    'middle_name' => $employee->middle_name,
                    'last_name' => $employee->last_name,
                ],

                'assignments' => $assignments,

                'workSchedules' => $workSchedules,
            ]
        );
    }

    /**
     * Assign a work schedule to an employee.
     */
    public function store(
        Request $request,
        Employee $employee
    ): RedirectResponse {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess($request, $company);

        abort_unless(
            (int) $employee->company_id === (int) $company->id,
            404
        );

        $validated = $request->validate([
            'work_schedule_id' => [
                'required',
                'integer',
                Rule::exists('work_schedules', 'id')
                    ->where('company_id', $company->id),
            ],

            'effective_from' => [
                'required',
                'date',
            ],

            'effective_to' => [
                'nullable',
                'date',
                'after_or_equal:effective_from',
            ],

            'is_primary' => [
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $schedule = WorkSchedule::query()
            ->where('company_id', $company->id)
            ->findOrFail($validated['work_schedule_id']);

        try {
            $this->workScheduleService->assignSchedule(
                $company,
                $employee,
                $schedule,
                [
                    'effective_from' => $validated['effective_from'],
                    'effective_to' => $validated['effective_to'] ?? null,
                    'is_primary' => $validated['is_primary'] ?? true,
                    'notes' => $validated['notes'] ?? null,
                    'assigned_by' => $request->user()->id,
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->back()
            ->with(
                'success',
                'Work schedule assigned successfully.'
            );
    }

    /**
     * Authorize access to the active company.
     */
    protected function authorizeCompanyAccess(
        Request $request,
        Company $company
    ): void {
        $user = $request->user();

        /*
         * Super Admin bypasses company membership checks.
         */
        if ($user->is_super_admin) {
            return;
        }

        /*
         * User must belong to the active company.
         */
        abort_unless(
            $user->companies()
                ->where('companies.id', $company->id)
                ->where('companies.is_active', true)
                ->wherePivot('is_active', true)
                ->exists(),
            403,
            'You do not have access to this company.'
        );

        /*
         * Schedule management requires attendance.manage.
         */
        abort_unless(
            $user->can('attendance.manage'),
            403,
            'You do not have permission to manage attendance.'
        );
    }

    /**
 * Show the edit form for an employee work schedule assignment.
 */
public function edit(
    Request $request,
    Employee $employee,
    int $assignment
): Response {
    $company = $this->companyContext->get();

    abort_unless(
        $company,
        400,
        'No active company selected.'
    );

    $this->authorizeCompanyAccess($request, $company);

    abort_unless(
        (int) $employee->company_id === (int) $company->id,
        404
    );

    $workScheduleAssignment = $employee
        ->workScheduleAssignments()
        ->with('workSchedule')
        ->where('id', $assignment)
        ->firstOrFail();

    $workSchedules = WorkSchedule::query()
        ->where('company_id', $company->id)
        ->where('is_active', true)
        ->orderBy('name')
        ->get([
            'id',
            'name',
            'code',
        ]);

    return Inertia::render(
        'Employees/WorkSchedules/Edit',
        [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],

            'employee' => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'first_name' => $employee->first_name,
                'middle_name' => $employee->middle_name,
                'last_name' => $employee->last_name,
            ],

            'assignment' => [
                'id' => $workScheduleAssignment->id,
                'work_schedule_id' => $workScheduleAssignment->work_schedule_id,
                'work_schedule' => $workScheduleAssignment->workSchedule ? [
                    'id' => $workScheduleAssignment->workSchedule->id,
                    'name' => $workScheduleAssignment->workSchedule->name,
                    'code' => $workScheduleAssignment->workSchedule->code,
                ] : null,
                'effective_from' => $workScheduleAssignment->effective_from?->format('Y-m-d'),
                'effective_to' => $workScheduleAssignment->effective_to?->format('Y-m-d'),
                'is_primary' => (bool) $workScheduleAssignment->is_primary,
                'notes' => $workScheduleAssignment->notes,
            ],

            'workSchedules' => $workSchedules,
        ]
    );
}

    /**
     * Update an employee work schedule assignment.
     */
    public function update(
        Request $request,
        Employee $employee,
        int $assignment
    ): RedirectResponse {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess($request, $company);

        abort_unless(
            (int) $employee->company_id === (int) $company->id,
            404
        );

        $workScheduleAssignment = $employee
            ->workScheduleAssignments()
            ->where('id', $assignment)
            ->firstOrFail();

        $validated = $request->validate([
            'work_schedule_id' => [
                'required',
                'integer',
                Rule::exists('work_schedules', 'id')
                    ->where('company_id', $company->id),
            ],

            'effective_from' => [
                'required',
                'date',
            ],

            'effective_to' => [
                'nullable',
                'date',
                'after_or_equal:effective_from',
            ],

            'is_primary' => [
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $schedule = WorkSchedule::query()
            ->where('company_id', $company->id)
            ->findOrFail($validated['work_schedule_id']);

        try {
            $this->workScheduleService->updateScheduleAssignment(
                $company,
                $employee,
                $workScheduleAssignment,
                $schedule,
                [
                    'effective_from' => $validated['effective_from'],
                    'effective_to' => $validated['effective_to'] ?? null,
                    'is_primary' => $validated['is_primary'] ?? true,
                    'notes' => $validated['notes'] ?? null,
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route(
                'employees.work-schedules.index',
                $employee->id
            )
            ->with(
                'success',
                'Work schedule assignment updated successfully.'
            );
    }

    /**
 * End an employee's work schedule assignment.
 */
        public function end(
            Request $request,
            Employee $employee,
            int $assignment
        ): RedirectResponse {
            $company = $this->companyContext->get();

            abort_unless(
                $company,
                400,
                'No active company selected.'
            );

            $this->authorizeCompanyAccess($request, $company);

            abort_unless(
                (int) $employee->company_id === (int) $company->id,
                404
            );

            $workScheduleAssignment = $employee
                ->workScheduleAssignments()
                ->where('id', $assignment)
                ->firstOrFail();

            $validated = $request->validate([
                'effective_to' => [
                    'required',
                    'date',
                ],
            ]);

            $effectiveTo = \Carbon\Carbon::parse(
                $validated['effective_to']
            )->startOfDay();

            $effectiveFrom = \Carbon\Carbon::parse(
                $workScheduleAssignment->effective_from
            )->startOfDay();

            if ($effectiveTo->lt($effectiveFrom)) {
                return redirect()
                    ->back()
                    ->with(
                        'error',
                        'The end date cannot be before the schedule start date.'
                    );
            }

            if (
                $workScheduleAssignment->effective_to &&
                $effectiveTo->gt(
                    \Carbon\Carbon::parse(
                        $workScheduleAssignment->effective_to
                    )->startOfDay()
                )
            ) {
                return redirect()
                    ->back()
                    ->with(
                        'error',
                        'The selected end date cannot be after the existing end date.'
                    );
            }

            $workScheduleAssignment->update([
                'effective_to' => $effectiveTo,
            ]);

            return redirect()
                ->route(
                    'employees.work-schedules.index',
                    $employee->id
                )
                ->with(
                    'success',
                    'Work schedule assignment ended successfully.'
                );
        }

        /**
 * Cancel a scheduled work schedule assignment.
 */
public function cancel(
    Request $request,
    Employee $employee,
    int $assignment
): RedirectResponse {
    $company = $this->companyContext->get();

    abort_unless(
        $company,
        400,
        'No active company selected.'
    );

    $this->authorizeCompanyAccess($request, $company);

    abort_unless(
        (int) $employee->company_id === (int) $company->id,
        404
    );

    $workScheduleAssignment = $employee
        ->workScheduleAssignments()
        ->where('id', $assignment)
        ->firstOrFail();

    $effectiveFrom = Carbon::parse(
        $workScheduleAssignment->effective_from
    )->startOfDay();

    if ($effectiveFrom->lte(now()->startOfDay())) {
        return redirect()
            ->back()
            ->with(
                'error',
                'This schedule has already started and cannot be cancelled. Please end the schedule instead.'
            );
    }

    $workScheduleAssignment->delete();

    return redirect()
        ->route(
            'employees.work-schedules.index',
            $employee->id
        )
        ->with(
            'success',
            'Scheduled work schedule cancelled successfully.'
        );
}
}