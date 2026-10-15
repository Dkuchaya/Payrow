<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LeaveTypeController extends Controller
{
    public function index(Request $request, CompanyContext $companyContext)
    {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $request->user()?->is_super_admin ||
            $request->user()->can('leave.view'),
            403,
            'You do not have permission to view leave types.'
        );

        $leaveTypes = LeaveType::query()
            ->where('company_id', $company->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Leave/Types/Index', [
            'leaveTypes' => $leaveTypes,
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
        ]);
    }

    public function store(
        Request $request,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $request->user()?->is_super_admin ||
            $request->user()->can('leave.manage'),
            403,
            'You do not have permission to manage leave types.'
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('leave_types', 'code')
                    ->where(
                        fn ($query) =>
                            $query->where('company_id', $company->id)
                    ),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_paid' => [
                'required',
                'boolean',
            ],

            'requires_balance' => [
                'required',
                'boolean',
            ],

            'requires_attachment' => [
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        LeaveType::create([
            ...$validated,
            'company_id' => $company->id,
        ]);

        return back()->with(
            'success',
            'Leave type created successfully.'
        );
    }

    public function update(
        Request $request,
        LeaveType $leaveType,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            (int) $leaveType->company_id === (int) $company->id,
            403,
            'You cannot manage this leave type.'
        );

        abort_unless(
            $request->user()?->is_super_admin ||
            $request->user()->can('leave.manage'),
            403,
            'You do not have permission to manage leave types.'
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('leave_types', 'code')
                    ->where(
                        fn ($query) =>
                            $query->where('company_id', $company->id)
                    )
                    ->ignore($leaveType->id),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_paid' => [
                'required',
                'boolean',
            ],

            'requires_balance' => [
                'required',
                'boolean',
            ],

            'requires_attachment' => [
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        $leaveType->update($validated);

        return back()->with(
            'success',
            'Leave type updated successfully.'
        );
    }

    public function destroy(
        Request $request,
        LeaveType $leaveType,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            (int) $leaveType->company_id === (int) $company->id,
            403,
            'You cannot delete this leave type.'
        );

        abort_unless(
            $request->user()?->is_super_admin ||
            $request->user()->can('leave.manage'),
            403,
            'You do not have permission to manage leave types.'
        );

        if (
            $leaveType->policies()->exists() ||
            $leaveType->balances()->exists() ||
            $leaveType->requests()->exists()
        ) {
            return back()->with(
                'error',
                'This leave type cannot be deleted because it is already in use. Deactivate it instead.'
            );
        }

        $leaveType->delete();

        return back()->with(
            'success',
            'Leave type deleted successfully.'
        );
    }

    public function toggleStatus(
        Request $request,
        LeaveType $leaveType,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            (int) $leaveType->company_id === (int) $company->id,
            403,
            'You cannot modify this leave type.'
        );

        abort_unless(
            $request->user()?->is_super_admin ||
            $request->user()->can('leave.manage'),
            403,
            'You do not have permission to manage leave types.'
        );

        $leaveType->update([
            'is_active' => ! $leaveType->is_active,
        ]);

        return back()->with(
            'success',
            'Leave type status updated successfully.'
        );
    }
}