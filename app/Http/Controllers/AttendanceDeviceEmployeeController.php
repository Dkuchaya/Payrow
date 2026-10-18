<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDevice;
use App\Models\AttendanceDeviceEmployee;
use App\Models\Company;
use App\Models\Employee;
use App\Services\Attendance\AttendanceDeviceEmployeeService;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class AttendanceDeviceEmployeeController extends Controller
{
    public function __construct(
        protected CompanyContext $companyContext,
        protected AttendanceDeviceEmployeeService $mappingService
    ) {}

    public function index(
        Request $request,
        AttendanceDevice $attendanceDevice
    ): Response {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess(
            $request,
            $company
        );

        abort_unless(
            (int) $attendanceDevice->company_id === (int) $company->id,
            404
        );

        $query = AttendanceDeviceEmployee::query()
            ->where('company_id', $company->id)
            ->where(
                'attendance_device_id',
                $attendanceDevice->id
            )
            ->with('employee')
            ->orderByDesc('is_active')
            ->orderBy('id');

        if ($request->filled('search')) {
            $search = trim(
                $request->input('search')
            );

            $query->where(function ($q) use ($search) {
                $q->where(
                    'device_user_id',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'device_username',
                    'like',
                    "%{$search}%"
                )
                ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                    $employeeQuery
                        ->where(
                            'employee_number',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'first_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'last_name',
                            'like',
                            "%{$search}%"
                        );
                });
            });
        }

        if ($request->filled('status')) {
            $query->where(
                'is_active',
                $request->input('status') === 'active'
            );
        }

        $mappings = $query
            ->paginate(20)
            ->withQueryString();

        return Inertia::render(
            'AttendanceDevices/Mappings/Index',
            [
                'company' => $company,

                'device' => [
                    'id' => $attendanceDevice->id,
                    'name' => $attendanceDevice->name,
                    'manufacturer' => $attendanceDevice->manufacturer,
                    'model' => $attendanceDevice->model,
                    'serial_number' => $attendanceDevice->serial_number,
                ],

                'mappings' => $mappings,

                'filters' => [
                    'search' => $request->input('search'),
                    'status' => $request->input('status'),
                ],
            ]
        );
    }

    public function create(
        Request $request,
        AttendanceDevice $attendanceDevice
    ): Response {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess(
            $request,
            $company
        );

        abort_unless(
            (int) $attendanceDevice->company_id === (int) $company->id,
            404
        );

        $mappedEmployeeIds = AttendanceDeviceEmployee::query()
            ->where(
                'attendance_device_id',
                $attendanceDevice->id
            )
            ->pluck('employee_id');

        $employees = Employee::query()
            ->where('company_id', $company->id)
            ->where('employment_status', 'active')
            ->whereNotIn('id', $mappedEmployeeIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get([
                'id',
                'employee_number',
                'first_name',
                'last_name',
            ]);

        return Inertia::render(
            'AttendanceDevices/Mappings/Create',
            [
                'company' => $company,

                'device' => [
                    'id' => $attendanceDevice->id,
                    'name' => $attendanceDevice->name,
                    'manufacturer' => $attendanceDevice->manufacturer,
                    'model' => $attendanceDevice->model,
                ],

                'employees' => $employees,
            ]
        );
    }

    public function store(
        Request $request,
        AttendanceDevice $attendanceDevice
    ): RedirectResponse {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess(
            $request,
            $company
        );

        abort_unless(
            (int) $attendanceDevice->company_id === (int) $company->id,
            404
        );

        $validated = $request->validate([
            'employee_id' => [
                'required',
                'integer',
            ],

            'device_user_id' => [
                'required',
                'string',
                'max:150',
            ],

            'device_username' => [
                'nullable',
                'string',
                'max:150',
            ],

            'is_active' => [
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        try {
            $this->mappingService->create(
                $company,
                $attendanceDevice,
                $validated
            );
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }

        return redirect()
            ->route(
                'attendance-devices.mappings.index',
                $attendanceDevice
            )
            ->with(
                'success',
                'Employee mapping created successfully.'
            );
    }

    public function edit(
        Request $request,
        AttendanceDevice $attendanceDevice,
        AttendanceDeviceEmployee $mapping
    ): Response {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess(
            $request,
            $company
        );

        abort_unless(
            (int) $attendanceDevice->company_id === (int) $company->id,
            404
        );

        abort_unless(
            (int) $mapping->company_id === (int) $company->id,
            404
        );

        abort_unless(
            (int) $mapping->attendance_device_id ===
                (int) $attendanceDevice->id,
            404
        );

        $employees = Employee::query()
            ->where('company_id', $company->id)
            ->where(function ($query) use ($mapping) {
                $query->where('employment_status', 'active')
                    ->orWhereKey($mapping->employee_id);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get([
                'id',
                'employee_number',
                'first_name',
                'last_name',
            ]);

        $mapping->load('employee');

        return Inertia::render(
            'AttendanceDevices/Mappings/Edit',
            [
                'company' => $company,

                'device' => [
                    'id' => $attendanceDevice->id,
                    'name' => $attendanceDevice->name,
                    'manufacturer' => $attendanceDevice->manufacturer,
                    'model' => $attendanceDevice->model,
                ],

                'mapping' => $mapping,

                'employees' => $employees,
            ]
        );
    }

    public function update(
        Request $request,
        AttendanceDevice $attendanceDevice,
        AttendanceDeviceEmployee $mapping
    ): RedirectResponse {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess(
            $request,
            $company
        );

        abort_unless(
            (int) $attendanceDevice->company_id === (int) $company->id,
            404
        );

        abort_unless(
            (int) $mapping->company_id === (int) $company->id,
            404
        );

        abort_unless(
            (int) $mapping->attendance_device_id ===
                (int) $attendanceDevice->id,
            404
        );

        $validated = $request->validate([
            'employee_id' => [
                'required',
                'integer',
            ],

            'device_user_id' => [
                'required',
                'string',
                'max:150',
            ],

            'device_username' => [
                'nullable',
                'string',
                'max:150',
            ],

            'is_active' => [
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        try {
            $this->mappingService->update(
                $company,
                $mapping,
                $validated
            );
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }

        return redirect()
            ->route(
                'attendance-devices.mappings.index',
                $attendanceDevice
            )
            ->with(
                'success',
                'Employee mapping updated successfully.'
            );
    }

    public function toggle(
        Request $request,
        AttendanceDevice $attendanceDevice,
        AttendanceDeviceEmployee $mapping
    ): RedirectResponse {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess(
            $request,
            $company
        );

        abort_unless(
            (int) $attendanceDevice->company_id === (int) $company->id,
            404
        );

        abort_unless(
            (int) $mapping->company_id === (int) $company->id,
            404
        );

        abort_unless(
            (int) $mapping->attendance_device_id ===
                (int) $attendanceDevice->id,
            404
        );

        $this->mappingService->toggle(
            $company,
            $mapping
        );

        return back()->with(
            'success',
            $mapping->is_active
                ? 'Employee mapping activated.'
                : 'Employee mapping deactivated.'
        );
    }

    protected function authorizeCompanyAccess(
        Request $request,
        Company $company
    ): void {
        $user = $request->user();

        abort_unless($user, 401);

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
