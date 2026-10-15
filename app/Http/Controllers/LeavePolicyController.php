<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;

class LeavePolicyController extends Controller
{
    public function index(Request $request, CompanyContext $companyContext)
    {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        $user = $request->user();

        if (! $user->is_super_admin) {
            app(PermissionRegistrar::class)
                ->setPermissionsTeamId($company->id);

            abort_unless(
                $user->can('leave.view'),
                403,
                'You do not have permission to view leave policies.'
            );
        }

        $policies = LeavePolicy::query()
            ->where('company_id', $company->id)
            ->with('leaveType:id,name,code')
            ->orderBy('effective_from', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function (LeavePolicy $policy) {
                return [
                    'id' => $policy->id,
                    'leave_type_id' => $policy->leave_type_id,
                    'leave_type' => $policy->leaveType
                        ? [
                            'id' => $policy->leaveType->id,
                            'name' => $policy->leaveType->name,
                            'code' => $policy->leaveType->code,
                        ]
                        : null,

                    'effective_from' => optional($policy->effective_from)
                        ->format('Y-m-d'),

                    'effective_to' => optional($policy->effective_to)
                        ->format('Y-m-d'),

                    'entitlement_days' => (float) $policy->entitlement_days,
                    'accrual_method' => $policy->accrual_method,
                    'accrual_days_per_period' => $policy->accrual_days_per_period !== null
                        ? (float) $policy->accrual_days_per_period
                        : null,

                    'carry_forward_allowed' => $policy->carry_forward_allowed,
                    'max_carry_forward_days' => $policy->max_carry_forward_days !== null
                        ? (float) $policy->max_carry_forward_days
                        : null,

                    'min_notice_days' => $policy->min_notice_days,

                    'max_consecutive_days' => $policy->max_consecutive_days !== null
                        ? (float) $policy->max_consecutive_days
                        : null,

                    'allow_negative_balance' => $policy->allow_negative_balance,
                    'requires_attachment' => $policy->requires_attachment,
                    'is_active' => $policy->is_active,
                ];
            });

        $leaveTypes = LeaveType::query()
            ->where('company_id', $company->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
                'is_paid',
                'requires_balance',
                'requires_attachment',
                'is_active',
            ]);

        return Inertia::render('Leave/Policies/Index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'policies' => $policies,
            'leaveTypes' => $leaveTypes,
        ]);
    }

    public function store(
        Request $request,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        $this->authorizeManage($request, $company->id);

        $validated = $this->validatePolicy($request, $company->id);

        LeavePolicy::create([
            'company_id' => $company->id,
            ...$validated,
        ]);

        return back()->with(
            'success',
            'Leave policy created successfully.'
        );
    }

    public function update(
        Request $request,
        LeavePolicy $policy,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            (int) $policy->company_id === (int) $company->id,
            404
        );

        $this->authorizeManage($request, $company->id);

        $validated = $this->validatePolicy(
            $request,
            $company->id,
            $policy->id
        );

        $policy->update($validated);

        return back()->with(
            'success',
            'Leave policy updated successfully.'
        );
    }

    public function toggleStatus(
        Request $request,
        LeavePolicy $policy,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            (int) $policy->company_id === (int) $company->id,
            404
        );

        $this->authorizeManage($request, $company->id);

        $policy->update([
            'is_active' => ! $policy->is_active,
        ]);

        return back()->with(
            'success',
            $policy->is_active
                ? 'Leave policy activated successfully.'
                : 'Leave policy deactivated successfully.'
        );
    }

    public function destroy(
        Request $request,
        LeavePolicy $policy,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            (int) $policy->company_id === (int) $company->id,
            404
        );

        $this->authorizeManage($request, $company->id);

        if ($policy->balances()->exists() || $policy->requests()->exists()) {
            return back()->with(
                'error',
                'This leave policy cannot be deleted because it is already being used.'
            );
        }

        $policy->delete();

        return back()->with(
            'success',
            'Leave policy deleted successfully.'
        );
    }

    private function authorizeManage(
        Request $request,
        int $companyId
    ): void {
        $user = $request->user();

        if ($user->is_super_admin) {
            return;
        }

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($companyId);

        abort_unless(
            $user->can('leave.manage'),
            403,
            'You do not have permission to manage leave policies.'
        );
    }

    private function validatePolicy(
        Request $request,
        int $companyId,
        ?int $policyId = null
    ): array {
        return $request->validate([
            'leave_type_id' => [
                'required',
                'integer',
                Rule::exists('leave_types', 'id')
                    ->where(
                        fn ($query) => $query
                            ->where('company_id', $companyId)
                            ->where('is_active', true)
                    ),
            ],

            'effective_from' => [
                'required',
                'date',
            ],

            'effective_to' => [
                'nullable',
                'date',
                'after_or_equal:effective_from',
            ],

            'entitlement_days' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
            ],

            'accrual_method' => [
                'required',
                Rule::in([
                    'annual',
                    'monthly',
                    'quarterly',
                    'per_payroll',
                    'none',
                ]),
            ],

            'accrual_days_per_period' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],

            'carry_forward_allowed' => [
                'required',
                'boolean',
            ],

            'max_carry_forward_days' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],

            'min_notice_days' => [
                'required',
                'integer',
                'min:0',
                'max:365',
            ],

            'max_consecutive_days' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],

            'allow_negative_balance' => [
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
        ]);
    }
}