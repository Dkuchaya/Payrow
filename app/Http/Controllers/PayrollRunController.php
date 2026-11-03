<?php

namespace App\Http\Controllers;

use App\Models\PayrollPeriod;
use App\Services\PayrollRunService;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Services\PayrollCalculationService;
use Inertia\Inertia;

class PayrollRunController extends Controller
{
    public function __construct(
    private CompanyContext $companyContext,
    private PayrollRunService $payrollRunService,
    private PayrollCalculationService $payrollCalculationService
) {}


  public function index(){
    $company = $this->companyContext->get();

    abort_unless($company, 400, 'No active company selected.');

    $this->authorizeCompanyAccess();

    $runs = PayrollRun::with([
        'payrollPeriod',
        'creator',
        'approver',
    ])
        ->where('company_id', $company->id)
        ->latest()
        ->paginate(15);

    return Inertia::render('Payroll/Runs/Index', [
        'company' => $company,
        'runs' => $runs,
    ]);
}

    public function store(Request $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        if ($payrollPeriod->company_id !== $company->id) {
            abort(404);
        }

        $run = $this->payrollRunService->createDraft(
            $company,
            $payrollPeriod,
            $request->user(),
            $request->input('notes')
        );

        return redirect()
            ->route('payroll.periods.index')
            ->with('success', "Payroll run {$run->run_number} created successfully.");
    }

    private function authorizeCompanyAccess(): void
    {
        $user = request()->user();

        abort_unless($user, 403);

        if ($user->is_super_admin) {
            return;
        }

        $company = $this->companyContext->get();

        $belongsToCompany = $user->companies()
            ->where('companies.id', $company->id)
            ->where('companies.is_active', true)
            ->exists();

        abort_unless($belongsToCompany, 403);

        abort_unless($user->can('payroll.manage'), 403);
    }

    public function show(PayrollRun $payrollRun)
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

       $payrollRun = PayrollRun::with([
            'payrollPeriod',
            'creator',
            'approver',
            'items.employee',
            'items.components',
        ])
            ->where('company_id', $company->id)
            ->findOrFail($payrollRun->id);

        return Inertia::render('Payroll/Runs/Show', [
            'company' => $company,
            'run' => $payrollRun,
        ]);
    }

    public function addEmployees(PayrollRun $payrollRun)
    {
            $company = $this->companyContext->get();

            abort_unless($company, 400, 'No active company selected.');

            $this->authorizeCompanyAccess();

            $payrollRun = PayrollRun::query()
                ->where('company_id', $company->id)
                ->findOrFail($payrollRun->id);

            $addedCount = $this->payrollRunService->addEmployees(
                $payrollRun,
                $company
            );

            return redirect()
                ->route('payroll.runs.show', $payrollRun->id)
                ->with(
                    'success',
                    $addedCount . ' employee(s) added to the payroll run successfully.'
                );
        }

     public function calculate(PayrollRun $payrollRun): RedirectResponse
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $payrollRun = PayrollRun::query()
            ->where('company_id', $company->id)
            ->findOrFail($payrollRun->id);

        $this->payrollCalculationService->calculateRun(
            $payrollRun,
            $company
        );

        return redirect()
            ->route('payroll.runs.show', $payrollRun->id)
            ->with('success', 'Payroll calculated successfully.');
    }   

    public function removeEmployee(
        PayrollRun $payrollRun,
        PayrollRunItem $payrollRunItem
    ): RedirectResponse {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $payrollRun = PayrollRun::query()
            ->where('company_id', $company->id)
            ->findOrFail($payrollRun->id);

        $this->payrollRunService->removeEmployee(
            $payrollRun,
            $payrollRunItem,
            $company
        );

        return redirect()
            ->route('payroll.runs.show', $payrollRun->id)
            ->with('success', 'Employee removed from the payroll run successfully.');
    }

    public function updateOptions(
    Request $request,
    PayrollRun $payrollRun
): RedirectResponse {
    $company = $this->companyContext->get();

    abort_unless($company, 400, 'No active company selected.');

    $this->authorizeCompanyAccess();

    $payrollRun = PayrollRun::query()
        ->where('company_id', $company->id)
        ->findOrFail($payrollRun->id);

    if ($payrollRun->status !== 'draft') {
        return back()->withErrors([
            'payroll_run' => 'Payroll options can only be changed while the payroll run is in draft status.',
        ]);
    }

    $validated = $request->validate([
        'apply_attendance_rules' => ['boolean'],
        'deduct_unpaid_absence' => ['boolean'],
        'deduct_unpaid_leave' => ['boolean'],
        'deduct_late_time' => ['boolean'],
        'deduct_early_departure' => ['boolean'],
        'pay_overtime' => ['boolean'],
    ]);

    $applyAttendanceRules = (bool) ($validated['apply_attendance_rules'] ?? false);

    $payrollRun->update([
        'apply_attendance_rules' => $applyAttendanceRules,
        'deduct_unpaid_absence' => $applyAttendanceRules
            ? (bool) ($validated['deduct_unpaid_absence'] ?? false)
            : false,
        'deduct_unpaid_leave' => $applyAttendanceRules
            ? (bool) ($validated['deduct_unpaid_leave'] ?? false)
            : false,
        'deduct_late_time' => $applyAttendanceRules
            ? (bool) ($validated['deduct_late_time'] ?? false)
            : false,
        'deduct_early_departure' => $applyAttendanceRules
            ? (bool) ($validated['deduct_early_departure'] ?? false)
            : false,
        'pay_overtime' => $applyAttendanceRules
            ? (bool) ($validated['pay_overtime'] ?? false)
            : false,
    ]);

    return back()->with(
        'success',
        'Payroll calculation options saved successfully.'
    );
}
}