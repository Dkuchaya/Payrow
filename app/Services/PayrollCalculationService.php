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
    private const OVERTIME_MULTIPLIER = 1.5;

   public function __construct(
        private PayrollAttendanceSummaryService $attendanceSummaryService,
        private ZambiaStatutoryCalculator $statutoryCalculator
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

                if (
                    !in_array($run->status, ['draft', 'calculated'], true) ||
                    $run->approved_by !== null ||
                    $run->approved_at !== null ||
                    $run->paid_at !== null
                ) {
                    throw ValidationException::withMessages([
                        'payroll_run' => 'This payroll run cannot be calculated because it is not editable or has already been finalized.',
                    ]);
                }

            if ($run->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'payroll_run' => 'The payroll run has no employees.',
                ]);
            }

            $period = PayrollPeriod::query()
            ->where('company_id', $company->id)
            ->lockForUpdate()
            ->findOrFail($run->payroll_period_id);

           if (!in_array($period->status, ['open', 'processing'], true)) {
            throw ValidationException::withMessages([
                'payroll_period' => 'Payroll cannot be recalculated because its period has already been processed or finalized.',
            ]);
        }

            $run->update([
                'status' => 'processing',
            ]);

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;

            $totalEmployerNapsa = 0;
            $totalEmployerNhima = 0;
            $totalSdl = 0;
            $totalEmployerStatutory = 0;
            $totalEmployerCost = 0;

            foreach ($run->items as $item) {

                /*
                |--------------------------------------------------------------------------
                | Attendance summary
                |--------------------------------------------------------------------------
                */

                $attendanceSummary = $this->attendanceSummaryService->summarize(
                    $item,
                    $period,
                    $company
                );

                $basicSalary = (float) $item->basic_salary;

                /*
                |--------------------------------------------------------------------------
                | Attendance options
                |--------------------------------------------------------------------------
                */

                $applyAttendanceRules = (bool) $run->apply_attendance_rules;

                /*
                |--------------------------------------------------------------------------
                | Scheduled hours
                |--------------------------------------------------------------------------
                |
                | This comes from the employee's actual work schedule.
                | No fixed 8-hour assumption.
                |
                */

                $scheduledHours = (float) (
                    $attendanceSummary['scheduled_hours'] ?? 0
                );

                /*
                |--------------------------------------------------------------------------
                | Rates
                |--------------------------------------------------------------------------
                */

                $dailyRate = $attendanceSummary['scheduled_days'] > 0
                    ? $basicSalary / $attendanceSummary['scheduled_days']
                    : 0;

                $hourlyRate = $scheduledHours > 0
                    ? $basicSalary / $scheduledHours
                    : 0;

                /*
                |--------------------------------------------------------------------------
                | Normal payroll components
                |--------------------------------------------------------------------------
                */

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
                |--------------------------------------------------------------------------
                | Attendance deductions
                |--------------------------------------------------------------------------
                */

                $absenceDeduction = 0;
                $unpaidLeaveDeduction = 0;
                $lateDeduction = 0;
                $earlyDepartureDeduction = 0;

                if ($applyAttendanceRules) {

                    // Unpaid absence
                    if ($run->deduct_unpaid_absence) {
                        $absenceDeduction = round(
                            $dailyRate * (float) $attendanceSummary['absent_days'],
                            2
                        );
                    }

                    // Unpaid leave
                    if ($run->deduct_unpaid_leave) {
                        $unpaidLeaveDeduction = round(
                            $dailyRate * (float) $attendanceSummary['unpaid_leave_days'],
                            2
                        );
                    }

                    // Late time
                    if ($run->deduct_late_time) {
                        $lateHours = (float) $attendanceSummary['late_minutes'] / 60;

                        $lateDeduction = round(
                            $hourlyRate * $lateHours,
                            2
                        );
                    }

                    // Early departure
                    if ($run->deduct_early_departure) {
                        $earlyHours = (float) $attendanceSummary['early_departure_minutes'] / 60;

                        $earlyDepartureDeduction = round(
                            $hourlyRate * $earlyHours,
                            2
                        );
                    }
                }

                $attendanceDeduction = round(
                    $absenceDeduction +
                    $unpaidLeaveDeduction +
                    $lateDeduction +
                    $earlyDepartureDeduction,
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Overtime pay
                |--------------------------------------------------------------------------
                */

                $overtimePay = 0;

                if (
                    $applyAttendanceRules &&
                    $run->pay_overtime
                ) {
                    $overtimePay = round(
                        $hourlyRate *
                        (float) $attendanceSummary['overtime_hours'] *
                        self::OVERTIME_MULTIPLIER,
                        2
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Final employee payroll
                |--------------------------------------------------------------------------
                */

                 $grossPay = round(
                    $basicSalary + $totalAllowances + $overtimePay,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | Statutory calculations
                |--------------------------------------------------------------------------
                */

                $statutory = $this->statutoryCalculator->calculate(
                    $item,
                    $period,
                    $company,
                    $grossPay,
                    $overtimePay
                );

                $employeeTotalDeductions = round(
                    $totalEmployeeDeductions
                    + $attendanceDeduction
                    + $statutory['employee_statutory_deductions'],
                    2
                );

                $netPay = round($grossPay - $employeeTotalDeductions, 2);

                    
                $calculationNotes = [
                    'basic_salary' => round($basicSalary, 2),

                    'rates' => [
                        'daily_rate' => round($dailyRate, 2),
                        'hourly_rate' => round($hourlyRate, 2),
                    ],

                    'options' => [
                        'apply_attendance_rules' => $applyAttendanceRules,
                        'deduct_unpaid_absence' => (bool) $run->deduct_unpaid_absence,
                        'deduct_unpaid_leave' => (bool) $run->deduct_unpaid_leave,
                        'deduct_late_time' => (bool) $run->deduct_late_time,
                        'deduct_early_departure' => (bool) $run->deduct_early_departure,
                        'pay_overtime' => (bool) $run->pay_overtime,
                    ],

                    'attendance' => [
                        'scheduled_days' => (float) $attendanceSummary['scheduled_days'],
                        'scheduled_hours' => (float) $attendanceSummary['scheduled_hours'],
                        'worked_days' => (float) $attendanceSummary['worked_days'],
                        'absent_days' => (float) $attendanceSummary['absent_days'],
                        'unpaid_leave_days' => (float) $attendanceSummary['unpaid_leave_days'],
                        'late_minutes' => (int) $attendanceSummary['late_minutes'],
                        'early_departure_minutes' => (int) $attendanceSummary['early_departure_minutes'],
                        'overtime_hours' => (float) $attendanceSummary['overtime_hours'],
                    ],

                    'attendance_deductions' => [
                        'absence' => $absenceDeduction,
                        'unpaid_leave' => $unpaidLeaveDeduction,
                        'late' => $lateDeduction,
                        'early_departure' => $earlyDepartureDeduction,
                        'total' => $attendanceDeduction,
                    ],

                    'overtime_pay' => round($overtimePay, 2),
                    'component_deductions' => round($totalEmployeeDeductions, 2),
                    'gross_pay' => round($grossPay, 2),
                    'statutory' => $statutory['statutory_breakdown'],
                    'total_deductions' => round($employeeTotalDeductions, 2),
                    'net_pay' => round($netPay, 2),
                ];


                /*
                |--------------------------------------------------------------------------
                | Save payroll item
                |--------------------------------------------------------------------------
                */

                $item->update([
                    'scheduled_days' =>
                        $attendanceSummary['scheduled_days'],

                    'worked_days' =>
                        $attendanceSummary['worked_days'],

                    'paid_leave_days' =>
                        $attendanceSummary['paid_leave_days'],

                    'unpaid_leave_days' =>
                        $attendanceSummary['unpaid_leave_days'],

                    'absent_days' =>
                        $attendanceSummary['absent_days'],

                    'late_minutes' =>
                        $attendanceSummary['late_minutes'],

                    'early_departure_minutes' =>
                        $attendanceSummary['early_departure_minutes'],

                    'overtime_hours' =>
                        $attendanceSummary['overtime_hours'],

                    'total_allowances' =>
                        round($totalAllowances + $overtimePay, 2),

                    'gross_pay' =>
                        round($grossPay, 2),

                    'total_deductions' =>
                        round($employeeTotalDeductions, 2),

                    'net_pay' =>
                        round($netPay, 2),

                    'status' =>
                        'calculated',

                   'calculation_notes' => $calculationNotes,

                   // Employee statutory deductions
                    'paye' => $statutory['paye'],
                    'employee_napsa' => $statutory['employee_napsa'],
                    'employee_nhima' => $statutory['employee_nhima'],
                    'employee_statutory_deductions' => $statutory['employee_statutory_deductions'],

                    // Employer statutory contributions
                    'employer_napsa' => $statutory['employer_napsa'],
                    'employer_nhima' => $statutory['employer_nhima'],
                    'skills_development_levy' => $statutory['skills_development_levy'],
                    'employer_statutory_contributions' => $statutory['employer_statutory_contributions'],
                    'employer_total_cost' => $statutory['employer_total_cost'],

                    // Statutory audit details
                    'statutory_breakdown' => $statutory['statutory_breakdown'],

                ]);

                /*
                |--------------------------------------------------------------------------
                | Run totals
                |--------------------------------------------------------------------------
                */

                $totalGross += $grossPay;
                $totalDeductions += $employeeTotalDeductions;
                $totalNet += $netPay;

                $totalEmployerNapsa += $statutory['employer_napsa'];
                $totalEmployerNhima += $statutory['employer_nhima'];
                $totalSdl += $statutory['skills_development_levy'];
                $totalEmployerStatutory += $statutory['employer_statutory_contributions'];
                $totalEmployerCost += $statutory['employer_total_cost'];
            }

            /*
            |--------------------------------------------------------------------------
            | Update payroll run
            |--------------------------------------------------------------------------
            */

            $run->update([
                    'status' =>
                        'calculated',

                    'employee_count' =>
                        $run->items()->count(),

                    'gross_pay' =>
                        round($totalGross, 2),

                    'total_deductions' =>
                        round($totalDeductions, 2),

                    'net_pay' =>
                        round($totalNet, 2),

                    'employer_napsa' => round($totalEmployerNapsa, 2),
                    'employer_nhima' => round($totalEmployerNhima, 2),
                    'skills_development_levy' => round($totalSdl, 2),
                    'employer_statutory_contributions' => round($totalEmployerStatutory, 2),
                    'employer_total_cost' => round($totalEmployerCost, 2),
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
                $basicSalary *
                ((float) $component->percentage / 100),
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
