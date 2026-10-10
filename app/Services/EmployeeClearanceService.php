<?php

namespace App\Services;

use App\Models\EmployeeSeparation;
use App\Models\EmployeeSeparationClearance;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EmployeeClearanceService
{
    /**
     * Create the standard clearance checklist for a separation.
     */
    public function createDefaultClearances(
        EmployeeSeparation $separation
    ): void {
        // Prevent duplicate checklists.
        if ($separation->clearances()->exists()) {
            return;
        }

        $defaults = [
            [
                'category' => 'HR',
                'title' => 'HR Records Clearance',
                'description' => 'Confirm that employee HR records, contracts and personnel documents are complete.',
            ],
            [
                'category' => 'HR',
                'title' => 'Exit Interview',
                'description' => 'Complete the employee exit interview and record any relevant feedback.',
            ],
            [
                'category' => 'Department',
                'title' => 'Department Handover',
                'description' => 'Confirm that duties, responsibilities and outstanding work have been handed over.',
            ],
            [
                'category' => 'Finance',
                'title' => 'Finance Clearance',
                'description' => 'Confirm outstanding advances, loans, deductions and other financial obligations.',
            ],
            [
                'category' => 'IT',
                'title' => 'IT Clearance',
                'description' => 'Disable system access and confirm return of IT equipment and credentials.',
            ],
            [
                'category' => 'Property',
                'title' => 'Company Property Return',
                'description' => 'Confirm that company property, equipment, keys, cards and other assets have been returned.',
            ],
            [
                'category' => 'Documents',
                'title' => 'Document Clearance',
                'description' => 'Confirm that required employee documents and exit documentation are complete.',
            ],
        ];

        DB::transaction(function () use ($separation, $defaults) {
            foreach ($defaults as $clearance) {
                EmployeeSeparationClearance::create([
                    'company_id' => $separation->company_id,
                    'employee_separation_id' => $separation->id,
                    'category' => $clearance['category'],
                    'title' => $clearance['title'],
                    'description' => $clearance['description'],
                    'is_required' => true,
                    'status' => 'pending',
                ]);
            }
        });
    }

    /**
     * Determine whether all required clearances are complete.
     */
    public function canProceedToFinalPayroll(
        EmployeeSeparation $separation
    ): bool {
        return ! $separation->clearances()
            ->where('is_required', true)
            ->where('status', '!=', 'cleared')
            ->exists();
    }

    /**
     * Clear a clearance item.
     */
    public function clear(
        EmployeeSeparationClearance $clearance,
        int $userId,
        ?string $comments = null
    ): void {
        if ($clearance->status === 'cleared') {
            return;
        }

        $clearance->update([
            'status' => 'cleared',
            'comments' => $comments,
            'cleared_by' => $userId,
            'cleared_at' => now(),
        ]);
    }

    /**
     * Reject a clearance item.
     */
    public function reject(
        EmployeeSeparationClearance $clearance,
        int $userId,
        string $comments
    ): void {
        if (trim($comments) === '') {
            throw new RuntimeException(
                'A reason is required when rejecting a clearance.'
            );
        }

        $clearance->update([
            'status' => 'rejected',
            'comments' => $comments,
            'cleared_by' => $userId,
            'cleared_at' => null,
        ]);
    }
}