<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\SalaryStructure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalaryStructureService
{
    public function create(
        Company $company,
        Employee $employee,
        array $data
    ): SalaryStructure {
        return DB::transaction(function () use ($company, $employee, $data) {
            $this->ensureEmployeeBelongsToCompany($company, $employee);

            $effectiveFrom = $data['effective_from'];

            $overlap = SalaryStructure::query()
                ->where('employee_id', $employee->id)
                ->where(function ($query) use ($effectiveFrom) {
                    $query
                        ->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $effectiveFrom);
                })
                ->whereDate('effective_from', '<=', $effectiveFrom)
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'effective_from' => 'The effective date overlaps an existing salary structure.',
                ]);
            }

            return SalaryStructure::create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'basic_salary' => $data['basic_salary'],
                'currency' => $data['currency'] ?? 'ZMW',
                'pay_frequency' => $data['pay_frequency'] ?? 'monthly',
                'effective_from' => $effectiveFrom,
                'effective_to' => $data['effective_to'] ?? null,
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function update(
        SalaryStructure $salaryStructure,
        array $data
    ): SalaryStructure {
        return DB::transaction(function () use ($salaryStructure, $data) {
            $effectiveFrom = $data['effective_from'];

            $overlap = SalaryStructure::query()
                ->where('employee_id', $salaryStructure->employee_id)
                ->where('id', '!=', $salaryStructure->id)
                ->where(function ($query) use ($effectiveFrom) {
                    $query
                        ->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $effectiveFrom);
                })
                ->whereDate('effective_from', '<=', $effectiveFrom)
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'effective_from' => 'The effective date overlaps an existing salary structure.',
                ]);
            }

            $salaryStructure->update([
                'basic_salary' => $data['basic_salary'],
                'currency' => $data['currency'] ?? $salaryStructure->currency,
                'pay_frequency' => $data['pay_frequency'] ?? $salaryStructure->pay_frequency,
                'effective_from' => $effectiveFrom,
                'effective_to' => $data['effective_to'] ?? null,
                'status' => $data['status'] ?? $salaryStructure->status,
                'notes' => $data['notes'] ?? null,
            ]);

            return $salaryStructure->fresh();
        });
    }

    public function deactivate(SalaryStructure $salaryStructure): SalaryStructure
    {
        $salaryStructure->update([
            'status' => 'inactive',
        ]);

        return $salaryStructure->fresh();
    }

    private function ensureEmployeeBelongsToCompany(
        Company $company,
        Employee $employee
    ): void {
        if ((int) $employee->company_id !== (int) $company->id) {
            throw ValidationException::withMessages([
                'employee' => 'The employee does not belong to the selected company.',
            ]);
        }
    }
}