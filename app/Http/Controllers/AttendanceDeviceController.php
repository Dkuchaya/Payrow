<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDevice;
use App\Support\CompanyContext;
use App\Services\Attendance\AttendanceDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceDeviceController extends Controller
{
    public function __construct(
        protected CompanyContext $companyContext,
        protected AttendanceDeviceService $deviceService
    ) {
    }

    public function index(Request $request): Response
    {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess($request, $company);

        $query = AttendanceDevice::query()
            ->where('company_id', $company->id)
            ->withCount('punches')
            ->withCount([
                'employeeMappings as active_employee_mappings_count' => function ($query) {
                    $query->where('is_active', true);
                },
            ])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('manufacturer')) {
            $query->where(
                'manufacturer',
                $request->input('manufacturer')
            );
        }

        if ($request->filled('is_active')) {
            $query->where(
                'is_active',
                filter_var(
                    $request->input('is_active'),
                    FILTER_VALIDATE_BOOLEAN
                )
            );
        }

        $devices = $query
            ->paginate(20)
            ->withQueryString();

        $manufacturers = AttendanceDevice::query()
            ->where('company_id', $company->id)
            ->whereNotNull('manufacturer')
            ->where('manufacturer', '!=', '')
            ->distinct()
            ->orderBy('manufacturer')
            ->pluck('manufacturer');

        return Inertia::render('AttendanceDevices/Index', [
            'company' => $company,
            'devices' => $devices,
            'manufacturers' => $manufacturers,
            'filters' => [
                'search' => $request->input('search'),
                'manufacturer' => $request->input('manufacturer'),
                'is_active' => $request->input('is_active'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess($request, $company);

        return Inertia::render('AttendanceDevices/Create', [
            'company' => $company,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess($request, $company);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'device_type' => [
                'required',
                'string',
                'max:50',
            ],

            'manufacturer' => [
                'nullable',
                'string',
                'max:100',
            ],

            'model' => [
                'nullable',
                'string',
                'max:100',
            ],

            'serial_number' => [
                'nullable',
                'string',
                'max:150',
            ],

            'connection_type' => [
                'required',
                'string',
                'max:30',
            ],

            'ip_address' => [
                'nullable',
                'ip',
            ],

            'port' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],

            'api_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'username' => [
                'nullable',
                'string',
                'max:255',
            ],

            'password' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'timezone' => [
                'required',
                'timezone',
            ],

            'sync_method' => [
                'required',
                'string',
                'max:30',
            ],

            'is_active' => [
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $this->deviceService->create(
            $company,
            $validated
        );

        return redirect()
            ->route('attendance-devices.index')
            ->with(
                'success',
                'Attendance device created successfully.'
            );
    }

    public function edit(
        Request $request,
        AttendanceDevice $attendanceDevice
    ): Response {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess($request, $company);

        abort_unless(
            (int) $attendanceDevice->company_id === (int) $company->id,
            404
        );

        return Inertia::render('AttendanceDevices/Edit', [
            'company' => $company,
            'device' => $attendanceDevice,
        ]);
    }

    public function update(
        Request $request,
        AttendanceDevice $attendanceDevice
    ): RedirectResponse {
        $company = $this->companyContext->get();

        abort_unless(
            $company,
            400,
            'No active company selected.'
        );

        $this->authorizeCompanyAccess($request, $company);

        abort_unless(
            (int) $attendanceDevice->company_id === (int) $company->id,
            404
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'device_type' => [
                'required',
                'string',
                'max:50',
            ],

            'manufacturer' => [
                'nullable',
                'string',
                'max:100',
            ],

            'model' => [
                'nullable',
                'string',
                'max:100',
            ],

            'serial_number' => [
                'nullable',
                'string',
                'max:150',
            ],

            'connection_type' => [
                'required',
                'string',
                'max:30',
            ],

            'ip_address' => [
                'nullable',
                'ip',
            ],

            'port' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],

            'api_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'username' => [
                'nullable',
                'string',
                'max:255',
            ],

            'password' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'timezone' => [
                'required',
                'timezone',
            ],

            'sync_method' => [
                'required',
                'string',
                'max:30',
            ],

            'is_active' => [
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $this->deviceService->update(
            $attendanceDevice,
            $validated
        );

        return redirect()
            ->route('attendance-devices.index')
            ->with(
                'success',
                'Attendance device updated successfully.'
            );
    }

    public function show(
    Request $request,
    AttendanceDevice $attendanceDevice
): Response {
    $company = $this->companyContext->get();

    abort_unless(
        $company,
        400,
        'No active company selected.'
    );

    $this->authorizeCompanyAccess($request, $company);

    abort_unless(
        (int) $attendanceDevice->company_id === (int) $company->id,
        404
    );

    $attendanceDevice->load([
        'employeeMappings.employee',
    ]);

    $recentPunches = $attendanceDevice->punches()
        ->with('employee')
        ->orderByDesc('punched_at')
        ->limit(20)
        ->get();

    return Inertia::render('AttendanceDevices/Show', [
        'company' => $company,
        'device' => $attendanceDevice,
        'recentPunches' => $recentPunches,
    ]);
}

public function testConnection(
    Request $request,
    AttendanceDevice $attendanceDevice
): RedirectResponse {
    $company = $this->companyContext->get();

    abort_unless(
        $company,
        400,
        'No active company selected.'
    );

    $this->authorizeCompanyAccess($request, $company);

    abort_unless(
        (int) $attendanceDevice->company_id === (int) $company->id,
        404
    );

    try {
        $connected = $this->deviceService->testConnection(
            $attendanceDevice
        );

        return redirect()
            ->back()
            ->with(
                $connected ? 'success' : 'error',
                $connected
                    ? 'Attendance device connection successful.'
                    : 'Unable to connect to the attendance device.'
            );
    } catch (\Throwable $e) {
        return redirect()
            ->back()
            ->with(
                'error',
                $e->getMessage()
            );
    }
}

public function sync(
    Request $request,
    AttendanceDevice $attendanceDevice
): RedirectResponse {
    $company = $this->companyContext->get();

    abort_unless(
        $company,
        400,
        'No active company selected.'
    );

    $this->authorizeCompanyAccess($request, $company);

    abort_unless(
        (int) $attendanceDevice->company_id === (int) $company->id,
        404
    );

    abort_if(
        !$attendanceDevice->is_active,
        422,
        'This attendance device is inactive.'
    );

    try {
        $count = $this->deviceService->sync(
            $attendanceDevice
        );

        return redirect()
            ->back()
            ->with(
                'success',
                "{$count} attendance punch(es) synchronized successfully."
            );
    } catch (\Throwable $e) {
        return redirect()
            ->back()
            ->with(
                'error',
                $e->getMessage()
            );
    }
}

public function toggle(
    Request $request,
    AttendanceDevice $attendanceDevice
): RedirectResponse {
    $company = $this->companyContext->get();

    abort_unless(
        $company,
        400,
        'No active company selected.'
    );

    $this->authorizeCompanyAccess($request, $company);

    abort_unless(
        (int) $attendanceDevice->company_id === (int) $company->id,
        404
    );

    $attendanceDevice->update([
        'is_active' => !$attendanceDevice->is_active,
    ]);

    return redirect()
        ->back()
        ->with(
            'success',
            $attendanceDevice->is_active
                ? 'Attendance device activated.'
                : 'Attendance device deactivated.'
        );
}

    protected function authorizeCompanyAccess(
        Request $request,
        $company
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
            'You do not have permission to manage attendance devices.'
        );
    }
}