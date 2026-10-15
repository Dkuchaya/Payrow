<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeWorkSchedule;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WorkScheduleService
{
    /**
     * Get the employee's active schedule for a specific date.
     */
    public function getScheduleForEmployee(
        Employee $employee,
        Carbon|string $date
    ): ?EmployeeWorkSchedule {
        $date = Carbon::parse($date)->startOfDay();

        return EmployeeWorkSchedule::query()
            ->with([
                'schedule.days',
            ])
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query
                    ->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date);
            })
            ->orderByDesc('is_primary')
            ->orderByDesc('effective_from')
            ->first();
    }

    /**
     * Get the schedule configuration for a specific date.
     */
    public function getScheduleDay(
        Employee $employee,
        Carbon|string $date
    ): ?WorkScheduleDay {
        $date = Carbon::parse($date);

        $assignment = $this->getScheduleForEmployee(
            $employee,
            $date
        );

        if (!$assignment || !$assignment->schedule) {
            return null;
        }

        // ISO weekday:
        // Monday = 1
        // Sunday = 7
        $dayOfWeek = $date->dayOfWeekIso;

        return $assignment->schedule
            ->days
            ->firstWhere('day_of_week', $dayOfWeek);
    }

    /**
     * Assign a schedule to an employee.
     */
    public function assignSchedule(
                Company $company,
                Employee $employee,
                WorkSchedule $schedule,
                array $data = []
            ): EmployeeWorkSchedule {
                if ((int) $employee->company_id !== (int) $company->id) {
                    throw new InvalidArgumentException(
                        'Employee does not belong to the active company.'
                    );
                }

                if ((int) $schedule->company_id !== (int) $company->id) {
                    throw new InvalidArgumentException(
                        'Work schedule does not belong to the active company.'
                    );
                }

                    $effectiveFrom = Carbon::parse(
                        $data['effective_from'] ?? now()
                    )->startOfDay();

                    $effectiveTo = !empty($data['effective_to'])
                        ? Carbon::parse($data['effective_to'])->startOfDay()
                        : null;

                    if (
                        $effectiveTo &&
                        $effectiveTo->lt($effectiveFrom)
                    ) {
                        throw new InvalidArgumentException(
                            'Effective end date cannot be before the start date.'
                        );
                    }

               return DB::transaction(function () use (
                $company,
                $employee,
                $schedule,
                $data,
                $effectiveFrom,
                $effectiveTo
            ) {
                $isPrimary = (bool) ($data['is_primary'] ?? true);

                if ($isPrimary) {
                    $newScheduleStart = $effectiveFrom->copy()->startOfDay();

                    // Find the employee's current open-ended primary schedule.
                    $existingAssignments = EmployeeWorkSchedule::query()
                        ->where('company_id', $company->id)
                        ->where('employee_id', $employee->id)
                        ->whereNull('effective_to')
                        ->whereDate('effective_from', '<', $newScheduleStart)
                        ->get();

                    foreach ($existingAssignments as $existingAssignment) {
                        $existingAssignment->update([
                            'effective_to' => $newScheduleStart->copy()->subDay(),
                            'is_primary' => false,
                        ]);
                    }
                }

                // Now verify that the new period does not overlap
                // any remaining assignment.
                $this->ensureNoOverlap(
                    $employee,
                    $effectiveFrom,
                    $effectiveTo
                );

                return EmployeeWorkSchedule::create([
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'work_schedule_id' => $schedule->id,
                    'effective_from' => $effectiveFrom,
                    'effective_to' => $effectiveTo,
                    'is_primary' => $isPrimary,
                    'notes' => $data['notes'] ?? null,
                    'assigned_by' => $data['assigned_by'] ?? auth()->id(),
                ]);
            });
    }

    /**
     * Ensure employee does not have overlapping schedule periods.
     */
    protected function ensureNoOverlap(
        Employee $employee,
        Carbon $from,
        ?Carbon $to = null,
        ?int $ignoreId = null
    ): void {
        $query = EmployeeWorkSchedule::query()
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        $query->where(function ($query) use ($from, $to) {
            // Existing schedule starts before the new period ends
            $query->whereDate(
                'effective_from',
                '<=',
                $to ?? $from
            );

            // AND existing schedule has no end,
            // OR existing schedule ends after the new period starts.
            $query->where(function ($query) use ($from) {
                $query
                    ->whereNull('effective_to')
                    ->orWhereDate(
                        'effective_to',
                        '>=',
                        $from
                    );
            });
        });

        if ($query->exists()) {
            throw new InvalidArgumentException(
                'The employee already has a work schedule covering this period.'
            );
        }
    }

        /**
     * Update an existing employee work schedule assignment.
     */
    public function updateScheduleAssignment(
        Company $company,
        Employee $employee,
        EmployeeWorkSchedule $assignment,
        WorkSchedule $schedule,
        array $data = []
    ): EmployeeWorkSchedule {
        if ((int) $employee->company_id !== (int) $company->id) {
            throw new InvalidArgumentException(
                'Employee does not belong to the active company.'
            );
        }

        if ((int) $assignment->company_id !== (int) $company->id) {
            throw new InvalidArgumentException(
                'Schedule assignment does not belong to the active company.'
            );
        }

        if ((int) $assignment->employee_id !== (int) $employee->id) {
            throw new InvalidArgumentException(
                'Schedule assignment does not belong to this employee.'
            );
        }

        if ((int) $schedule->company_id !== (int) $company->id) {
            throw new InvalidArgumentException(
                'Work schedule does not belong to the active company.'
            );
        }

        $effectiveFrom = Carbon::parse(
            $data['effective_from'] ?? $assignment->effective_from
        )->startOfDay();

        $effectiveTo = !empty($data['effective_to'])
            ? Carbon::parse($data['effective_to'])->startOfDay()
            : null;

        if (
            $effectiveTo &&
            $effectiveTo->lt($effectiveFrom)
        ) {
            throw new InvalidArgumentException(
                'Effective end date cannot be before the start date.'
            );
        }

        return DB::transaction(function () use (
            $company,
            $employee,
            $assignment,
            $schedule,
            $data,
            $effectiveFrom,
            $effectiveTo
        ) {
            /*
            * Check for overlapping assignments while ignoring
            * the assignment currently being edited.
            */
            $this->ensureNoOverlap(
                $employee,
                $effectiveFrom,
                $effectiveTo,
                $assignment->id
            );

            /*
            * If this assignment is being made primary,
            * remove primary status from other open assignments.
            */
            if (($data['is_primary'] ?? false) === true) {
                EmployeeWorkSchedule::query()
                    ->where('company_id', $company->id)
                    ->where('employee_id', $employee->id)
                    ->whereKeyNot($assignment->id)
                    ->whereNull('effective_to')
                    ->update([
                        'is_primary' => false,
                    ]);
            }

            $assignment->update([
                'work_schedule_id' => $schedule->id,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'is_primary' => $data['is_primary'] ?? false,
                'notes' => $data['notes'] ?? null,
            ]);

            return $assignment->fresh([
                'workSchedule',
            ]);
        });
    }
}