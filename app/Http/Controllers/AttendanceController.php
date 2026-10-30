<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Services\AttendanceService;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class AttendanceController extends Controller
{
    public function __construct(
        protected CompanyContext $companyContext,
        protected AttendanceService $attendanceService
    ) {
    }

    /**
     * Attendance records.
     */
    public function index(Request $request): Response
    {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess($request, $company);

        $query = AttendanceRecord::query()
            ->where('company_id', $company->id)
            ->with([
                'employee',
                'device',
            ])
            ->orderByDesc('attendance_date')
            ->orderByDesc('id');

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        // Backwards compatibility with the existing single-date filter.
        if (
            $request->filled('date')
            && !$request->filled('date_from')
            && !$request->filled('date_to')
        ) {
            $query->whereDate(
                'attendance_date',
                $request->input('date')
            );
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'attendance_date',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'attendance_date',
                '<=',
                $request->input('date_to')
            );
        }

        if ($request->filled('employee_id')) {
            $query->where(
                'employee_id',
                $request->input('employee_id')
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        if ($request->filled('source')) {
            $query->where(
                'source',
                $request->input('source')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

       $summary = (clone $query)
    ->reorder()
    ->selectRaw("
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status = 'incomplete' THEN 1 ELSE 0 END) as incomplete,
        COALESCE(SUM(overtime_hours), 0) as overtime_hours
    ")
    ->first();

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $attendance = $query
            ->paginate(20)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Employees
        |--------------------------------------------------------------------------
        */

        $employees = Employee::query()
            ->where('company_id', $company->id)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get([
                'id',
                'employee_number',
                'first_name',
                'last_name',
            ]);

        return Inertia::render(
            'Attendance/Index',
            [
                'company' => $company,

                'attendance' => $attendance,

                'employees' => $employees,

                'summary' => [
                    'present' => (int) ($summary->present ?? 0),
                    'late' => (int) ($summary->late ?? 0),
                    'absent' => (int) ($summary->absent ?? 0),
                    'incomplete' => (int) ($summary->incomplete ?? 0),
                    'overtime_hours' => round(
                        (float) ($summary->overtime_hours ?? 0),
                        2
                    ),
                ],

                'filters' => [
                    'date' => $request->input('date'),
                    'date_from' => $request->input('date_from'),
                    'date_to' => $request->input('date_to'),
                    'employee_id' => $request->input('employee_id'),
                    'status' => $request->input('status'),
                    'source' => $request->input('source'),
                ],
            ]
        );
    }

    /**
     * Show manual attendance form.
     */
    public function create(Request $request): Response
    {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess($request, $company);

        $employees = Employee::query()
            ->where('company_id', $company->id)
            ->where('employment_status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get([
                'id',
                'employee_number',
                'first_name',
                'last_name',
                'employment_status',
            ]);

        return Inertia::render(
            'Attendance/Create',
            [
                'company' => $company,
                'employees' => $employees,
            ]
        );
    }

    /**
     * Store manual attendance.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess($request, $company);

        $validated = $request->validate([
            'employee_id' => [
                'required',
                'integer',
            ],

            'attendance_date' => [
                'required',
                'date',
            ],

            'clock_in_at' => [
                'nullable',
                'date',
            ],

            'clock_out_at' => [
                'nullable',
                'date',
            ],

            'status' => [
                'nullable',
                'string',
                'max:50',
            ],

            'adjustment_reason' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $employee = Employee::query()
            ->where('company_id', $company->id)
            ->findOrFail(
                $validated['employee_id']
            );

        try {
            $this->attendanceService->saveManualAttendance(
                $company,
                $employee,
                [
                    'attendance_date' =>
                        $validated['attendance_date'],

                    'clock_in_at' =>
                        $validated['clock_in_at'] ?? null,

                    'clock_out_at' =>
                        $validated['clock_out_at'] ?? null,

                    'status' =>
                        $validated['status'] ?? 'present',

                    'adjustment_reason' =>
                        $validated['adjustment_reason'] ?? null,

                    'notes' =>
                        $validated['notes'] ?? null,
                ]
            );
        } catch (InvalidArgumentException $e) {
            return Redirect::back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }

        return Redirect::route(
            'attendance.index'
        )->with(
            'success',
            'Attendance recorded successfully.'
        );
    }

    public function edit(
        Request $request,
        AttendanceRecord $attendance
    ): Response {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess($request, $company);

        abort_unless(
            (int) $attendance->company_id === (int) $company->id,
            404
        );

        $employee = Employee::query()
            ->where('company_id', $company->id)
            ->where('employment_status', 'active')
            ->findOrFail($attendance->employee_id);

        return Inertia::render(
            'Attendance/Edit',
            [
                'company' => $company,
                'attendance' => $attendance->load('employee'),
                'employee' => $employee,
            ]
        );
    }

    public function update(
        Request $request,
        AttendanceRecord $attendance
    ): RedirectResponse {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess($request, $company);

        abort_unless(
            (int) $attendance->company_id === (int) $company->id,
            404
        );

        $validated = $request->validate([
            'attendance_date' => [
                'required',
                'date',
            ],

            'clock_in_at' => [
                'nullable',
                'date',
            ],

            'clock_out_at' => [
                'nullable',
                'date',
            ],

            'status' => [
                'required',
                'string',
                'max:50',
            ],

            'adjustment_reason' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        try {
            $this->attendanceService->updateManualAttendance(
                $company,
                $attendance,
                $validated
            );
        } catch (InvalidArgumentException $e) {
            return Redirect::back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }

        return Redirect::route(
            'attendance.index'
        )->with(
            'success',
            'Attendance updated successfully.'
        );
    }


    public function show(
    Request $request,
    int $attendance
): Response {
    $company = $this->companyContext->get();

    abort_unless($company, 400, 'No active company selected.');

    $this->authorizeCompanyAccess($request, $company);

    $record = AttendanceRecord::query()
        ->where('company_id', $company->id)
        ->with([
            'employee',
            'device',
            'adjustedBy',
        ])
        ->findOrFail($attendance);

    return Inertia::render('Attendance/Show', [
        'company' => $company,
        'record' => $record,
    ]);
}

    /**
     * Company authorization.
     */
    protected function authorizeCompanyAccess(
        Request $request,
        Company $company
    ): void {
        $user = $request->user();

        abort_unless(
            $user,
            401
        );

        if ($user->is_super_admin) {
            return;
        }

        abort_unless(
            $user->companies()
                ->whereKey($company->id)
                ->exists(),
            403,
            'You do not have access to this company.'
        );

        abort_unless(
            $user->can('attendance.manage'),
            403,
            'You do not have permission to manage attendance.'
        );
    }
}