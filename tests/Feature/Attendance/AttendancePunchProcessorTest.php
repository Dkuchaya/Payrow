<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceDevice;
use App\Models\AttendanceDeviceEmployee;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Services\Attendance\AttendancePunchProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use App\Services\WorkScheduleService;


class AttendancePunchProcessorTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_punches_are_processed_into_attendance_record(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'legal_name' => 'Test Company Limited',
            'slug' => 'test-company',
            'email' => 'test@example.com',
            'phone' => '0977000000',
            'country' => 'Zambia',
            'currency' => 'ZMW',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'company_id' => $company->id,
            'employee_number' => 'EMP-001',
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'employment_status' => 'active',
            'employment_type' => 'permanent',
            'hire_date' => '2026-01-01',
            'salary_currency' => 'ZMW',
            'pay_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $device = AttendanceDevice::create([
            'company_id' => $company->id,
            'name' => 'Test Device',
            'device_type' => 'biometric',
            'manufacturer' => 'Hikvision',
            'connection_type' => 'api',
            'ip_address' => '192.168.1.100',
            'port' => 80,
            'timezone' => 'Africa/Lusaka',
            'sync_method' => 'pull',
            'username' => 'admin',
            'password' => 'test-password',
            'is_active' => true,
        ]);

        AttendanceDeviceEmployee::create([
            'company_id' => $company->id,
            'attendance_device_id' => $device->id,
            'employee_id' => $employee->id,
            'device_user_id' => '4465666',
            'is_active' => true,
        ]);

        AttendancePunch::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'attendance_device_id' => $device->id,
            'punch_date' => '2026-10-20',
            'punched_at' => '2026-10-20 07:58:00',
            'punch_type' => 'unknown',
            'source' => 'device',
            'external_id' => 'HV-001',
            'device_user_id' => '4465666',
            'is_processed' => false,
        ]);

        AttendancePunch::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'attendance_device_id' => $device->id,
            'punch_date' => '2026-10-20',
            'punched_at' => '2026-10-20 17:05:00',
            'punch_type' => 'unknown',
            'source' => 'device',
            'external_id' => 'HV-002',
            'device_user_id' => '4465666',
            'is_processed' => false,
        ]);

        $processor = app(
            AttendancePunchProcessor::class
        );

        $record = $processor->processEmployee(
            $company,
            $employee,
            \Carbon\Carbon::parse('2026-10-20')
        );

        $this->assertNotNull($record);

        $this->assertSame(
            '07:58',
            $record->clock_in_at->format('H:i')
        );

        $this->assertSame(
            '17:05',
            $record->clock_out_at->format('H:i')
        );

        $this->assertSame(
            'device',
            $record->source
        );

        $this->assertSame(
            2,
            AttendancePunch::query()
                ->where('employee_id', $employee->id)
                ->where('is_processed', true)
                ->count()
        );

        $this->assertSame(
            1,
            AttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->whereDate(
                    'attendance_date',
                    '2026-10-20'
                )
                ->count()
        );
    }


    public function test_multiple_punches_use_first_as_clock_in_and_last_as_clock_out(): void
{
    $company = $this->createCompany();

    $employee = $this->createEmployee($company);

    foreach ([
        ['07:55:00', 'HV-001'],
        ['12:00:00', 'HV-002'],
        ['13:00:00', 'HV-003'],
        ['17:10:00', 'HV-004'],
    ] as [$time, $externalId]) {
        AttendancePunch::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'attendance_device_id' => null,
            'punch_date' => '2026-10-20',
            'punched_at' => "2026-10-20 {$time}",
            'punch_type' => 'unknown',
            'source' => 'device',
            'external_id' => $externalId,
            'device_user_id' => '4465666',
            'is_processed' => false,
        ]);
    }

    $processor = app(AttendancePunchProcessor::class);

    $record = $processor->processEmployee(
        $company,
        $employee,
        \Carbon\Carbon::parse('2026-10-20')
    );

    $this->assertNotNull($record);

    $this->assertSame(
        '07:55',
        $record->clock_in_at->format('H:i')
    );

    $this->assertSame(
        '17:10',
        $record->clock_out_at->format('H:i')
    );

    $this->assertSame(
        4,
        AttendancePunch::query()
            ->where('employee_id', $employee->id)
            ->where('is_processed', true)
            ->count()
    );
}
public function test_single_punch_creates_incomplete_attendance(): void
{
    $company = $this->createCompany();

    $employee = $this->createEmployee($company);

    AttendancePunch::create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'attendance_device_id' => null,
        'punch_date' => '2026-10-20',
        'punched_at' => '2026-10-20 07:55:00',
        'punch_type' => 'unknown',
        'source' => 'device',
        'external_id' => 'HV-IN-001',
        'device_user_id' => '4465666',
        'is_processed' => false,
    ]);

    $processor = app(AttendancePunchProcessor::class);

    $record = $processor->processEmployee(
        $company,
        $employee,
        \Carbon\Carbon::parse('2026-10-20')
    );

    $this->assertNotNull($record);

    $this->assertSame(
        '07:55',
        $record->clock_in_at->format('H:i')
    );

    $this->assertNull($record->clock_out_at);

    $this->assertSame(
        'incomplete',
        $record->status
    );
}
public function test_processing_same_punches_twice_does_not_duplicate_attendance_record(): void
{
    $company = $this->createCompany();

    $employee = $this->createEmployee($company);

    AttendancePunch::create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'attendance_device_id' => null,
        'punch_date' => '2026-10-20',
        'punched_at' => '2026-10-20 07:55:00',
        'punch_type' => 'unknown',
        'source' => 'device',
        'external_id' => 'HV-IN-001',
        'device_user_id' => '4465666',
        'is_processed' => false,
    ]);

    AttendancePunch::create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'attendance_device_id' => null,
        'punch_date' => '2026-10-20',
        'punched_at' => '2026-10-20 17:05:00',
        'punch_type' => 'unknown',
        'source' => 'device',
        'external_id' => 'HV-OUT-001',
        'device_user_id' => '4465666',
        'is_processed' => false,
    ]);

    $processor = app(AttendancePunchProcessor::class);

    $date = \Carbon\Carbon::parse('2026-10-20');

    $firstRecord = $processor->processEmployee(
        $company,
        $employee,
        $date
    );

    $secondRecord = $processor->processEmployee(
        $company,
        $employee,
        $date
    );

    $this->assertNotNull($firstRecord);
    $this->assertNotNull($secondRecord);

    $this->assertSame(
        $firstRecord->id,
        $secondRecord->id
    );

    $this->assertSame(
        1,
        AttendanceRecord::query()
            ->where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', '2026-10-20')
            ->count()
    );
}
protected function createCompany(): Company
{
    return Company::create([
        'name' => 'Test Company',
        'legal_name' => 'Test Company Limited',
        'slug' => 'test-company-' . uniqid(),
        'email' => 'test@example.com',
        'phone' => '0977000000',
        'country' => 'Zambia',
        'currency' => 'ZMW',
        'is_active' => true,
    ]);
}


public function test_overnight_shift_includes_next_day_clock_out(): void
{
    $company = $this->createCompany();
    $employee = $this->createEmployee($company);

    // Create a schedule from 22:00 to 06:00.
    $schedule = WorkSchedule::create([
        'company_id' => $company->id,
        'name' => 'Night Shift',
        'code' => 'NIGHT-' . uniqid(),
        'default_start_time' => '22:00:00',
        'default_end_time' => '06:00:00',
        'default_break_minutes' => 0,
        'grace_period_minutes' => 0,
        'early_departure_grace_minutes' => 0,
        'overtime_after_minutes' => 0,
        'allow_overtime' => true,
        'is_active' => true,
    ]);

    // 2026-10-26 is a Monday (ISO weekday 1).
    WorkScheduleDay::create([
        'work_schedule_id' => $schedule->id,
        'day_of_week' => 1,
        'is_working_day' => true,
        'start_time' => '22:00:00',
        'end_time' => '06:00:00',
        'break_minutes' => 0,
        'grace_period_minutes' => 0,
        'early_departure_grace_minutes' => 0,
        'allow_overtime' => true,
    ]);

    // Assign the schedule effective from the shift date.
    app(WorkScheduleService::class)->assignSchedule(
        $company,
        $employee,
        $schedule,
        [
            'effective_from' => '2026-10-26',
            'is_primary' => true,
        ]
    );

    // Punch before the scheduled start.
    AttendancePunch::create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'attendance_device_id' => null,
        'punch_date' => '2026-10-26',
        'punched_at' => '2026-10-26 21:55:00',
        'punch_type' => 'in',
        'source' => 'device',
        'external_id' => 'NIGHT-IN-001',
        'device_user_id' => 'EMP001',
        'is_processed' => false,
    ]);

    // Clock-out is on the following calendar day.
    AttendancePunch::create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'attendance_device_id' => null,
        'punch_date' => '2026-10-27',
        'punched_at' => '2026-10-27 06:05:00',
        'punch_type' => 'out',
        'source' => 'device',
        'external_id' => 'NIGHT-OUT-001',
        'device_user_id' => 'EMP001',
        'is_processed' => false,
    ]);

    $processor = app(AttendancePunchProcessor::class);

    $record = $processor->processEmployee(
        $company,
        $employee,
        \Carbon\Carbon::parse('2026-10-26')
    );

    $this->assertNotNull($record);

    $this->assertSame(
        '2026-10-26',
        $record->attendance_date->format('Y-m-d')
    );

    $this->assertSame(
        '2026-10-26 21:55:00',
        $record->clock_in_at->format('Y-m-d H:i:s')
    );

    $this->assertSame(
        '2026-10-27 06:05:00',
        $record->clock_out_at->format('Y-m-d H:i:s')
    );

    $this->assertSame('present', $record->status);

    $this->assertSame(
        2,
        AttendancePunch::query()
            ->where('employee_id', $employee->id)
            ->where('is_processed', true)
            ->count()
    );

    $this->assertEquals(
    8.00,
    (float) $record->scheduled_hours
);

    $this->assertEquals(
        8.17,
        (float) $record->worked_hours
    );

    $this->assertEquals(
        0.08,
        (float) $record->overtime_hours
    );

    $this->assertSame(0, (int) $record->late_minutes);

    $this->assertSame(
        0,
        (int) $record->early_departure_minutes
    );

    $this->assertSame(
        1,
        AttendanceRecord::query()
            ->where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', '2026-10-26')
            ->count()
    );
}


protected function createEmployee(Company $company): Employee
{
    return Employee::create([
        'company_id' => $company->id,
        'employee_number' => 'EMP-' . uniqid(),
        'first_name' => 'Test',
        'last_name' => 'Employee',
        'employment_status' => 'active',
        'employment_type' => 'permanent',
        'hire_date' => '2026-01-01',
        'salary_currency' => 'ZMW',
        'pay_frequency' => 'monthly',
        'is_active' => true,
    ]);
}
}