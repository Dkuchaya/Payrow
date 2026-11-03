<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeePayrollComponent;
use App\Models\PayrollComponent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeePayrollComponentService
{
    public function create(
        Company $company,
        Employee $employee,
        PayrollComponent $component,
        array $data
    ): EmployeePayrollComponent {
        return DB::transaction(function () use (
            $company,
            $employee,
            $component,
            $data
        ) {
            $this->ensureCompanyOwnership($company, $employee, $component);

            $this->ensureNoOverlap(
                $employee,
                $component,
                $data['effective_from'],
                $data['effective_to'] ?? null
            );

            return EmployeePayrollComponent::create([
                'employee_id' => $employee->id,
                'payroll_component_id' => $component->id,
                'amount' => $data['amount'] ?? 0,
                'percentage' => $data['percentage'] ?? null,
                'effective_from' => $data['effective_from'],
                'effective_to' => $data['effective_to'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function update(
        Company $company,
        EmployeePayrollComponent $assignment,
        array $data
    ): EmployeePayrollComponent {
        return DB::transaction(function () use ($company, $assignment, $data) {
            $assignment = EmployeePayrollComponent::query()
                ->with(['employee', 'payrollComponent'])
                ->lockForUpdate()
                ->findOrFail($assignment->id);

            $this->ensureCompanyOwnership(
                $company,
                $assignment->employee,
                $assignment->payrollComponent
            );

            $this->ensureNoOverlap(
                $assignment->employee,
                $assignment->payrollComponent,
                $data['effective_from'],
                $data['effective_to'] ?? null,
                $assignment->id
            );

            $assignment->update([
                'amount' => $data['amount'] ?? 0,
                'percentage' => $data['percentage'] ?? null,
                'effective_from' => $data['effective_from'],
                'effective_to' => $data['effective_to'] ?? null,
                'is_active' => $data['is_active'] ?? $assignment->is_active,
                'notes' => $data['notes'] ?? null,
            ]);

            return $assignment->fresh([
                'employee',
                'payrollComponent',
            ]);
        });
    }

    public function deactivate(
        Company $company,
        EmployeePayrollComponent $assignment
    ): EmployeePayrollComponent {
        $assignment->loadMissing(['employee', 'payrollComponent']);

        $this->ensureCompanyOwnership(
            $company,
            $assignment->employee,
            $assignment->payrollComponent
        );

        $assignment->update([
            'is_active' => false,
        ]);

        return $assignment->fresh();
    }

    private function ensureCompanyOwnership(
        Company $company,
        Employee $employee,
        PayrollComponent $component
    ): void {
        if (
            (int) $employee->company_id !== (int) $company->id ||
            (int) $component->company_id !== (int) $company->id
        ) {
            throw ValidationException::withMessages([
                'employee' => 'The employee and payroll component must belong to the selected company.',
            ]);
        }
    }

    private function ensureNoOverlap(
        Employee $employee,
        PayrollComponent $component,
        string $effectiveFrom,
        ?string $effectiveTo,
        ?int $ignoreAssignmentId = null
    ): void {
        $query = EmployeePayrollComponent::query()
            ->where('employee_id', $employee->id)
            ->where('payroll_component_id', $component->id)
            ->where('is_active', true)
            ->whereDate(
                'effective_from',
                '<=',
                $effectiveTo ?? '9999-12-31'
            )
            ->where(function ($query) use ($effectiveFrom) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $effectiveFrom);
            });

        if ($ignoreAssignmentId) {
            $query->where('id', '!=', $ignoreAssignmentId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => 'This employee already has an overlapping assignment for this payroll component.',
            ]);
        }
    }
}
