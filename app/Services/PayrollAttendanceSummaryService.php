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

        /*
         * Get the employee's attendance records for the payroll period.
         */
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

        /*
         * Get approved leave requests that overlap the payroll period.
         */
        $leaveRequests = LeaveRequest::query()
            ->where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $endDate->toDateString())
            ->whereDate('end_date', '>=', $startDate->toDateString())
            ->with('leaveType')
            ->get();

        /*
         * Get the employee's applicable work schedules.
         */
        $scheduleAssignments = EmployeeWorkSchedule::query()
            ->where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->where('is_primary', true)
            ->whereDate('effective_from', '<=', $endDate->toDateString())
            ->where(function ($query) use ($startDate) {
                $query
                    ->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $startDate->toDateString());
            })
            ->with([
                'workSchedule.days',
            ])
            ->get();

        $scheduledDays = 0;
        $workedDays = 0;
        $paidLeaveDays = 0;
        $unpaidLeaveDays = 0;
        $absentDays = 0;
        $lateMinutes = 0;
        $earlyDepartureMinutes = 0;
        $overtimeHours = 0;

        for (
            $date = $startDate->copy();
            $date->lte($endDate);
            $date->addDay()
        ) {
            $dateString = $date->toDateString();

            /*
             * Find the work schedule that applies on this date.
             */
            $schedule = $this->getScheduleForDate(
                $scheduleAssignments,
                $date
            );

            /*
             * No applicable schedule means we cannot classify
             * this date as a scheduled working day.
             */
            if (!$schedule) {
                continue;
            }

            $dayOfWeek = $date->isoWeekday();

            $scheduleDay = $schedule->days
                ->firstWhere('day_of_week', $dayOfWeek);

            /*
             * If the schedule does not define this weekday,
             * or it is explicitly a non-working day, skip it.
             */
            if (!$scheduleDay || !$scheduleDay->is_working_day) {
                continue;
            }

            $scheduledDays++;

            $attendance = $attendanceRecords->get($dateString);

            $leave = $leaveRequests->first(
                function (LeaveRequest $request) use ($date) {
                    return $date->between(
                        Carbon::parse($request->start_date)->startOfDay(),
                        Carbon::parse($request->end_date)->startOfDay()
                    );
                }
            );

            /*
             * Attendance takes precedence when an employee actually
             * has an attendance record for the working day.
             */
            if ($attendance) {
                $workedDays++;

                $lateMinutes += (int) $attendance->late_minutes;
                $earlyDepartureMinutes += (int) $attendance->early_departure_minutes;
                $overtimeHours += (float) $attendance->overtime_hours;

                continue;
            }

            /*
             * No attendance: check for approved leave.
             */
            if ($leave && $leave->leaveType) {
                if ($leave->leaveType->is_paid) {
                    $paidLeaveDays++;
                } else {
                    $unpaidLeaveDays++;
                }

                continue;
            }

            /*
             * Scheduled working day with neither attendance
             * nor approved leave = absence.
             */
            $absentDays++;
        }

        return [
            'scheduled_days' => $scheduledDays,
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
}