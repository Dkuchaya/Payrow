<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSeparation;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\EmployeeClearanceService;
use App\Services\EmployeeFinalSettlementService;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EmployeeSeparationManagementController extends Controller
{
    private function company()
    {
        $company = app(CompanyContext::class)->get();

        abort_unless($company, 403, 'No active company selected.');

        return $company;
    }

    private function ensureCompanyAccess(EmployeeSeparation $separation): void
    {
        $company = $this->company();

        abort_unless(
            $separation->company_id === $company->id,
            403,
            'You do not have access to this separation request.'
        );
    }

    public function index(Request $request)
    {
        $company = $this->company();

        $search = trim((string) $request->input('search'));
        $status = $request->input('status', 'all');

        $query = EmployeeSeparation::query()
            ->where('company_id', $company->id)
            ->with([
                'employee.department',
                'employee.position',
                'creator',
                'approver',
            ])
            ->latest();

        if ($search !== '') {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('employee_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $separations = $query
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => EmployeeSeparation::where('company_id', $company->id)->count(),

            'pending' => EmployeeSeparation::where('company_id', $company->id)
                ->where('status', 'pending')
                ->count(),

            'approved' => EmployeeSeparation::where('company_id', $company->id)
                ->where('status', 'approved')
                ->count(),

            'clearance' => EmployeeSeparation::where('company_id', $company->id)
                ->where('status', 'clearance')
                ->count(),

            'final_payroll' => EmployeeSeparation::where('company_id', $company->id)
                ->where('status', 'final_payroll')
                ->count(),

            'completed' => EmployeeSeparation::where('company_id', $company->id)
                ->where('status', 'completed')
                ->count(),
        ];

        return Inertia::render('Employees/Separation/Index', [
            'separations' => $separations,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

   public function show(EmployeeSeparation $separation)
{
    $this->ensureCompanyAccess($separation);

    $separation->load([
        'employee.department',
        'employee.position',
        'employee.supervisor',
        'creator',
        'approver',
        'clearances',
        'finalSettlement',
    ]);

    $requiredClearances = $separation->clearances
        ->where('is_required', true);

    $clearanceComplete =
        $requiredClearances->isNotEmpty()
        && $requiredClearances->every(
            fn ($clearance) => $clearance->status === 'cleared'
        );

    $finalSettlementStatus = $separation->finalSettlement?->status;
    
    return Inertia::render('Employees/Separation/Show', [
        'separation' => $separation,
        'clearanceComplete' => $clearanceComplete,
        'finalSettlementStatus' => $finalSettlementStatus,
    ]);
}

    public function approve(Request $request, EmployeeSeparation $separation)
    {
        $this->ensureCompanyAccess($separation);

        abort_unless(
            $separation->status === 'pending',
            422,
            'Only pending separation requests can be approved.'
        );

        $separation->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with(
            'success',
            'Separation request approved successfully.'
        );
    }

    public function startClearance(
    EmployeeSeparation $separation,
    EmployeeClearanceService $clearanceService
) {
    $this->ensureCompanyAccess($separation);

    if ($separation->status !== 'approved') {
        return back()->with(
            'error',
            'Only approved separation requests can enter clearance.'
        );
    }

    DB::transaction(function () use ($separation, $clearanceService) {
        $separation->update([
            'status' => 'clearance',
        ]);

        $clearanceService->createDefaultClearances($separation);
    });

    return back()->with(
        'success',
        'Employee separation has entered clearance.'
    );
}

  public function sendToFinalPayroll(
    EmployeeSeparation $separation,
    EmployeeClearanceService $clearanceService,
    EmployeeFinalSettlementService $settlementService
) {
    $this->ensureCompanyAccess($separation);

    if ($separation->status !== 'clearance') {
        return back()->with(
            'error',
            'Only separations in clearance can proceed to final payroll.'
        );
    }

    if (! $clearanceService->canProceedToFinalPayroll($separation)) {
        return back()->with(
            'error',
            'All required clearance items must be completed before final payroll.'
        );
    }

    DB::transaction(function () use (
        $separation,
        $settlementService
    ) {
        $separation->update([
            'status' => 'final_payroll',
        ]);

        $settlementService->create(
            $separation,
            auth()->id()
        );
    });

    return back()->with(
        'success',
        'Employee separation has been moved to final payroll and the final settlement has been created.'
    );
}

   public function complete(EmployeeSeparation $separation)
{
    $this->ensureCompanyAccess($separation);

    if ($separation->status !== 'final_payroll') {
        return back()->with(
            'error',
            'Only separations in final payroll can be completed.'
        );
    }

    $settlement = $separation->finalSettlement;

    if (! $settlement) {
        return back()->with(
            'error',
            'A final settlement must be created before completing this separation.'
        );
    }

    if ($settlement->status !== 'paid') {
        return back()->with(
            'error',
            'The final settlement must be approved and paid before completing this separation.'
        );
    }

    DB::transaction(function () use ($separation) {
        $separation->update([
            'status' => 'completed',
        ]);

        $separation->employee()->update([
            'employment_status' => 'separated',
            'is_active' => false,
            'separation_date' => $separation->effective_date,
            'separation_type' => $separation->separation_type,
            'separation_reason' => $separation->reason,
        ]);
    });

    return back()->with(
        'success',
        'Employee separation has been completed successfully.'
    );
}

    public function cancel(EmployeeSeparation $separation)
    {
        $this->ensureCompanyAccess($separation);

        abort_if(
            $separation->status === 'completed',
            422,
            'A completed separation cannot be cancelled.'
        );

        $separation->update([
            'status' => 'cancelled',
        ]);

        return back()->with(
            'success',
            'Separation request cancelled.'
        );
    }
}