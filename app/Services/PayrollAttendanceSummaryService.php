<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\EmployeeWorkSchedule;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use Carbon\Carbon;

class PayrollAttendanceSummaryService
{
    /**
     * Build the attendance and leave summary for one payroll employee.
     */
    public function summarize(
        PayrollRunItem $item,
        PayrollPeriod $period,
        Company $company
    ): array {
        $employee = $item->employee;

        $startDate = Carbon::parse($period->start_date)->startOfDay();
        $endDate = Carbon::parse($period->end_date)->startOfDay();
        $today = Carbon::today();

        // Get attendance records for the payroll period.
        $attendanceRecords = AttendanceRecord::query()
            ->where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->get()
            ->keyBy(function (AttendanceRecord $record) {
                return $record->attendance_date->toDateString();
            });

        // Get approved leave requests.
        $leaveRequests = LeaveRequest::query()
            ->where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $endDate->toDateString())
            ->whereDate('end_date', '>=', $startDate->toDateString())
            ->with('leaveType')
            ->get();

        // Get applicable work schedules.
        $scheduleAssignments = EmployeeWorkSchedule::query()
            ->where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->where('is_primary', true)
            ->whereDate('effective_from', '<=', $endDate->toDateString())
            ->where(function ($query) use ($startDate) {
                $query
                    ->whereNull('effective_to')
                    ->orWhereDate(
                        'effective_to',
                        '>=',
                        $startDate->toDateString()
                    );
            })
            ->with('workSchedule.days')
            ->get();

        $scheduledDays = 0;
        $scheduledHours = 0;
        $workedDays = 0;
        $paidLeaveDays = 0;
        $unpaidLeaveDays = 0;
        $absentDays = 0;
        $lateMinutes = 0;
        $earlyDepartureMinutes = 0;
        $overtimeHours = 0;

        // Process every date in the payroll period.
        for (
            $date = $startDate->copy();
            $date->lte($endDate);
            $date->addDay()
        ) {
            $dateString = $date->toDateString();

            // Find the schedule applicable on this date.
            $schedule = $this->getScheduleForDate(
                $scheduleAssignments,
                $date
            );

            if (!$schedule) {
                continue;
            }

            $dayOfWeek = $date->isoWeekday();

            $scheduleDay = $schedule->days
                ->firstWhere('day_of_week', $dayOfWeek);

            if (!$scheduleDay || !$scheduleDay->is_working_day) {
                continue;
            }

            // Calculate scheduled days and hours.
            $scheduledDays++;

            $scheduledHours += $this->getScheduledHours($scheduleDay);

            $attendance = $attendanceRecords->get($dateString);

            // Find approved leave covering this date.
            $leave = $leaveRequests->first(
                function (LeaveRequest $request) use ($date) {
                    return $date->between(
                        Carbon::parse($request->start_date)->startOfDay(),
                        Carbon::parse($request->end_date)->startOfDay()
                    );
                }
            );

            // Attendance takes precedence over leave.
           // Attendance takes precedence over leave.
            if ($attendance) {

                // Explicitly marked absent.
                if ($attendance->status === 'absent') {

                    // Only count past dates as absences.
                    if ($date->lt($today)) {
                        $absentDays++;
                    }

                    continue;
                }

                // Process normal attendance.
                $workedDays++;

                $lateMinutes += (int) $attendance->late_minutes;

                $earlyDepartureMinutes +=
                    (int) $attendance->early_departure_minutes;

                $overtimeHours +=
                    (float) $attendance->overtime_hours;

                continue;
            }

            // Check approved leave.
            if ($leave && $leave->leaveType) {
                if ($leave->leaveType->is_paid) {
                    $paidLeaveDays++;
                } else {
                    $unpaidLeaveDays++;
                }

                continue;
            }

            // Do not count today or future days as absences.
            if ($date->gte($today)) {
                continue;
            }

            // No attendance or approved leave on a past working day.
            $absentDays++;
        }

        // Return the completed attendance summary.
        return [
            'scheduled_days' => $scheduledDays,
            'scheduled_hours' => round($scheduledHours, 2),
            'worked_days' => $workedDays,
            'paid_leave_days' => $paidLeaveDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'absent_days' => $absentDays,
            'late_minutes' => $lateMinutes,
            'early_departure_minutes' => $earlyDepartureMinutes,
            'overtime_hours' => round($overtimeHours, 2),
        ];
    }

    /**
     * Find the employee work schedule applicable to a particular date.
     */
    private function getScheduleForDate(
        $assignments,
        Carbon $date
    ) {
        return $assignments
            ->filter(function (EmployeeWorkSchedule $assignment) use ($date) {
                if ($assignment->effective_from->gt($date)) {
                    return false;
                }

                if (
                    $assignment->effective_to !== null &&
                    $assignment->effective_to->lt($date)
                ) {
                    return false;
                }

                return true;
            })
            ->sortByDesc('effective_from')
            ->first()
            ?->workSchedule;
    }

    /**
     * Calculate scheduled working hours from the daily schedule.
     */
    private function getScheduledHours($scheduleDay): float
    {
        if (
            empty($scheduleDay->start_time) ||
            empty($scheduleDay->end_time)
        ) {
            return 0;
        }

        $start = Carbon::parse($scheduleDay->start_time);
        $end = Carbon::parse($scheduleDay->end_time);

        $minutes = $start->diffInMinutes($end);

        $breakMinutes = (int) ($scheduleDay->break_minutes ?? 0);

        $workedMinutes = max(
            0,
            $minutes - $breakMinutes
        );

        return round($workedMinutes / 60, 2);
    }
}
