<?php

namespace App\Http\Controllers;

use App\Models\EmployeeFinalSettlement;
use App\Models\EmployeeSeparation;
use App\Support\CompanyContext;
use App\Services\EmployeeFinalSettlementService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeFinalSettlementController extends Controller
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
        abort_unless(
            $separation->company_id === $this->company()->id,
            403,
            'You do not have access to this separation.'
        );
    }

    protected function ensureSettlementAccess(
        EmployeeFinalSettlement $settlement
    ): void {
        abort_unless(
            $settlement->company_id === $this->company()->id,
            403,
            'You do not have access to this settlement.'
        );
    }

    /**
     * Display final settlement.
     */
    public function show(EmployeeSeparation $separation)
    {
        $this->ensureSeparationAccess($separation);

        abort_unless(
            $separation->status === 'final_payroll',
            422,
            'This separation is not currently in final payroll.'
        );

        $separation->load([
            'employee.department',
            'employee.position',
            'employee.supervisor',
            'finalSettlement.preparedBy',
            'finalSettlement.reviewedBy',
            'finalSettlement.approvedBy',
            'finalSettlement.paidBy',
        ]);

        $settlement = $separation->finalSettlement;

        abort_unless(
            $settlement,
            404,
            'Final settlement has not been created.'
        );

        return Inertia::render(
            'Employees/Separation/FinalSettlement/Show',
            [
                'separation' => $separation,
                'settlement' => $settlement,
            ]
        );
    }

    /**
     * Update settlement amounts.
     */
    public function update(
        Request $request,
        EmployeeFinalSettlement $settlement,
        EmployeeFinalSettlementService $service
    ) {
        $this->ensureSettlementAccess($settlement);

        if ($settlement->status !== 'draft') {
            return back()->with(
                'error',
                'Only draft settlements can be edited.'
            );
        }

        $validated = $request->validate([
            'salary_due' => ['nullable', 'numeric', 'min:0'],
            'leave_pay' => ['nullable', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'overtime' => ['nullable', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'commission' => ['nullable', 'numeric', 'min:0'],
            'notice_pay' => ['nullable', 'numeric', 'min:0'],
            'gratuity' => ['nullable', 'numeric', 'min:0'],
            'severance_pay' => ['nullable', 'numeric', 'min:0'],
            'other_earnings' => ['nullable', 'numeric', 'min:0'],

            'loan_deduction' => ['nullable', 'numeric', 'min:0'],
            'advance_deduction' => ['nullable', 'numeric', 'min:0'],
            'notice_deduction' => ['nullable', 'numeric', 'min:0'],
            'statutory_deductions' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],

            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $settlement->update([
            'salary_due' => $validated['salary_due'] ?? 0,
            'leave_pay' => $validated['leave_pay'] ?? 0,
            'allowances' => $validated['allowances'] ?? 0,
            'overtime' => $validated['overtime'] ?? 0,
            'bonus' => $validated['bonus'] ?? 0,
            'commission' => $validated['commission'] ?? 0,
            'notice_pay' => $validated['notice_pay'] ?? 0,
            'gratuity' => $validated['gratuity'] ?? 0,
            'severance_pay' => $validated['severance_pay'] ?? 0,
            'other_earnings' => $validated['other_earnings'] ?? 0,

            'loan_deduction' => $validated['loan_deduction'] ?? 0,
            'advance_deduction' => $validated['advance_deduction'] ?? 0,
            'notice_deduction' => $validated['notice_deduction'] ?? 0,
            'statutory_deductions' => $validated['statutory_deductions'] ?? 0,
            'other_deductions' => $validated['other_deductions'] ?? 0,

            'notes' => $validated['notes'] ?? null,
        ]);

        $service->calculate($settlement);

        return back()->with(
            'success',
            'Final settlement updated successfully.'
        );
    }

    /**
     * Submit settlement for review.
     */
    public function submitForReview(
        EmployeeFinalSettlement $settlement,
        EmployeeFinalSettlementService $service
    ) {
        $this->ensureSettlementAccess($settlement);

        try {
            $service->submitForReview($settlement);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            'Final settlement submitted for review.'
        );
    }

    /**
     * Approve settlement.
     */
    public function approve(
        EmployeeFinalSettlement $settlement,
        EmployeeFinalSettlementService $service
    ) {
        $this->ensureSettlementAccess($settlement);

        try {
            $service->approve(
                $settlement,
                auth()->id()
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            'Final settlement approved successfully.'
        );
    }

    /**
     * Mark settlement as paid.
     */
    public function markPaid(
        Request $request,
        EmployeeFinalSettlement $settlement,
        EmployeeFinalSettlementService $service
    ) {
        $this->ensureSettlementAccess($settlement);

        $validated = $request->validate([
            'payment_reference' => [
                'required',
                'string',
                'max:255',
            ],
            'payment_method' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        try {
            $service->markPaid(
                $settlement,
                auth()->id(),
                $validated['payment_reference'],
                $validated['payment_method']
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            'Final settlement marked as paid.'
        );
    }

    /**
     * Cancel settlement.
     */
    public function cancel(
        EmployeeFinalSettlement $settlement,
        EmployeeFinalSettlementService $service
    ) {
        $this->ensureSettlementAccess($settlement);

        try {
            $service->cancel($settlement);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            'Final settlement cancelled.'
        );
    }
}