<?php

namespace App\Services;
use App\Models\Company;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\PayrollRunItem;
use App\Models\PayrollRunItemComponent;


class PayrollRunService
{
    public function createDraft(
        Company $company,
        PayrollPeriod $period,
        User $user,
        ?string $notes = null
    ): PayrollRun {
        return DB::transaction(function () use ($company, $period, $user, $notes) {

            $period = PayrollPeriod::query()
                ->where('company_id', $company->id)
                ->lockForUpdate()
                ->findOrFail($period->id);

            if ($period->status !== 'open') {
                throw ValidationException::withMessages([
                    'payroll_period' => 'A payroll run can only be created for an open payroll period.',
                ]);
            }

            $existingRun = PayrollRun::query()
                ->where('company_id', $company->id)
                ->where('payroll_period_id', $period->id)
                ->where('status', '!=', 'cancelled')
                ->exists();

            if ($existingRun) {
                throw ValidationException::withMessages([
                    'payroll_period' => 'An active payroll run already exists for this period.',
                ]);
            }

            $run = PayrollRun::create([
                'company_id' => $company->id,
                'payroll_period_id' => $period->id,
                'status' => 'draft',
                'employee_count' => 0,
                'gross_pay' => 0,
                'total_deductions' => 0,
                'net_pay' => 0,
                'created_by' => $user->id,
                'notes' => $notes,
            ]);

            $run->update([
                'run_number' => sprintf(
                    'PR-%s-%06d',
                    $period->start_date->format('Ym'),
                    $run->id
                ),
            ]);

            return $run->fresh();
        });
    }

    public function addEmployees(PayrollRun $payrollRun, Company $company): int
    {
        return DB::transaction(function () use ($payrollRun, $company) {
            $run = PayrollRun::query()
                ->where('company_id', $company->id)
                ->lockForUpdate()
                ->findOrFail($payrollRun->id);

            if ($run->status !== 'draft') {
                throw ValidationException::withMessages([
                    'payroll_run' => 'Employees can only be added to a draft payroll run.',
                ]);
            }

            $run->load('payrollPeriod');

            $period = $run->payrollPeriod;

            $addedCount = 0;

            Employee::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->where('employment_status', 'active')
                ->whereDoesntHave('payrollRunItems', function ($query) use ($run) {
                    $query->where('payroll_run_id', $run->id);
                })
                ->chunkById(100, function ($employees) use ($run, $period, &$addedCount) {
                    foreach ($employees as $employee) {
                        $salaryStructure = $employee->salaryStructures()
                            ->where('status', 'active')
                            ->whereDate(
                                'effective_from',
                                '<=',
                                $period->end_date
                            )
                            ->where(function ($query) use ($period) {
                                $query->whereNull('effective_to')
                                    ->orWhereDate(
                                        'effective_to',
                                        '>=',
                                        $period->start_date
                                    );
                            })
                            ->orderByDesc('effective_from')
                            ->first();

                        if (!$salaryStructure) {
                            throw ValidationException::withMessages([
                                'employee' => sprintf(
                                    '%s %s does not have a valid salary structure for the payroll period.',
                                    $employee->first_name,
                                    $employee->last_name
                                ),
                            ]);
                        }

                        $payrollRunItem = PayrollRunItem::create([
                        'payroll_run_id' => $run->id,
                        'employee_id' => $employee->id,
                        'basic_salary' => $salaryStructure->basic_salary,
                        'total_allowances' => 0,
                        'gross_pay' => 0,
                        'total_deductions' => 0,
                        'net_pay' => 0,
                        'status' => 'pending',
                    ]);

                    $employee->loadMissing('payrollComponents.payrollComponent');

                    $components = $employee->payrollComponents
                        ->filter(function ($assignment) use ($period) {
                            if (!$assignment->is_active) {
                                return false;
                            }

                            if ($assignment->effective_from->gt($period->end_date)) {
                                return false;
                            }

                            if (
                                $assignment->effective_to !== null &&
                                $assignment->effective_to->lt($period->start_date)
                            ) {
                                return false;
                            }

                            return true;
                        });

                    foreach ($components as $assignment) {
                        $component = $assignment->payrollComponent;

                        PayrollRunItemComponent::create([
                            'payroll_run_item_id' => $payrollRunItem->id,
                            'payroll_component_id' => $component->id,
                            'name' => $component->name,
                            'type' => $component->type,
                            'calculation_type' => $component->calculation_type,
                            'amount' => $assignment->amount,
                            'percentage' => $assignment->percentage,
                            'calculated_amount' => 0,

                            // Existing tax configuration
                            'is_taxable' => $component->is_taxable,

                            // Statutory classifications
                            'is_paye_taxable' => $component->is_paye_taxable,
                            'is_napsa_pensionable' => $component->is_napsa_pensionable,
                            'is_nhima_liable' => $component->is_nhima_liable,
                            'is_sdl_liable' => $component->is_sdl_liable,
                        ]);

                    }

                    $addedCount++;
                    }
                });

            $run->update([
                'employee_count' => $run->items()->count(),
            ]);

            return $addedCount;
        });
    }

        public function removeEmployee(
        PayrollRun $payrollRun,
        PayrollRunItem $payrollRunItem,
        Company $company
    ): void {
        DB::transaction(function () use ($payrollRun, $payrollRunItem, $company) {
            $run = PayrollRun::query()
                ->where('company_id', $company->id)
                ->lockForUpdate()
                ->findOrFail($payrollRun->id);

            if ($run->status !== 'draft') {
                throw ValidationException::withMessages([
                    'payroll_run' => 'Employees can only be removed from a draft payroll run.',
                ]);
            }

            $item = PayrollRunItem::query()
                ->where('payroll_run_id', $run->id)
                ->findOrFail($payrollRunItem->id);

            $item->delete();

            $run->update([
                'employee_count' => $run->items()->count(),
            ]);
        });
    }


    public function resetToDraft(
    PayrollRun $payrollRun,
    Company $company
): PayrollRun {
    return DB::transaction(function () use ($payrollRun, $company) {

        $run = PayrollRun::query()
            ->where('company_id', $company->id)
            ->lockForUpdate()
            ->findOrFail($payrollRun->id);

        if ($run->status !== 'calculated') {
            throw ValidationException::withMessages([
                'payroll_run' => 'Only calculated payroll runs can be reset to draft.',
            ]);
        }

        if (
            $run->approved_by !== null ||
            $run->approved_at !== null ||
            $run->paid_at !== null
        ) {
            throw ValidationException::withMessages([
                'payroll_run' => 'Approved or paid payroll runs cannot be reset.',
            ]);
        }

        $run->load('items.components');

        foreach ($run->items as $item) {
            foreach ($item->components as $component) {
                $component->update([
                    'calculated_amount' => 0,
                ]);
            }

            $item->update([
                'scheduled_days' => 0,
                'worked_days' => 0,
                'paid_leave_days' => 0,
                'unpaid_leave_days' => 0,
                'absent_days' => 0,
                'late_minutes' => 0,
                'early_departure_minutes' => 0,
                'overtime_hours' => 0,
                'total_allowances' => 0,
                'gross_pay' => 0,
                'total_deductions' => 0,
                'net_pay' => 0,
                'calculation_notes' => null,
                'status' => 'pending',

                // Employee statutory deductions
                'paye' => 0,
                'employee_napsa' => 0,
                'employee_nhima' => 0,
                'employee_statutory_deductions' => 0,

                // Employer statutory contributions
                'employer_napsa' => 0,
                'employer_nhima' => 0,
                'skills_development_levy' => 0,
                'employer_statutory_contributions' => 0,
                'employer_total_cost' => 0,

                // Statutory calculation breakdown
                'statutory_breakdown' => null,
                            ]);
                        }

                        $run->update([
                            'status' => 'draft',
                            'employee_count' => $run->items()->count(),

                            'gross_pay' => 0,
                            'total_deductions' => 0,
                            'net_pay' => 0,

                            // Employer statutory totals
                            'employer_napsa' => 0,
                            'employer_nhima' => 0,
                            'skills_development_levy' => 0,
                            'employer_statutory_contributions' => 0,
                            'employer_total_cost' => 0,
                        ]);

                        return $run->fresh(['items.components']);
                    });
                }

    
}

