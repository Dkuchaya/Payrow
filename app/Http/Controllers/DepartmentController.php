<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index(
        Request $request,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $request->user()->is_super_admin ||
            $request->user()->can('employees.view'),
            403,
            'You do not have permission to view departments.'
        );

        $search = trim($request->input('search', ''));

        $departments = Department::query()
            ->where('company_id', $company->id)
            ->withCount('employees')
            ->with('manager:id,first_name,last_name')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Departments/Index', [
            'departments' => $departments,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(
        Request $request,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $request->user()->is_super_admin ||
            $request->user()->can('employees.create'),
            403,
            'You do not have permission to create departments.'
        );

        return Inertia::render('Departments/Create');
    }

    public function store(
        Request $request,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $request->user()->is_super_admin ||
            $request->user()->can('employees.create'),
            403,
            'You do not have permission to create departments.'
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')
                    ->where('company_id', $company->id),
            ],

            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('departments', 'code')
                    ->where('company_id', $company->id),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'boolean',
            ],
        ]);

        Department::create([
            ...$validated,
            'company_id' => $company->id,
        ]);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(
        Request $request,
        Department $department,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $request->user()->is_super_admin ||
            $request->user()->can('employees.update'),
            403,
            'You do not have permission to edit departments.'
        );

        abort_unless(
            $department->company_id === $company->id,
            404
        );

        return Inertia::render('Departments/Edit', [
            'department' => $department,
        ]);
    }

    public function update(
        Request $request,
        Department $department,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $request->user()->is_super_admin ||
            $request->user()->can('employees.update'),
            403,
            'You do not have permission to update departments.'
        );

        abort_unless(
            $department->company_id === $company->id,
            404
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')
                    ->where('company_id', $company->id)
                    ->ignore($department->id),
            ],

            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('departments', 'code')
                    ->where('company_id', $company->id)
                    ->ignore($department->id),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'boolean',
            ],
        ]);

        $department->update($validated);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(
        Request $request,
        Department $department,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $request->user()->is_super_admin ||
            $request->user()->can('employees.delete'),
            403,
            'You do not have permission to delete departments.'
        );

        abort_unless(
            $department->company_id === $company->id,
            404
        );

        if ($department->employees()->exists()) {
            return back()->with(
                'error',
                'This department cannot be deleted because it has employees assigned to it.'
            );
        }

        if ($department->manager_id) {
            $department->update([
                'manager_id' => null,
            ]);
        }

        $department->delete();

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}