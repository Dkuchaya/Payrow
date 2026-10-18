<?php

namespace App\Services;

use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AttendanceService
{

    protected WorkScheduleService $workScheduleService;

    public function __construct(
        WorkScheduleService $workScheduleService
    ) {
        $this->workScheduleService = $workScheduleService;
        
    }

    /*
    |--------------------------------------------------------------------------
    | Manual Attendance
    |--------------------------------------------------------------------------
    */

    public function saveManualAttendance(
        Company $company,
        Employee $employee,
        array $data
    ): AttendanceRecord {
        $this->validateEmployee($company, $employee);

        return DB::transaction(function () use (
            $company,
            $employee,
            $data
        ) {
            $date = Carbon::parse($data['attendance_date'])
                ->startOfDay();

            $clockIn = !empty($data['clock_in_at'])
                ? Carbon::parse($data['clock_in_at'])
                : null;

            $clockOut = !empty($data['clock_out_at'])
                ? Carbon::parse($data['clock_out_at'])
                : null;

            if ($clockIn && $clockOut && $clockOut->lessThan($clockIn)) {
                throw new InvalidArgumentException(
                    'Clock-out time cannot be earlier than clock-in time.'
                );
            }

            $record = AttendanceRecord::query()
                ->where('company_id', $company->id)
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date)
                ->lockForUpdate()
                ->first();

            if (!$record) {
                $record = new AttendanceRecord();

                $record->company_id = $company->id;
                $record->employee_id = $employee->id;
                $record->attendance_date = $date;
            }

            $record->status = $data['status'] ?? 'present';

            $record->clock_in_at = $clockIn;
            $record->clock_out_at = $clockOut;

            $record->scheduled_hours = (float) (
                $data['scheduled_hours'] ?? 0
            );

            $record->break_minutes = (int) (
                $data['break_minutes'] ?? 0
            );

            $record->source = 'manual';

            $record->is_manual_adjustment = true;
            $record->adjusted_by = Auth::id();
            $record->adjusted_at = now();
            $record->adjustment_reason =
                $data['adjustment_reason'] ?? null;

            $record->notes = $data['notes'] ?? null;

            $this->calculateMetrics($record);

            $record->save();

            return $record->fresh([
                'employee',
                'device',
                'adjustedBy',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Process Biometric Punches
    |--------------------------------------------------------------------------
    */

    public function processEmployeePunches(
        Company $company,
        Employee $employee,
        Carbon|string $date
    ): ?AttendanceRecord {
        $this->validateEmployee($company, $employee);

        $date = Carbon::parse($date)->startOfDay();

        $punches = AttendancePunch::query()
            ->where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->whereDate('punch_date', $date)
            ->orderBy('punched_at')
            ->get();

        if ($punches->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use (
            $company,
            $employee,
            $date,
            $punches
        ) {
            $record = AttendanceRecord::query()
                ->where('company_id', $company->id)
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date)
                ->lockForUpdate()
                ->first();

            if (!$record) {
                $record = new AttendanceRecord();

                $record->company_id = $company->id;
                $record->employee_id = $employee->id;
                $record->attendance_date = $date;
            }

            $firstPunch = $punches->first();
            $lastPunch = $punches->last();

            $record->clock_in_at = $firstPunch->punched_at;

            if ($punches->count() > 1) {
                $record->clock_out_at = $lastPunch->punched_at;
            }

            $record->status = 'present';
            $record->source = 'device';

            $record->attendance_device_id =
                $firstPunch->attendance_device_id;

            $record->is_manual_adjustment = false;
            $record->adjusted_by = null;
            $record->adjusted_at = null;

            /*
             * Until company work schedules are implemented,
             * scheduled hours remain zero.
             */
            $record->scheduled_hours = $record->scheduled_hours ?? 0;

            $this->calculateMetrics($record);

            $record->save();

            AttendancePunch::query()
                ->whereIn('id', $punches->pluck('id'))
                ->update([
                    'is_processed' => true,
                    'processed_at' => now(),
                ]);

            return $record->fresh([
                'employee',
                'device',
            ]);
        });
    }

    /**
     * Get the employee's work schedule configuration
     * for a specific attendance date.
     */
    protected function getScheduleForAttendance(
        Employee $employee,
        Carbon $date
    ): ?array {
        $scheduleDay = $this->workScheduleService->getScheduleDay(
            $employee,
            $date
        );

        if (!$scheduleDay) {
            return null;
        }

        if (!$scheduleDay->is_working_day) {
            return [
                'is_working_day' => false,
                'start_time' => null,
                'end_time' => null,
                'break_minutes' => 0,
                'grace_period_minutes' => 0,
                'early_departure_grace_minutes' => 0,
                'allow_overtime' => false,
                'overtime_after_minutes' => 0,
            ];
        }

        $schedule = $scheduleDay->schedule;

        return [
            'is_working_day' => true,
            'start_time' => $scheduleDay->start_time
                ?? $schedule?->default_start_time,
            'end_time' => $scheduleDay->end_time
                ?? $schedule?->default_end_time,
            'break_minutes' => (int) (
                $scheduleDay->break_minutes
                ?? $schedule?->default_break_minutes
                ?? 0
            ),
            'grace_period_minutes' => (int) (
                $scheduleDay->grace_period_minutes
                ?? $schedule?->grace_period_minutes
                ?? 0
            ),
            'early_departure_grace_minutes' => (int) (
                $scheduleDay->early_departure_grace_minutes
                ?? $schedule?->early_departure_grace_minutes
                ?? 0
            ),
            'allow_overtime' => (bool) (
                $scheduleDay->allow_overtime
                ?? $schedule?->allow_overtime
                ?? false
            ),
            'overtime_after_minutes' => (int) (
                $schedule?->overtime_after_minutes
                ?? 0
            ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Calculate Attendance Metrics
    |--------------------------------------------------------------------------
    */

   public function calculateMetrics(
    AttendanceRecord $record
): AttendanceRecord {
    $record->worked_hours = 0;
    $record->overtime_hours = 0;
    $record->late_minutes = 0;
    $record->early_departure_minutes = 0;

    if (
        !$record->clock_in_at ||
        !$record->clock_out_at
    ) {
        return $record;
    }

    $date = Carbon::parse(
        $record->attendance_date
    )->startOfDay();

    $schedule = $this->getScheduleForAttendance(
        $record->employee,
        $date
    );

    /*
     * No work schedule assigned.
     *
     * Keep the existing attendance calculation
     * using the manually supplied scheduled hours.
     */
    if (!$schedule) {
        $totalMinutes = $record->clock_in_at
            ->diffInMinutes($record->clock_out_at);

        $breakMinutes = max(
            0,
            (int) $record->break_minutes
        );

        $workedMinutes = max(
            0,
            $totalMinutes - $breakMinutes
        );

        $record->worked_hours = round(
            $workedMinutes / 60,
            2
        );

        $scheduledMinutes = max(
            0,
            (int) round(
                ((float) $record->scheduled_hours) * 60
            )
        );

        if ($scheduledMinutes > 0) {
            $overtimeMinutes = max(
                0,
                $workedMinutes - $scheduledMinutes
            );

            $record->overtime_hours = round(
                $overtimeMinutes / 60,
                2
            );
        }

        return $record;
    }

    /*
     * Non-working day.
     */
   if (!$schedule['is_working_day']) {
    $record->scheduled_hours = 0;
    $record->break_minutes = 0;

    $clockIn = $record->clock_in_at;
    $clockOut = $record->clock_out_at;

    if ($clockOut->lessThan($clockIn)) {
        $clockOut = $clockOut->copy()->addDay();
    }

    $workedMinutes = max(
        0,
        $clockIn->diffInMinutes($clockOut)
    );

    $record->worked_hours = round(
        $workedMinutes / 60,
        2
    );

    // Any work performed on a scheduled
    // non-working day is treated as overtime.
    $record->overtime_hours = round(
        $workedMinutes / 60,
        2
    );

    $record->late_minutes = 0;
    $record->early_departure_minutes = 0;

    return $record;
}

    /*
     * Update attendance record with the
     * employee's actual schedule configuration.
     */
    $record->break_minutes = $schedule['break_minutes'];

    /*
     * Calculate scheduled minutes from the
     * schedule's start and end times.
     */
    if (
        !$schedule['start_time'] ||
        !$schedule['end_time']
    ) {
        return $record;
    }

    $scheduledStart = Carbon::parse(
        $date->format('Y-m-d') . ' ' . $schedule['start_time']
    );

    $scheduledEnd = Carbon::parse(
        $date->format('Y-m-d') . ' ' . $schedule['end_time']
    );

    /*
     * Handle overnight schedules.
     *
     * Example:
     * 22:00 → 06:00
     */
    if ($scheduledEnd->lessThanOrEqualTo($scheduledStart)) {
        $scheduledEnd->addDay();
    }

    $scheduledMinutes = max(
        0,
        $scheduledStart->diffInMinutes($scheduledEnd)
        - $schedule['break_minutes']
    );

    $record->scheduled_hours = round(
        $scheduledMinutes / 60,
        2
    );

    /*
     * Calculate actual worked time.
     */
    $clockIn = $record->clock_in_at;
    $clockOut = $record->clock_out_at;

    /*
     * If the clock-out is earlier than clock-in,
     * treat it as an overnight attendance.
     */
    if ($clockOut->lessThan($clockIn)) {
        $clockOut = $clockOut->copy()->addDay();
    }

    $totalMinutes = $clockIn->diffInMinutes(
        $clockOut
    );

    $breakMinutes = max(
        0,
        (int) $record->break_minutes
    );

    $workedMinutes = max(
        0,
        $totalMinutes - $breakMinutes
    );

    $record->worked_hours = round(
        $workedMinutes / 60,
        2
    );

    /*
     * Calculate late arrival.
     *
     * Employee is only considered late after
     * the configured grace period.
     */
    $graceMinutes = max(
        0,
        (int) $schedule['grace_period_minutes']
    );

    $lateThreshold = $scheduledStart->copy()
        ->addMinutes($graceMinutes);

    if ($clockIn->greaterThan($lateThreshold)) {
        $record->late_minutes = $lateThreshold->diffInMinutes(
            $clockIn
        );
    }

    /*
     * Calculate early departure.
     *
     * Employee is only considered early if they
     * leave before the allowed departure threshold.
     */
    $earlyGraceMinutes = max(
        0,
        (int) $schedule['early_departure_grace_minutes']
    );

    $earlyThreshold = $scheduledEnd->copy()
        ->subMinutes($earlyGraceMinutes);

    if ($clockOut->lessThan($earlyThreshold)) {
        $record->early_departure_minutes =
            $clockOut->diffInMinutes($earlyThreshold);
    }

    /*
     * Calculate overtime.
     *
     * Overtime is only allowed when the schedule
     * permits it.
     */
    if ($schedule['allow_overtime']) {
        $overtimeThreshold = $scheduledEnd->copy()
            ->addMinutes(
                max(
                    0,
                    (int) $schedule['overtime_after_minutes']
                )
            );

        if ($clockOut->greaterThan($overtimeThreshold)) {
            $overtimeMinutes = $overtimeThreshold->diffInMinutes(
                $clockOut
            );

            $record->overtime_hours = round(
                $overtimeMinutes / 60,
                2
            );
        }
    }

    return $record;
}

public function updateManualAttendance(
    Company $company,
    AttendanceRecord $record,
    array $data
): AttendanceRecord {
    if ((int) $record->company_id !== (int) $company->id) {
        throw new InvalidArgumentException(
            'Attendance record does not belong to the active company.'
        );
    }

    $employee = Employee::query()
        ->where('company_id', $company->id)
        ->where('employment_status', 'active')
        ->findOrFail($record->employee_id);

    $attendanceDate = Carbon::parse(
        $data['attendance_date']
    );

    $clockIn = !empty($data['clock_in_at'])
        ? Carbon::parse($data['clock_in_at'])
        : null;

    $clockOut = !empty($data['clock_out_at'])
        ? Carbon::parse($data['clock_out_at'])
        : null;

    $record->attendance_date = $attendanceDate;
    $record->clock_in_at = $clockIn;
    $record->clock_out_at = $clockOut;
    $record->status = $data['status'] ?? 'present';
    $record->adjustment_reason = $data['adjustment_reason'] ?? null;
    $record->notes = $data['notes'] ?? null;

    // This record has been manually adjusted.
    $record->is_manual_adjustment = true;
    $record->adjusted_at = now();

    /*
     * Recalculate attendance metrics using the employee's
     * assigned work schedule.
     */
    if ($clockIn && $clockOut) {
        $record = $this->calculateMetrics(
            $record,
            $employee,
            $attendanceDate
        );
    } else {
        $record->scheduled_hours = 0;
        $record->worked_hours = 0;
        $record->overtime_hours = 0;
        $record->late_minutes = 0;
        $record->early_departure_minutes = 0;
        $record->break_minutes = 0;
    }

    $record->source = 'manual';

    $record->save();

    return $record->fresh([
        'employee',
        'device',
    ]);
}

    /*
    |--------------------------------------------------------------------------
    | Employee Validation
    |--------------------------------------------------------------------------
    */

    protected function validateEmployee(
        Company $company,
        Employee $employee
    ): void {
        if ((int) $employee->company_id !== (int) $company->id) {
            throw new InvalidArgumentException(
                'Employee does not belong to the active company.'
            );
        }
    }
}