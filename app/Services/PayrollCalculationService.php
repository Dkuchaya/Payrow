<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\PayrollRunItemComponent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollCalculationService
{
    public function __construct(
        private PayrollAttendanceSummaryService $attendanceSummaryService
    ) {
    }

    public function calculateRun(
        PayrollRun $payrollRun,
        Company $company
    ): PayrollRun {
        return DB::transaction(function () use ($payrollRun, $company) {

            $run = PayrollRun::query()
                ->where('company_id', $company->id)
                ->with([
                    'items.components',
                ])
                ->lockForUpdate()
                ->findOrFail($payrollRun->id);

            if (!in_array($run->status, ['draft', 'calculated'], true)) {
                throw ValidationException::withMessages([
                    'payroll_run' => 'This payroll run cannot be calculated in its current status.',
                ]);
            }

            if ($run->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'payroll_run' => 'The payroll run has no employees.',
                ]);
            }

            /*
             * Get the payroll period used by this payroll run.
             */
            $period = PayrollPeriod::query()
                ->where('company_id', $company->id)
                ->findOrFail($run->payroll_period_id);

            $run->update([
                'status' => 'processing',
            ]);

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;

            foreach ($run->items as $item) {

                /*
                 * ---------------------------------------------------------
                 * ATTENDANCE & LEAVE SUMMARY
                 * ---------------------------------------------------------
                 *
                 * Build the employee's attendance summary for this
                 * payroll period and save it onto the payroll item.
                 */
                $attendanceSummary = $this->attendanceSummaryService->summarize(
                    $item,
                    $period,
                    $company
                );

                /*
                 * ---------------------------------------------------------
                 * PAYROLL COMPONENTS
                 * ---------------------------------------------------------
                 */
                $basicSalary = (float) $item->basic_salary;

                $totalAllowances = 0;
                $totalEmployeeDeductions = 0;

                foreach ($item->components as $component) {

                    $calculatedAmount = $this->calculateComponent(
                        $component,
                        $basicSalary
                    );

                    $component->update([
                        'calculated_amount' => $calculatedAmount,
                    ]);

                    if ($component->type === 'allowance') {
                        $totalAllowances += $calculatedAmount;
                    }

                    if ($component->type === 'deduction') {
                        $totalEmployeeDeductions += $calculatedAmount;
                    }
                }

                /*
                 * ---------------------------------------------------------
                 * CURRENT PAYROLL TOTALS
                 * ---------------------------------------------------------
                 *
                 * Overtime is intentionally NOT added to gross pay yet.
                 * We will implement the overtime earning rule separately
                 * once the overtime rate/source has been confirmed.
                 */
                $grossPay = $basicSalary + $totalAllowances;

                $netPay = $grossPay - $totalEmployeeDeductions;

                /*
                 * ---------------------------------------------------------
                 * UPDATE PAYROLL RUN ITEM
                 * ---------------------------------------------------------
                 */
                $item->update([
                    // Attendance summary
                    'scheduled_days' => $attendanceSummary['scheduled_days'],
                    'worked_days' => $attendanceSummary['worked_days'],
                    'paid_leave_days' => $attendanceSummary['paid_leave_days'],
                    'unpaid_leave_days' => $attendanceSummary['unpaid_leave_days'],
                    'absent_days' => $attendanceSummary['absent_days'],
                    'late_minutes' => $attendanceSummary['late_minutes'],
                    'early_departure_minutes' => $attendanceSummary['early_departure_minutes'],
                    'overtime_hours' => $attendanceSummary['overtime_hours'],

                    // Payroll amounts
                    'total_allowances' => round($totalAllowances, 2),
                    'gross_pay' => round($grossPay, 2),
                    'total_deductions' => round($totalEmployeeDeductions, 2),
                    'net_pay' => round($netPay, 2),

                    'status' => 'calculated',
                    'calculation_notes' => null,
                ]);

                $totalGross += $grossPay;
                $totalDeductions += $totalEmployeeDeductions;
                $totalNet += $netPay;
            }

            /*
             * -------------------------------------------------------------
             * UPDATE PAYROLL RUN TOTALS
             * -------------------------------------------------------------
             */
            $run->update([
                'status' => 'calculated',
                'employee_count' => $run->items()->count(),
                'gross_pay' => round($totalGross, 2),
                'total_deductions' => round($totalDeductions, 2),
                'net_pay' => round($totalNet, 2),
            ]);

            return $run->fresh([
                'items.components',
            ]);
        });
    }

    private function calculateComponent(
        PayrollRunItemComponent $component,
        float $basicSalary
    ): float {
        if ($component->calculation_type === 'fixed') {
            return round((float) $component->amount, 2);
        }

        if ($component->calculation_type === 'percentage') {
            return round(
                $basicSalary * ((float) $component->percentage / 100),
                2
            );
        }

        throw ValidationException::withMessages([
            'component' => sprintf(
                'Unsupported calculation type for payroll component "%s".',
                $component->name
            ),
        ]);
    }
}
