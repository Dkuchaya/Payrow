<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Position;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    public function index(
        Request $request,
        CompanyContext $companyContext
    ): Response {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $request->user()->is_super_admin ||
            $request->user()->can('employees.view'),
            403,
            'You do not have permission to view positions.'
        );

        $search = trim($request->string('search')->toString());

        $positions = Position::query()
            ->where('company_id', $company->id)
            ->with('department:id,name')
            ->withCount('employees')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Positions/Index', [
            'positions' => $positions,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(
        Request $request,
        CompanyContext $companyContext
    ): Response {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $request->user()->is_super_admin ||
            $request->user()->can('employees.create'),
            403,
            'You do not have permission to create positions.'
        );

        $departments = Department::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Positions/Create', [
            'departments' => $departments,
        ]);
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
            'You do not have permission to create positions.'
        );

        $validated = $request->validate([
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')
                    ->where('company_id', $company->id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name')
                    ->where('company_id', $company->id),
            ],
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('positions', 'code')
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

        Position::create([
            'company_id' => $company->id,
            'department_id' => $validated['department_id'] ?? null,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('positions.index')
            ->with('success', 'Position created successfully.');
    }

    public function edit(
        Request $request,
        Position $position,
        CompanyContext $companyContext
    ): Response {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $request->user()->is_super_admin ||
            $request->user()->can('employees.update'),
            403,
            'You do not have permission to edit positions.'
        );

        abort_unless(
            $position->company_id === $company->id,
            404
        );

        $departments = Department::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Positions/Edit', [
            'position' => $position,
            'departments' => $departments,
        ]);
    }

    public function update(
        Request $request,
        Position $position,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $request->user()->is_super_admin ||
            $request->user()->can('employees.update'),
            403,
            'You do not have permission to update positions.'
        );

        abort_unless(
            $position->company_id === $company->id,
            404
        );

        $validated = $request->validate([
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')
                    ->where('company_id', $company->id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name')
                    ->where('company_id', $company->id)
                    ->ignore($position->id),
            ],
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('positions', 'code')
                    ->where('company_id', $company->id)
                    ->ignore($position->id),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'is_active' => [
                'boolean',
            ],
        ]);

        $position->update([
            'department_id' => $validated['department_id'] ?? null,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return redirect()
            ->route('positions.index')
            ->with('success', 'Position updated successfully.');
    }

    public function destroy(
        Request $request,
        Position $position,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $request->user()->is_super_admin ||
            $request->user()->can('employees.delete'),
            403,
            'You do not have permission to delete positions.'
        );

        abort_unless(
            $position->company_id === $company->id,
            404
        );

        if ($position->employees()->exists()) {
            return back()->with(
                'error',
                'This position cannot be deleted because employees are assigned to it.'
            );
        }

        $position->delete();

        return back()->with(
            'success',
            'Position deleted successfully.'
        );
    }
}