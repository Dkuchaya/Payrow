<?php

namespace App\Services;

use App\Models\EmployeeFinalSettlement;
use App\Models\EmployeeSeparation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EmployeeFinalSettlementService
{
    /**
     * Create the initial final settlement.
     *
     * At this stage we are creating the settlement structure.
     * The actual payroll engine will later calculate statutory
     * deductions, leave pay, loans, etc.
     */
    public function create(
        EmployeeSeparation $separation,
        int $userId
    ): EmployeeFinalSettlement {
        if ($separation->status !== 'final_payroll') {
            throw new RuntimeException(
                'The separation must be in final payroll before creating a final settlement.'
            );
        }

        if ($separation->finalSettlement()->exists()) {
            return $separation->finalSettlement()->firstOrFail();
        }

        $employee = $separation->employee;

        if (! $employee) {
            throw new RuntimeException(
                'The employee associated with this separation could not be found.'
            );
        }

        return DB::transaction(function () use (
            $separation,
            $employee,
            $userId
        ) {
            return EmployeeFinalSettlement::create([
                'company_id' => $separation->company_id,
                'employee_separation_id' => $separation->id,
                'employee_id' => $employee->id,

                'last_working_date' =>
                    $separation->last_working_date
                    ?? $separation->effective_date,

                'basic_salary' => $employee->basic_salary ?? 0,

                'status' => 'draft',

                'prepared_by' => $userId,
            ]);
        });
    }

    /**
     * Calculate settlement totals.
     */
    public function calculate(
        EmployeeFinalSettlement $settlement
    ): EmployeeFinalSettlement {
        $gross = collect([
            $settlement->salary_due,
            $settlement->leave_pay,
            $settlement->allowances,
            $settlement->overtime,
            $settlement->bonus,
            $settlement->commission,
            $settlement->notice_pay,
            $settlement->gratuity,
            $settlement->severance_pay,
            $settlement->other_earnings,
        ])->sum();

        $deductions = collect([
            $settlement->loan_deduction,
            $settlement->advance_deduction,
            $settlement->notice_deduction,
            $settlement->statutory_deductions,
            $settlement->other_deductions,
        ])->sum();

        $settlement->update([
            'gross_amount' => $gross,
            'total_deductions' => $deductions,
            'net_amount' => max(0, $gross - $deductions),
        ]);

        return $settlement->fresh();
    }

    /**
     * Submit settlement for review.
     */
    public function submitForReview(
        EmployeeFinalSettlement $settlement
    ): void {
        if ($settlement->status !== 'draft') {
            throw new RuntimeException(
                'Only draft settlements can be submitted for review.'
            );
        }

        $this->calculate($settlement);

        $settlement->update([
            'status' => 'review',
        ]);
    }

    /**
     * Approve settlement.
     */
    public function approve(
        EmployeeFinalSettlement $settlement,
        int $userId
    ): void {
        if ($settlement->status !== 'review') {
            throw new RuntimeException(
                'Only settlements under review can be approved.'
            );
        }

        $settlement->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark settlement as paid.
     */
    public function markPaid(
        EmployeeFinalSettlement $settlement,
        int $userId,
        string $paymentReference,
        ?string $paymentMethod = null
    ): void {
        if ($settlement->status !== 'approved') {
            throw new RuntimeException(
                'Only approved settlements can be marked as paid.'
            );
        }

        $settlement->update([
            'status' => 'paid',
            'settlement_date' => now()->toDateString(),
            'paid_by' => $userId,
            'paid_at' => now(),
            'payment_reference' => $paymentReference,
            'payment_method' => $paymentMethod,
        ]);
    }

    /**
     * Cancel settlement.
     */
    public function cancel(
        EmployeeFinalSettlement $settlement
    ): void {
        if ($settlement->status === 'paid') {
            throw new RuntimeException(
                'A paid settlement cannot be cancelled.'
            );
        }

        $settlement->update([
            'status' => 'cancelled',
        ]);
    }
}