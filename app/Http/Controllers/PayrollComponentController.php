<?php

namespace App\Http\Controllers;

use App\Models\PayrollComponent;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PayrollComponentController extends Controller
{
    public function __construct(
        protected CompanyContext $companyContext
    ) {}

    public function index(Request $request)
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $components = PayrollComponent::query()
            ->where('company_id', $company->id)
            ->when($request->type, fn ($query, $type) =>
                $query->where('type', $type)
            )
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Payroll/Components/Index', [
            'company' => $company,
            'components' => $components,
            'filters' => $request->only('type', 'search'),
        ]);
    }

    public function store(Request $request)
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('payroll_components', 'code')
                    ->where('company_id', $company->id),
            ],
            'type' => ['required', Rule::in(['allowance', 'deduction'])],
            'calculation_type' => [
                'required',
                Rule::in(['fixed', 'percentage']),
            ],
            'default_amount' => ['required', 'numeric', 'min:0'],
            'is_taxable' => ['required', 'boolean'],
            'is_recurring' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        PayrollComponent::create([
            ...$data,
            'company_id' => $company->id,
        ]);

        return redirect()
            ->route('payroll.components.index')
            ->with('success', 'Payroll component created successfully.');
    }

    public function update(Request $request, PayrollComponent $payrollComponent)
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $payrollComponent = PayrollComponent::query()
            ->where('company_id', $company->id)
            ->findOrFail($payrollComponent->id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('payroll_components', 'code')
                    ->where('company_id', $company->id)
                    ->ignore($payrollComponent->id),
            ],
            'type' => ['required', Rule::in(['allowance', 'deduction'])],
            'calculation_type' => [
                'required',
                Rule::in(['fixed', 'percentage']),
            ],
            'default_amount' => ['required', 'numeric', 'min:0'],
            'is_taxable' => ['required', 'boolean'],
            'is_recurring' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $payrollComponent->update($data);

        return redirect()
            ->route('payroll.components.index')
            ->with('success', 'Payroll component updated successfully.');
    }

    public function deactivate(PayrollComponent $payrollComponent)
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess();

        $payrollComponent = PayrollComponent::query()
            ->where('company_id', $company->id)
            ->findOrFail($payrollComponent->id);

        $payrollComponent->update([
            'is_active' => false,
        ]);

        return redirect()
            ->route('payroll.components.index')
            ->with('success', 'Payroll component deactivated successfully.');
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