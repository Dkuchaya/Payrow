<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeavePolicy;
use App\Services\LeaveBalanceService;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;


class LeaveBalanceController extends Controller
{
    public function index(
        Request $request,
        CompanyContext $companyContext
    ) {
        $company = $companyContext->get();

        abort_unless(
            $company,
            403,
            'No active company selected.'
        );

        $user = $request->user();

        if (! $user->is_super_admin) {
            app(PermissionRegistrar::class)
                ->setPermissionsTeamId($company->id);

            abort_unless(
                $user->can('leave.view'),
                403,
                'You do not have permission to view leave balances.'
            );
        }

        $year = (int) $request->integer(
            'year',
            now()->year
        );

        $balances = LeaveBalance::query()
            ->where('company_id', $company->id)
            ->where('year', $year)
            ->with([
                'employee:id,company_id,employee_number,first_name,last_name',
                'leaveType:id,name,code',
            ])
            ->orderBy('employee_id')
            ->orderBy('leave_type_id')
            ->get()
            ->map(function (LeaveBalance $balance) {

                $available =
                    (float) $balance->entitled_days
                    + (float) $balance->accrued_days
                    + (float) $balance->carried_forward_days
                    + (float) $balance->adjusted_days
                    - (float) $balance->used_days
                    - (float) $balance->pending_days;

                return [
                    'id' => $balance->id,

                    'employee' => $balance->employee
                        ? [
                            'id' => $balance->employee->id,
                            'employee_number' =>
                                $balance->employee->employee_number,
                            'name' =>
                                $balance->employee->first_name
                                . ' '
                                . $balance->employee->last_name,
                        ]
                        : null,

                    'leave_type' => $balance->leaveType
                        ? [
                            'id' => $balance->leaveType->id,
                            'name' => $balance->leaveType->name,
                            'code' => $balance->leaveType->code,
                        ]
                        : null,

                    'year' => $balance->year,

                    'entitled_days' =>
                        (float) $balance->entitled_days,

                    'accrued_days' =>
                        (float) $balance->accrued_days,

                    'carried_forward_days' =>
                        (float) $balance->carried_forward_days,

                    'used_days' =>
                        (float) $balance->used_days,

                    'pending_days' =>
                        (float) $balance->pending_days,

                    'adjusted_days' =>
                        (float) $balance->adjusted_days,

                    'available_days' =>
                        max(0, $available),
                ];
            });

        return Inertia::render(
            'Leave/Balances/Index',
            [
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                ],
                'balances' => $balances,
                'year' => $year,
            ]
        );
    }

    public function generate(
        Request $request,
        CompanyContext $companyContext,
        LeaveBalanceService $balanceService
    ) {
        $company = $companyContext->get();

        abort_unless(
            $company,
            403,
            'No active company selected.'
        );

        $this->authorizeManage(
            $request,
            $company->id
        );

        $validated = $request->validate([
            'year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
            ],
        ]);

        $count = $balanceService->generateCompanyBalances(
            $company,
            (int) $validated['year']
        );

        return back()->with(
            'success',
            "{$count} leave balances generated successfully."
        );
    }

    public function adjust(
        Request $request,
        LeaveBalance $balance,
        CompanyContext $companyContext,
        LeaveBalanceService $balanceService
    ) {
        $company = $companyContext->get();

        abort_unless(
            $company,
            403,
            'No active company selected.'
        );

        abort_unless(
            (int) $balance->company_id === (int) $company->id,
            404
        );

        $this->authorizeManage(
            $request,
            $company->id
        );

        $validated = $request->validate([
            'days' => [
                'required',
                'numeric',
                'between:-999999.99,999999.99',
            ],

            'reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        $balanceService->adjust(
            $balance,
            (float) $validated['days'],
            $validated['reason']
        );

        return back()->with(
            'success',
            'Leave balance adjusted successfully.'
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
            'You do not have permission to manage leave balances.'
        );
    }

public function accrue(Request $request){
        $company = app(CompanyContext::class)->get();

        abort_unless(
            $company,
            403,
            'No active company selected.'
        );

        $this->authorizeManage(
            $request,
            $company->id
        );

        $validated = $request->validate([
            'year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
            ],
            'as_of' => [
                'nullable',
                'date',
            ],
        ]);

        $year = (int) $validated['year'];

        $asOf = !empty($validated['as_of'])
            ? \Carbon\Carbon::parse($validated['as_of'])
            : now();

        $updated = app(\App\Services\LeaveBalanceService::class)
            ->accrueCompanyBalances(
                $company,
                $year,
                $asOf
            );

        return back()->with(
            'success',
            "Leave accrual completed. {$updated} balance(s) processed."
        );
    }

        public function carryForward(Request $request)
    {
        $company = app(CompanyContext::class)->get();

        abort_unless(
            $company,
            403,
            'No active company selected.'
        );

                $this->authorizeManage(
            $request,
            $company->id
        );

        $validated = $request->validate([
            'from_year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
            ],
        ]);

        $fromYear = (int) $validated['from_year'];

        $processed = app(\App\Services\LeaveBalanceService::class)
            ->carryForwardCompanyBalances(
                $company,
                $fromYear
            );

        return back()->with(
            'success',
            "Leave carry forward completed. {$processed} balance(s) processed."
        );
    }

    
}