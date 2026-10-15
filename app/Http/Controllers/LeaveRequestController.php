<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeavePolicy;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceTransaction;
use App\Services\LeaveRequestService;
use App\Support\CompanyContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;


class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveRequestService $leaveRequestService
    ) {
    }

    /**
     * Leave request list.
     */
    public function index(Request $request)
    {
        $company = app(CompanyContext::class)->get();

        abort_unless($company, 403, 'No active company selected.');

        $this->authorizeLeaveAccess($request);

        $query = LeaveRequest::query()
            ->where('company_id', $company->id)
            ->with([
                'employee:id,employee_number,first_name,middle_name,last_name',
                'leaveType:id,name,code',
                'submittedBy:id,name',
                'approvedBy:id,name',
            ])
            ->latest();

       if ($request->filled('status')) {
    $query->where(
        'status',
        $request->string('status')->toString()
    );
}

if ($request->filled('leave_type_id')) {
    $query->where(
        'leave_type_id',
        $request->integer('leave_type_id')
    );
}

if ($request->filled('employee_id')) {
    $query->where(
        'employee_id',
        $request->integer('employee_id')
    );
}

if ($request->filled('search')) {
    $search = $request->string('search')->toString();

    $query->whereHas('employee', function ($q) use ($search) {
        $q->where(function ($employeeQuery) use ($search) {
            $employeeQuery
                ->where('employee_number', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('middle_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%");
        });
    });
}

/*
|--------------------------------------------------------------------------
| Date filters
|--------------------------------------------------------------------------
| Return requests that overlap the selected date range.
*/

if ($request->filled('date_from')) {
    $dateFrom = Carbon::parse(
        $request->string('date_from')->toString()
    )->startOfDay();

    $query->whereDate('end_date', '>=', $dateFrom->toDateString());
}

if ($request->filled('date_to')) {
    $dateTo = Carbon::parse(
        $request->string('date_to')->toString()
    )->endOfDay();

    $query->whereDate('start_date', '<=', $dateTo->toDateString());
}

        $requests = $query
            ->paginate(15)
            ->withQueryString()
            ->through(function (LeaveRequest $leave) {
                return [
                    'id' => $leave->id,
                    'employee' => [
                        'id' => $leave->employee?->id,
                        'employee_number' => $leave->employee?->employee_number,
                        'name' => $leave->employee?->full_name
                            ?? trim(
                                ($leave->employee?->first_name ?? '') . ' ' .
                                ($leave->employee?->middle_name ?? '') . ' ' .
                                ($leave->employee?->last_name ?? '')
                            ),
                    ],
                    'leave_type' => [
                        'id' => $leave->leaveType?->id,
                        'name' => $leave->leaveType?->name,
                        'code' => $leave->leaveType?->code,
                    ],
                    'start_date' => $leave->start_date?->format('Y-m-d'),
                    'end_date' => $leave->end_date?->format('Y-m-d'),
                    'days' => (float) $leave->days,
                    'reason' => $leave->reason,
                    'status' => $leave->status,
                    'submitted_by' => $leave->submittedBy?->name,
                    'approved_by' => $leave->approvedBy?->name,
                    'approved_at' => $leave->approved_at?->format('Y-m-d H:i'),
                    'rejection_reason' => $leave->rejection_reason,
                    'return_date' => $leave->return_date?->format('Y-m-d'),
                    'created_at' => $leave->created_at?->format('Y-m-d H:i'),
                ];
            });

        $leaveTypes = LeaveType::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
                'is_paid',
                'requires_balance',
            ]);

        $employees = Employee::query()
        ->where('company_id', $company->id)
        ->where('is_active', true)
        ->orderBy('first_name')
        ->orderBy('last_name')
        ->get([
            'id',
            'employee_number',
            'first_name',
            'middle_name',
            'last_name',
        ]);

        $statsQuery = LeaveRequest::where('company_id', $company->id);

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)
                ->where('status', 'pending')
                ->count(),
            'approved' => (clone $statsQuery)
                ->where('status', 'approved')
                ->count(),
            'rejected' => (clone $statsQuery)
                ->where('status', 'rejected')
                ->count(),
            'cancelled' => (clone $statsQuery)
                ->where('status', 'cancelled')
                ->count(),
        ];

       return Inertia::render('Leave/Requests/Index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],

            'requests' => $requests,

            'leaveTypes' => $leaveTypes,

            'employees' => $employees,

            'stats' => $stats,

            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'leave_type_id' => $request->leave_type_id,
                'employee_id' => $request->employee_id,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
            ],
            
        ]);
    }

    /**
     * Create leave request page.
     */
    public function create(Request $request)
    {
        $company = app(CompanyContext::class)->get();

        abort_unless($company, 403, 'No active company selected.');

        $this->authorizeLeaveAccess($request);

        $employees = Employee::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->whereIn('employment_status', ['active', 'on_leave'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get([
                'id',
                'employee_number',
                'first_name',
                'middle_name',
                'last_name',
                'department_id',
                'position_id',
            ]);

        $leaveTypes = LeaveType::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
                'description',
                'is_paid',
                'requires_balance',
                'requires_attachment',
            ]);

        return Inertia::render('Leave/Requests/Create', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'employees' => $employees,
            'leaveTypes' => $leaveTypes,
        ]);
    }

    /**
     * Store leave request.
     */
    public function store(Request $request)
    {
        $company = app(CompanyContext::class)->get();

        abort_unless($company, 403, 'No active company selected.');

        $this->authorizeLeaveAccess($request);

        $validated = $request->validate([
            'employee_id' => [
                'required',
                'integer',
            ],
            'leave_type_id' => [
                'required',
                'integer',
            ],
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'attachment' => [
                'nullable',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,doc,docx',
            ],
        ]);

        $employee = Employee::query()
            ->where('company_id', $company->id)
            ->where('id', $validated['employee_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $leaveType = LeaveType::query()
            ->where('company_id', $company->id)
            ->where('id', $validated['leave_type_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        /*
         * For the first version, prevent a leave request
         * from crossing calendar years.
         */
        if ($startDate->year !== $endDate->year) {
            throw ValidationException::withMessages([
                'end_date' => 'A leave request cannot cross calendar years. Please create separate requests for each year.',
            ]);
        }

        /*
         * Find the active policy effective for the requested date.
         */
        $policy = LeavePolicy::query()
            ->where('company_id', $company->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $startDate->toDateString())
            ->where(function ($query) use ($startDate) {
                $query->whereNull('effective_to')
                    ->orWhereDate(
                        'effective_to',
                        '>=',
                        $startDate->toDateString()
                    );
            })
            ->orderByDesc('effective_from')
            ->first();

        if (!$policy) {
            throw ValidationException::withMessages([
                'leave_type_id' => 'There is no active leave policy for this leave type that is effective on the selected date.',
            ]);
        }

        /*
         * Policy-level attachment requirement.
         */
        if (
            ($leaveType->requires_attachment || $policy->requires_attachment)
            && !$request->hasFile('attachment')
        ) {
            throw ValidationException::withMessages([
                'attachment' => 'An attachment is required for this leave type.',
            ]);
        }

        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')
                ->store(
                    "leave-attachments/{$company->id}",
                    'public'
                );
        }

        try {
            $leaveRequest = $this->leaveRequestService->create(
                $employee,
                $leaveType,
                $policy,
                [
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'reason' => $validated['reason'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'attachment_path' => $attachmentPath,
                ],
                $request->user()->id
            );
        } catch (\Throwable $e) {
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }

            throw $e;
        }

        return redirect()
            ->route('leave.requests.show', $leaveRequest)
            ->with('success', 'Leave request submitted successfully.');
    }

    /**
     * Show leave request.
     */
    public function show(
        Request $request,
        LeaveRequest $leaveRequest
    ) {
        $company = app(CompanyContext::class)->get();

        abort_unless($company, 403, 'No active company selected.');

        $this->authorizeLeaveAccess($request);

        abort_unless(
            $leaveRequest->company_id === $company->id,
            404
        );

        $leaveRequest->load([
            'employee',
            'leaveType',
            'leavePolicy',
            'submittedBy:id,name',
            'approvedBy:id,name',
            'cancelledBy:id,name',
        ]);

        $balance = null;
$balanceTransactions = collect();

if ($leaveRequest->leaveType?->requires_balance) {
    $year = Carbon::parse(
        $leaveRequest->start_date
    )->year;

    $balance = LeaveBalance::query()
        ->where('company_id', $company->id)
        ->where('employee_id', $leaveRequest->employee_id)
        ->where('leave_type_id', $leaveRequest->leave_type_id)
        ->where('year', $year)
        ->with('leavePolicy')
        ->first();

            if ($balance) {
                $balanceTransactions = LeaveBalanceTransaction::query()
                    ->where('leave_balance_id', $balance->id)
                    ->with('creator:id,name')
                    ->latest()
                    ->get()
                    ->map(function (LeaveBalanceTransaction $transaction) {
                        return [
                            'id' => $transaction->id,
                            'transaction_type' => $transaction->transaction_type,
                            'days' => (float) $transaction->days,
                            'balance_before' => (float) $transaction->balance_before,
                            'balance_after' => (float) $transaction->balance_after,
                            'description' => $transaction->description,
                            'reason' => $transaction->reason,
                            'created_by' => $transaction->creator?->name,
                            'created_at' => $transaction->created_at?->format(
                                'Y-m-d H:i:s'
                            ),
                        ];
                    })
                    ->values();
            }
        }

    return Inertia::render('Leave/Requests/Show', [
    'company' => [
        'id' => $company->id,
        'name' => $company->name,
    ],

    'leaveRequest' => [
        'id' => $leaveRequest->id,

        'employee' => [
            'id' => $leaveRequest->employee?->id,
            'employee_number' => $leaveRequest->employee?->employee_number,
            'name' => $leaveRequest->employee?->full_name
                ?? trim(
                    ($leaveRequest->employee?->first_name ?? '') . ' ' .
                    ($leaveRequest->employee?->middle_name ?? '') . ' ' .
                    ($leaveRequest->employee?->last_name ?? '')
                ),
        ],

        'leave_type' => [
            'id' => $leaveRequest->leaveType?->id,
            'name' => $leaveRequest->leaveType?->name,
            'code' => $leaveRequest->leaveType?->code,
        ],

        'policy' => $leaveRequest->leavePolicy ? [
            'id' => $leaveRequest->leavePolicy->id,
            'entitlement_days' => (float) $leaveRequest->leavePolicy->entitlement_days,
            'accrual_method' => $leaveRequest->leavePolicy->accrual_method,
            'carry_forward_allowed' => $leaveRequest->leavePolicy->carry_forward_allowed,
        ] : null,

        'start_date' => $leaveRequest->start_date?->format('Y-m-d'),
        'end_date' => $leaveRequest->end_date?->format('Y-m-d'),
        'days' => (float) $leaveRequest->days,
        'reason' => $leaveRequest->reason,
        'notes' => $leaveRequest->notes,

        'attachment_path' => $leaveRequest->attachment_path,

        'attachment_url' => $leaveRequest->attachment_path
            ? Storage::disk('public')->url(
                $leaveRequest->attachment_path
            )
            : null,

        'status' => $leaveRequest->status,

        'submitted_by' => $leaveRequest->submittedBy?->name,

        'approved_by' => $leaveRequest->approvedBy?->name,

        'approved_at' => $leaveRequest->approved_at?->format(
            'Y-m-d H:i'
        ),

        'rejection_reason' => $leaveRequest->rejection_reason,

        'cancelled_by' => $leaveRequest->cancelledBy?->name,

        'cancelled_at' => $leaveRequest->cancelled_at?->format(
            'Y-m-d H:i'
        ),

        'return_date' => $leaveRequest->return_date?->format(
            'Y-m-d'
        ),

        'created_at' => $leaveRequest->created_at?->format(
            'Y-m-d H:i'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Leave Balance
    |--------------------------------------------------------------------------
    */

    'balance' => $balance ? [
        'id' => $balance->id,
        'year' => (int) $balance->year,
        'entitled_days' => (float) $balance->entitled_days,
        'accrued_days' => (float) $balance->accrued_days,
        'carried_forward_days' => (float) $balance->carried_forward_days,
        'used_days' => (float) $balance->used_days,
        'adjusted_days' => (float) $balance->adjusted_days,
        'pending_days' => (float) $balance->pending_days,
        'available_days' => (float) $balance->available_days,
    ] : null,

    /*
    |--------------------------------------------------------------------------
    | Leave Balance Audit History
    |--------------------------------------------------------------------------
    */

    'balance_transactions' => $balanceTransactions,
]);
    }

    /**
     * Approve request.
     */
    public function approve(
        Request $request,
        LeaveRequest $leaveRequest
    ) {
        $company = app(CompanyContext::class)->get();

        abort_unless($company, 403, 'No active company selected.');

        $this->authorizeLeaveManage($request);

        abort_unless(
            $leaveRequest->company_id === $company->id,
            404
        );

        $this->leaveRequestService->approve(
            $leaveRequest,
            $request->user()->id
        );

        return back()->with(
            'success',
            'Leave request approved successfully.'
        );
    }

    /**
     * Reject request.
     */
    public function reject(
        Request $request,
        LeaveRequest $leaveRequest
    ) {
        $company = app(CompanyContext::class)->get();

        abort_unless($company, 403, 'No active company selected.');

        $this->authorizeLeaveManage($request);

        abort_unless(
            $leaveRequest->company_id === $company->id,
            404
        );

        $validated = $request->validate([
            'rejection_reason' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        $this->leaveRequestService->reject(
            $leaveRequest,
            $request->user()->id,
            $validated['rejection_reason']
        );

        return back()->with(
            'success',
            'Leave request rejected.'
        );
    }

    /**
     * Cancel request.
     */
    public function cancel(
        Request $request,
        LeaveRequest $leaveRequest
    ) {
        $company = app(CompanyContext::class)->get();

        abort_unless($company, 403, 'No active company selected.');

        $this->authorizeLeaveManage($request);

        abort_unless(
            $leaveRequest->company_id === $company->id,
            404
        );

        $this->leaveRequestService->cancel(
            $leaveRequest,
            $request->user()->id
        );

        return back()->with(
            'success',
            'Leave request cancelled.'
        );
    }

    /**
     * Download/view attachment.
     */
    public function attachment(
        Request $request,
        LeaveRequest $leaveRequest
    ) {
        $company = app(CompanyContext::class)->get();

        abort_unless($company, 403, 'No active company selected.');

        $this->authorizeLeaveAccess($request);

        abort_unless(
            $leaveRequest->company_id === $company->id,
            404
        );

        abort_unless(
            $leaveRequest->attachment_path &&
            Storage::disk('public')->exists(
                $leaveRequest->attachment_path
            ),
            404
        );

        return Storage::disk('public')->response(
            $leaveRequest->attachment_path
        );
    }

    /**
     * General leave access.
     */
    private function authorizeLeaveAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless($user, 403);

        if ($user->is_super_admin) {
            return;
        }

        $company = app(CompanyContext::class)->get();

        abort_unless($company, 403, 'No active company selected.');

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($company->id);

        abort_unless(
            $user->companies()
                ->wherePivot('is_active', true)
                ->where('companies.id', $company->id)
                ->exists(),
            403,
            'You do not belong to this company.'
        );

        abort_unless(
            $user->can('leave.view'),
            403,
            'You do not have permission to view leave requests.'
        );
    }

    /**
     * Leave management access.
     */
    private function authorizeLeaveManage(Request $request): void
    {
        $user = $request->user();

        abort_unless($user, 403);

        if ($user->is_super_admin) {
            return;
        }

        $company = app(CompanyContext::class)->get();

        abort_unless($company, 403, 'No active company selected.');

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($company->id);

        abort_unless(
            $user->companies()
                ->wherePivot('is_active', true)
                ->where('companies.id', $company->id)
                ->exists(),
            403,
            'You do not belong to this company.'
        );

        abort_unless(
            $user->can('leave.manage'),
            403,
            'You do not have permission to manage leave requests.'
        );
    }

    public function options(Request $request)
{
    $company = app(CompanyContext::class)->get();

    abort_unless($company, 403, 'No active company selected.');

    $this->authorizeLeaveAccess($request);

    $validated = $request->validate([
        'employee_id' => ['required', 'integer'],
        'leave_type_id' => ['required', 'integer'],
        'start_date' => ['required', 'date'],
    ]);

    $employee = Employee::query()
        ->where('company_id', $company->id)
        ->where('id', $validated['employee_id'])
        ->where('is_active', true)
        ->firstOrFail();

    $leaveType = LeaveType::query()
        ->where('company_id', $company->id)
        ->where('id', $validated['leave_type_id'])
        ->where('is_active', true)
        ->firstOrFail();

    $startDate = Carbon::parse($validated['start_date']);

    $policy = LeavePolicy::query()
        ->where('company_id', $company->id)
        ->where('leave_type_id', $leaveType->id)
        ->where('is_active', true)
        ->whereDate('effective_from', '<=', $startDate->toDateString())
        ->where(function ($query) use ($startDate) {
            $query->whereNull('effective_to')
                ->orWhereDate(
                    'effective_to',
                    '>=',
                    $startDate->toDateString()
                );
        })
        ->orderByDesc('effective_from')
        ->first();

    if (!$policy) {
        return response()->json([
            'policy' => null,
            'balance' => null,
            'message' => 'No active leave policy is available for this leave type on the selected date.',
        ]);
    }

    $balance = null;

    if ($leaveType->requires_balance) {
        $balance = \App\Models\LeaveBalance::query()
            ->where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $startDate->year)
            ->first();
    }

    return response()->json([
        'policy' => [
            'id' => $policy->id,
            'entitlement_days' => (float) $policy->entitlement_days,
            'accrual_method' => $policy->accrual_method,
            'carry_forward_allowed' => $policy->carry_forward_allowed,
            'max_carry_forward_days' => $policy->max_carry_forward_days
                !== null
                    ? (float) $policy->max_carry_forward_days
                    : null,
            'min_notice_days' => (int) $policy->min_notice_days,
            'max_consecutive_days' => $policy->max_consecutive_days
                !== null
                    ? (float) $policy->max_consecutive_days
                    : null,
            'allow_negative_balance' => $policy->allow_negative_balance,
            'requires_attachment' => $policy->requires_attachment,
        ],
        'balance' => $balance ? [
            'entitled_days' => (float) $balance->entitled_days,
            'accrued_days' => (float) $balance->accrued_days,
            'carried_forward_days' => (float) $balance->carried_forward_days,
            'used_days' => (float) $balance->used_days,
            'adjusted_days' => (float) $balance->adjusted_days,
            'pending_days' => (float) $balance->pending_days,
            'available_days' => (float) $balance->available_days,
        ] : null,
    ]);
}
}