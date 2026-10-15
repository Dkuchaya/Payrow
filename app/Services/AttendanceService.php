<?php

namespace App\Services;

use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AttendanceService
{
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