<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSeparation;
use App\Models\EmployeeSeparationClearance;
use App\Models\Department;
use App\Support\CompanyContext;
use App\Services\EmployeeClearanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeSeparationClearanceController extends Controller
{
    protected function company()
    {
        $company = app(CompanyContext::class)->get();

        abort_unless(
            $company,
            403,
            'No active company selected.'
        );

        return $company;
    }

    protected function ensureSeparationAccess(
        EmployeeSeparation $separation
    ): void {
        $company = $this->company();

        abort_unless(
            $separation->company_id === $company->id,
            403,
            'You do not have access to this separation.'
        );
    }

    protected function ensureClearanceAccess(
        EmployeeSeparationClearance $clearance
    ): void {
        $company = $this->company();

        abort_unless(
            $clearance->company_id === $company->id,
            403,
            'You do not have access to this clearance.'
        );
    }

    public function index(EmployeeSeparation $separation)
    {
        $this->ensureSeparationAccess($separation);

        $separation->load([
            'employee.department',
            'employee.position',
            'employee.supervisor',
        ]);

        $clearances = $separation->clearances()
            ->with([
                'department',
                'responsibleUser',
                'clearedBy',
            ])
            ->orderBy('is_required', 'desc')
            ->orderBy('category')
            ->orderBy('title')
            ->get();

        $stats = [
            'total' => $clearances->count(),

            'pending' => $clearances
                ->where('status', 'pending')
                ->count(),

            'cleared' => $clearances
                ->where('status', 'cleared')
                ->count(),

            'rejected' => $clearances
                ->where('status', 'rejected')
                ->count(),

            'required' => $clearances
                ->where('is_required', true)
                ->count(),

            'required_cleared' => $clearances
                ->where('is_required', true)
                ->where('status', 'cleared')
                ->count(),

            'complete' => $clearances
                ->where('is_required', true)
                ->where('status', '!=', 'cleared')
                ->isEmpty(),
        ];

        $departments = Department::query()
            ->where('company_id', $this->company()->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render(
            'Employees/Separation/Clearance/Index',
            [
                'separation' => $separation,
                'clearances' => $clearances,
                'stats' => $stats,
                'departments' => $departments,
            ]
        );
    }

    public function clear(
        Request $request,
        EmployeeSeparationClearance $clearance,
        EmployeeClearanceService $service
    ) {
        $this->ensureClearanceAccess($clearance);

        $validated = $request->validate([
            'comments' => ['nullable', 'string', 'max:5000'],
        ]);

        abort_unless(
            $clearance->status !== 'cleared',
            422,
            'This clearance has already been completed.'
        );

        $service->clear(
            $clearance,
            $request->user()->id,
            $validated['comments'] ?? null
        );

        return back()->with(
            'success',
            'Clearance item completed successfully.'
        );
    }

    public function reject(
        Request $request,
        EmployeeSeparationClearance $clearance,
        EmployeeClearanceService $service
    ) {
        $this->ensureClearanceAccess($clearance);

        $validated = $request->validate([
            'comments' => ['required', 'string', 'max:5000'],
        ]);

        abort_unless(
            $clearance->status !== 'cleared',
            422,
            'A completed clearance cannot be rejected.'
        );

        $service->reject(
            $clearance,
            $request->user()->id,
            $validated['comments']
        );

        return back()->with(
            'success',
            'Clearance item rejected.'
        );
    }

    public function update(
        Request $request,
        EmployeeSeparationClearance $clearance
    ) {
        $this->ensureClearanceAccess($clearance);

        $company = $this->company();

        $validated = $request->validate([
            'department_id' => [
                'nullable',
                'integer',
                'exists:departments,id',
            ],
            'responsible_user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'is_required' => [
                'required',
                'boolean',
            ],
        ]);

        if ($validated['department_id'] ?? null) {
            $departmentExists = Department::query()
                ->where('id', $validated['department_id'])
                ->where('company_id', $company->id)
                ->exists();

            abort_unless(
                $departmentExists,
                422,
                'The selected department does not belong to this company.'
            );
        }

        $clearance->update([
            'department_id' => $validated['department_id'] ?? null,
            'responsible_user_id' => $validated['responsible_user_id'] ?? null,
            'is_required' => $validated['is_required'],
        ]);

        return back()->with(
            'success',
            'Clearance assignment updated.'
        );
    }
}