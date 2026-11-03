<?php

namespace App\Http\Controllers;

use App\Models\PayrollPeriod;
use App\Support\CompanyContext;
use App\Services\PayrollPeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollPeriodController extends Controller
{
    public function __construct(
        protected CompanyContext $companyContext,
        protected PayrollPeriodService $payrollPeriodService
    ) {
    }

    public function index(Request $request): Response
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        $periods = PayrollPeriod::query()
            ->where('company_id', $company->id)
            ->orderByDesc('start_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Payroll/Periods/Index', [
            'company' => $company,
            'periods' => $periods,
        ]);
    }

    public function create(Request $request): Response
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        return Inertia::render('Payroll/Periods/Create', [
            'company' => $company,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'pay_date' => ['nullable', 'date', 'after_or_equal:end_date'],
            'frequency' => ['required', 'in:weekly,biweekly,monthly'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->payrollPeriodService->create($company, $data);

        return redirect()
            ->route('payroll.periods.index')
            ->with('success', 'Payroll period created successfully.');
    }

    public function edit(
        Request $request,
        PayrollPeriod $payrollPeriod
    ): Response {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        abort_unless(
            $payrollPeriod->company_id === $company->id,
            404
        );

        return Inertia::render('Payroll/Periods/Edit', [
            'company' => $company,
            'period' => $payrollPeriod,
        ]);
    }

    public function update(
        Request $request,
        PayrollPeriod $payrollPeriod
    ): RedirectResponse {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        abort_unless(
            $payrollPeriod->company_id === $company->id,
            404
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'pay_date' => ['nullable', 'date', 'after_or_equal:end_date'],
            'frequency' => ['required', 'in:weekly,biweekly,monthly'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->payrollPeriodService->update(
            $payrollPeriod,
            $data
        );

        return redirect()
            ->route('payroll.periods.index')
            ->with('success', 'Payroll period updated successfully.');
    }

    public function process(
        Request $request,
        PayrollPeriod $payrollPeriod
    ): RedirectResponse {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        $this->ensureCompanyPeriod($payrollPeriod, $company->id);

        $this->payrollPeriodService->markProcessing($payrollPeriod);

        return back()->with(
            'success',
            'Payroll period moved to processing.'
        );
    }

    public function markProcessed(
        Request $request,
        PayrollPeriod $payrollPeriod
    ): RedirectResponse {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        $this->ensureCompanyPeriod($payrollPeriod, $company->id);

        $this->payrollPeriodService->markProcessed($payrollPeriod);

        return back()->with(
            'success',
            'Payroll period marked as processed.'
        );
    }

    public function approve(
        Request $request,
        PayrollPeriod $payrollPeriod
    ): RedirectResponse {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        $this->ensureCompanyPeriod($payrollPeriod, $company->id);

        $this->payrollPeriodService->approve($payrollPeriod);

        return back()->with(
            'success',
            'Payroll period approved successfully.'
        );
    }

    public function markPaid(
        Request $request,
        PayrollPeriod $payrollPeriod
    ): RedirectResponse {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        $this->ensureCompanyPeriod($payrollPeriod, $company->id);

        $this->payrollPeriodService->markPaid($payrollPeriod);

        return back()->with(
            'success',
            'Payroll period marked as paid.'
        );
    }

    public function lock(
        Request $request,
        PayrollPeriod $payrollPeriod
    ): RedirectResponse {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        $this->ensureCompanyPeriod($payrollPeriod, $company->id);

        $this->payrollPeriodService->lock($payrollPeriod);

        return back()->with(
            'success',
            'Payroll period locked successfully.'
        );
    }

    protected function ensureCompanyPeriod(
        PayrollPeriod $period,
        int $companyId
    ): void {
        abort_unless(
            $period->company_id === $companyId,
            404
        );
    }

    protected function authorizeCompanyAccess(
        Request $request,
        $company
    ): void {
        if ($request->user()?->is_super_admin) {
            return;
        }

        abort_unless(
            $request->user()?->companies()
                ->where('companies.id', $company->id)
                ->where('companies.is_active', true)
                ->exists(),
            403
        );

        abort_unless(
            $request->user()?->can('payroll.manage'),
            403
        );
    }
}