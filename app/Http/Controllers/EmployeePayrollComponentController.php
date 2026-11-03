<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeePayrollComponent;
use App\Models\PayrollComponent;
use App\Services\EmployeePayrollComponentService;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EmployeePayrollComponentController extends Controller
{
    public function __construct(
        private CompanyContext $companyContext,
        private EmployeePayrollComponentService $service
    ) {}

    public function index(Employee $employee)
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $employee = Employee::query()
            ->where('company_id', $company->id)
            ->findOrFail($employee->id);

        $assignments = EmployeePayrollComponent::query()
            ->with('payrollComponent')
            ->where('employee_id', $employee->id)
            ->latest('effective_from')
            ->paginate(15);

        $components = PayrollComponent::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return Inertia::render('Payroll/EmployeeComponents/Index', [
            'company' => $company,
            'employee' => $employee,
            'assignments' => $assignments,
            'components' => $components,
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $employee = Employee::query()
            ->where('company_id', $company->id)
            ->findOrFail($employee->id);

        $data = $request->validate([
            'payroll_component_id' => [
                'required',
                'integer',
                Rule::exists('payroll_components', 'id')
                    ->where('company_id', $company->id)
                    ->where('is_active', true),
            ],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $component = PayrollComponent::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->findOrFail($data['payroll_component_id']);

        if ($component->calculation_type === 'fixed') {
            $data['percentage'] = null;
        } else {
            $data['amount'] = 0;
        }

        $this->service->create(
            $company,
            $employee,
            $component,
            $data
        );

        return redirect()
            ->route('payroll.employee-components.index', $employee->id)
            ->with('success', 'Payroll component assigned successfully.');
    }

    public function update(
        Request $request,
        EmployeePayrollComponent $employeePayrollComponent
    ) {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $assignment = EmployeePayrollComponent::query()
            ->with(['employee', 'payrollComponent'])
            ->findOrFail($employeePayrollComponent->id);

        abort_unless(
            (int) $assignment->employee->company_id === (int) $company->id,
            404
        );

        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($assignment->payrollComponent->calculation_type === 'fixed') {
            $data['percentage'] = null;
        } else {
            $data['amount'] = 0;
        }

        $this->service->update(
            $company,
            $assignment,
            $data
        );

        return redirect()
            ->back()
            ->with('success', 'Payroll component assignment updated successfully.');
    }

    public function deactivate(EmployeePayrollComponent $employeePayrollComponent)
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $assignment = EmployeePayrollComponent::query()
            ->with(['employee', 'payrollComponent'])
            ->findOrFail($employeePayrollComponent->id);

        abort_unless(
            (int) $assignment->employee->company_id === (int) $company->id,
            404
        );

        $this->service->deactivate(
            $company,
            $assignment
        );

        return redirect()
            ->back()
            ->with('success', 'Payroll component assignment deactivated successfully.');
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
}