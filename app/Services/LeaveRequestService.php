<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeavePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class LeaveRequestService
{
    public function __construct(
        protected LeaveBalanceService $leaveBalanceService
    ) {
    }

    /**
     * Create a pending leave request.
     */
   public function create(
    Employee $employee,
    LeaveType $leaveType,
    LeavePolicy $policy,
    array $data,
    int $submittedBy
): LeaveRequest {
    return DB::transaction(function () use (
        $employee,
        $leaveType,
        $policy,
        $data,
        $submittedBy
    ) {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        /**
         * Calendar days, inclusive.
         *
         * Example:
         * 1 Jan - 3 Jan = 3 days
         */
        $days = $startDate->diffInDays($endDate) + 1;

        if ($days <= 0) {
            throw ValidationException::withMessages([
                'end_date' => 'The selected leave period is invalid.',
            ]);
        }

        /**
         * Prevent overlapping pending/approved leave.
         */
        $overlap = LeaveRequest::query()
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $endDate->toDateString())
            ->whereDate('end_date', '>=', $startDate->toDateString())
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_date' =>
                    'The employee already has a pending or approved leave request overlapping these dates.',
            ]);
        }

        /**
         * Check maximum consecutive leave.
         */
        if (
            $policy->max_consecutive_days !== null
            && $days > (float) $policy->max_consecutive_days
        ) {
            throw ValidationException::withMessages([
                'end_date' =>
                    "This leave policy allows a maximum of {$policy->max_consecutive_days} consecutive days.",
            ]);
        }

        /**
         * Leave balance.
         */
        $balance = null;
        $before = null;
        $after = null;

        if ($leaveType->requires_balance) {
            $balance = \App\Models\LeaveBalance::query()
                ->where('company_id', $employee->company_id)
                ->where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $startDate->year)
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                throw ValidationException::withMessages([
                    'employee_id' =>
                        'No leave balance has been initialized for this employee and leave type.',
                ]);
            }

            $available = $this->leaveBalanceService
                ->calculateAvailable($balance);

            /**
             * Negative balances are allowed only when the policy says so.
             */
            if (
                !$policy->allow_negative_balance
                && $available < $days
            ) {
                throw ValidationException::withMessages([
                    'employee_id' =>
                        "Insufficient leave balance. Available: {$available} days; requested: {$days} days.",
                ]);
            }

            /**
             * Capture balance before reserving the leave.
             */
            $before = $this->leaveBalanceService
                ->calculateAvailable($balance);

            /**
             * Reserve the requested days as pending.
             */
            $balance->pending_days = (float) $balance->pending_days
                + (float) $days;

            $balance->save();
            $balance->refresh();

            /**
             * Calculate balance after the pending reservation.
             */
            $after = $this->leaveBalanceService
                ->calculateAvailable($balance);
        }

        /**
         * Create the leave request.
         */
        $leaveRequest = LeaveRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'leave_policy_id' => $policy->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'days' => $days,
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'attachment_path' => $data['attachment_path'] ?? null,
            'status' => 'pending',
            'submitted_by' => $submittedBy,
            'return_date' => $endDate->copy()->addDay()->toDateString(),
        ]);

        /**
         * Record the pending reservation in the audit trail.
         */
        if ($balance) {
            $this->leaveBalanceService->recordTransaction(
                balance: $balance,
                type: 'leave_pending',
                days: (float) $days,
                before: $before,
                after: $after,
                description: 'Leave request submitted',
                reason: $data['reason'] ?? null,
                sourceType: 'LeaveRequest',
                sourceId: $leaveRequest->id,
                createdBy: $submittedBy
            );
        }

        return $leaveRequest;
    });
}
    /**
     * Approve a pending leave request.
     */
    public function approve(
    LeaveRequest $leaveRequest,
    int $approvedBy
): LeaveRequest {
    return DB::transaction(function () use (
        $leaveRequest,
        $approvedBy
    ) {
        $leaveRequest = LeaveRequest::query()
            ->lockForUpdate()
            ->findOrFail($leaveRequest->id);

        if ($leaveRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' =>
                    'Only pending leave requests can be approved.',
            ]);
        }

        if ($leaveRequest->leaveType?->requires_balance) {
            $balance = $this->getBalanceForRequest($leaveRequest);

            if (!$balance) {
                throw ValidationException::withMessages([
                    'status' =>
                        'No leave balance exists for this request.',
                ]);
            }

            /*
             * Capture the available balance before approval.
             */
            $before = $this->leaveBalanceService
                ->calculateAvailable($balance);

            /*
             * Remove pending days.
             */
            $balance->pending_days = max(
                0,
                (float) $balance->pending_days
                - (float) $leaveRequest->days
            );

            /*
             * Add approved days to used.
             */
            $balance->used_days =
                (float) $balance->used_days
                + (float) $leaveRequest->days;

            $balance->save();

            /*
             * Refresh and calculate the new available balance.
             */
            $balance->refresh();

            $after = $this->leaveBalanceService
                ->calculateAvailable($balance);

            /*
             * Record the approved leave usage.
             */
           $this->leaveBalanceService->recordTransaction(
                balance: $balance,
                type: 'leave_used',
                days: (float) $leaveRequest->days,
                before: $before,
                after: $after,
                description: 'Leave request approved',
                reason: $leaveRequest->reason,
                sourceType: 'LeaveRequest',
                sourceId: $leaveRequest->id,
                createdBy: $approvedBy
            );
        }

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        return $leaveRequest->fresh();
    });
}

    /**
     * Reject a pending leave request.
     */
    public function reject(
    LeaveRequest $leaveRequest,
    int $rejectedBy,
    string $rejectionReason
): LeaveRequest {
    return DB::transaction(function () use (
        $leaveRequest,
        $rejectedBy,
        $rejectionReason
    ) {
        $leaveRequest = LeaveRequest::query()
            ->lockForUpdate()
            ->findOrFail($leaveRequest->id);

        if ($leaveRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' =>
                    'Only pending leave requests can be rejected.',
            ]);
        }

        if ($leaveRequest->leaveType?->requires_balance) {
            $balance = $this->getBalanceForRequest($leaveRequest);

            if ($balance) {
                // Capture available balance before reversing pending days.
                $before = $this->leaveBalanceService
                    ->calculateAvailable($balance);

                // Remove pending days.
                $balance->pending_days = max(
                    0,
                    (float) $balance->pending_days
                    - (float) $leaveRequest->days
                );

                $balance->save();
                $balance->refresh();

                // Calculate available balance after reversal.
                $after = $this->leaveBalanceService
                    ->calculateAvailable($balance);

                // Record the reversal in the audit trail.
                $this->leaveBalanceService->recordTransaction(
                    balance: $balance,
                    type: 'pending_reversed',
                    days: (float) $leaveRequest->days,
                    before: $before,
                    after: $after,
                    description: 'Leave request rejected',
                    reason: $rejectionReason,
                    sourceType: 'LeaveRequest',
                    sourceId: $leaveRequest->id,
                    createdBy: $rejectedBy
                );
            }
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $rejectionReason,
        ]);

        return $leaveRequest->fresh();
    });
}

    /**
     * Cancel a leave request.
     */
    public function cancel(
    LeaveRequest $leaveRequest,
    int $cancelledBy
): LeaveRequest {
    return DB::transaction(function () use (
        $leaveRequest,
        $cancelledBy
    ) {
        $leaveRequest = LeaveRequest::query()
            ->lockForUpdate()
            ->findOrFail($leaveRequest->id);

        if (
            !in_array(
                $leaveRequest->status,
                ['pending', 'approved'],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'status' =>
                    'Only pending or approved leave requests can be cancelled.',
            ]);
        }

        if ($leaveRequest->leaveType?->requires_balance) {
            $balance = $this->getBalanceForRequest($leaveRequest);

            if ($balance) {
                // Capture available balance before reversal.
                $before = $this->leaveBalanceService
                    ->calculateAvailable($balance);

                if ($leaveRequest->status === 'pending') {
                    $balance->pending_days = max(
                        0,
                        (float) $balance->pending_days
                        - (float) $leaveRequest->days
                    );
                }

                if ($leaveRequest->status === 'approved') {
                    $balance->used_days = max(
                        0,
                        (float) $balance->used_days
                        - (float) $leaveRequest->days
                    );
                }

                $balance->save();
                $balance->refresh();

                // Calculate available balance after reversal.
                $after = $this->leaveBalanceService
                    ->calculateAvailable($balance);

                $transactionType =
                    $leaveRequest->status === 'pending'
                        ? 'pending_reversed'
                        : 'leave_cancelled';

                $description =
                    $leaveRequest->status === 'pending'
                        ? 'Pending leave request cancelled'
                        : 'Approved leave request cancelled';

                // Record the cancellation in the audit trail.
                $this->leaveBalanceService->recordTransaction(
                    balance: $balance,
                    type: $transactionType,
                    days: (float) $leaveRequest->days,
                    before: $before,
                    after: $after,
                    description: $description,
                    reason: 'Leave request cancelled',
                    sourceType: 'LeaveRequest',
                    sourceId: $leaveRequest->id,
                    createdBy: $cancelledBy
                );
            }
        }

        $leaveRequest->update([
            'status' => 'cancelled',
            'cancelled_by' => $cancelledBy,
            'cancelled_at' => now(),
        ]);

        return $leaveRequest->fresh();
    });
}

    /**
     * Get the leave balance belonging to a request.
     */
    protected function getBalanceForRequest(
        LeaveRequest $leaveRequest
    ) {
        return \App\Models\LeaveBalance::query()
            ->where('company_id', $leaveRequest->company_id)
            ->where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where(
                'year',
                Carbon::parse($leaveRequest->start_date)->year
            )
            ->lockForUpdate()
            ->first();
    }
}