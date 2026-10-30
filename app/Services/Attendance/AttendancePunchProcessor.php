<?php
namespace App\Services\Attendance;

use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Services\AttendanceService;
use App\Services\WorkScheduleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AttendancePunchProcessor
{

   public function __construct(
    protected AttendanceService $attendanceService,
    protected WorkScheduleService $workScheduleService
) {
}
    public function processEmployee(
        Company $company,
        Employee $employee,
        Carbon $date
    ): ?AttendanceRecord {

        $scheduleDay = $this->workScheduleService->getScheduleDay(
        $employee,
        $date
    );
        if ((int) $employee->company_id !== (int) $company->id) {
            throw new RuntimeException(
                'Employee does not belong to the active company.'
            );
        }

        return DB::transaction(function () use (
            $company,
            $employee,
            $date
        ) {
            $scheduleDay = $this->workScheduleService->getScheduleDay(
    $employee,
    $date
);

    $punchQuery = AttendancePunch::query()
        ->where('company_id', $company->id)
        ->where('employee_id', $employee->id);

    if (
        $scheduleDay &&
        $scheduleDay->is_working_day &&
        $scheduleDay->start_time &&
        $scheduleDay->end_time
    ) {
        $startTime = Carbon::parse(
            $date->toDateString() . ' ' . $scheduleDay->start_time
        );

        $endTime = Carbon::parse(
            $date->toDateString() . ' ' . $scheduleDay->end_time
        );

        /*
        * If end time is earlier than start time,
        * this is an overnight shift.
        */
        if ($endTime->lessThanOrEqualTo($startTime)) {
            $endTime->addDay();
        }

        $windowStart = $startTime->copy()->subHours(4);
        $windowEnd = $endTime->copy()->addHours(4);

        $punchQuery
            ->whereBetween('punched_at', [
                $windowStart,
                $windowEnd,
            ]);
    } else {
        $punchQuery->whereDate(
            'punch_date',
            $date->toDateString()
        );
    }

    $punches = $punchQuery
        ->orderBy('punched_at')
        ->orderBy('id')
        ->get();

            if ($punches->isEmpty()) {
                return null;
            }

            $clockIn = $punches->first()->punched_at;
            $clockOut = $punches->count() > 1
                ? $punches->last()->punched_at
                : null;

            $record = AttendanceRecord::query()
                ->where('company_id', $company->id)
                ->where('employee_id', $employee->id)
                ->whereDate(
                    'attendance_date',
                    $date->toDateString()
                )
                ->first();

            if (!$record) {
                $record = new AttendanceRecord();

                $record->company_id = $company->id;
                $record->employee_id = $employee->id;
                $record->attendance_date = $date;
            }

            $record->clock_in_at = $clockIn;
            $record->clock_out_at = $clockOut;

            $record->status = $this->determineStatus(
                $clockIn,
                $clockOut
            );

            $record->source = 'device';

            $record->save();

            /*
             * Only mark punches as processed after the
             * attendance record has been successfully saved.
             */
            // Calculate attendance metrics using the employee's work schedule.
            $record = $this->attendanceService->calculateMetrics($record);

            $record->save();

            /**
             * Only mark punches as processed after the
             * attendance record has been successfully saved.
             */
            $punches->each(function (AttendancePunch $punch) {
                $punch->update([
                    'is_processed' => true,
                    'processed_at' => now(),
                ]);
            });

            return $record->fresh([
                'employee',
                'device',
            ]);
        });
    }

    protected function determineStatus(
        Carbon $clockIn,
        ?Carbon $clockOut
    ): string {
        if (!$clockIn) {
            return 'absent';
        }

        if (!$clockOut) {
            return 'incomplete';
        }

        return 'present';
    }
}