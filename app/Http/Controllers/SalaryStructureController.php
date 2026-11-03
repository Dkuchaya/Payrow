<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalaryStructure;
use App\Services\SalaryStructureService;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalaryStructureController extends Controller
{
    public function __construct(
        private CompanyContext $companyContext,
        private SalaryStructureService $salaryStructureService
    ) {
    }

    public function index()
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $salaryStructures = SalaryStructure::query()
            ->with('employee')
            ->where('company_id', $company->id)
            ->latest('effective_from')
            ->paginate(15);

        return Inertia::render('Payroll/SalaryStructures/Index', [
            'company' => $company,
            'salaryStructures' => $salaryStructures,
        ]);
    }

    public function create()
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $employees = Employee::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where('employment_status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get([
                'id',
                'employee_number',
                'first_name',
                'middle_name',
                'last_name',
                'basic_salary',
                'salary_currency',
                'pay_frequency',
            ]);

        return Inertia::render('Payroll/SalaryStructures/Create', [
            'company' => $company,
            'employees' => $employees,
        ]);
    }

    public function store(Request $request)
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'pay_frequency' => ['required', 'in:weekly,biweekly,monthly'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
        ]);

        $employee = Employee::query()
            ->where('company_id', $company->id)
            ->findOrFail($validated['employee_id']);

        $this->salaryStructureService->create(
            $company,
            $employee,
            $validated
        );

        return redirect()
            ->route('payroll.salary-structures.index')
            ->with('success', 'Salary structure created successfully.');
    }

    public function edit(SalaryStructure $salaryStructure)
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $salaryStructure = SalaryStructure::query()
            ->with('employee')
            ->where('company_id', $company->id)
            ->findOrFail($salaryStructure->id);

        return Inertia::render('Payroll/SalaryStructures/Edit', [
            'company' => $company,
            'salaryStructure' => $salaryStructure,
        ]);
    }

    public function update(
        Request $request,
        SalaryStructure $salaryStructure
    ) {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $salaryStructure = SalaryStructure::query()
            ->where('company_id', $company->id)
            ->findOrFail($salaryStructure->id);

        $validated = $request->validate([
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'pay_frequency' => ['required', 'in:weekly,biweekly,monthly'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->salaryStructureService->update(
            $salaryStructure,
            $validated
        );

        return redirect()
            ->route('payroll.salary-structures.index')
            ->with('success', 'Salary structure updated successfully.');
    }

    public function deactivate(SalaryStructure $salaryStructure)
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $salaryStructure = SalaryStructure::query()
            ->where('company_id', $company->id)
            ->findOrFail($salaryStructure->id);

        $this->salaryStructureService->deactivate($salaryStructure);

        return redirect()
            ->route('payroll.salary-structures.index')
            ->with('success', 'Salary structure deactivated successfully.');
    }

    private function authorizeCompanyAccess(): void
    {
        $user = request()->user();

        abort_unless($user, 403);

        if ($user->is_super_admin) {
            return;
        }

        $company = $this->companyContext->get();

        abort_unless(
            $company &&
            $user->companies()
                ->where('companies.id', $company->id)
                ->where('companies.is_active', true)
                ->wherePivot('is_active', true)
                ->exists(),
            403
        );

        abort_unless($user->can('payroll.manage'), 403);
    }
}