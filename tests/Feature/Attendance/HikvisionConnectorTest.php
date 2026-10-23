<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceDevice;
use App\Models\AttendanceDeviceEmployee;
use App\Models\AttendancePunch;
use App\Models\Company;
use App\Models\Employee;
use App\Services\Attendance\AttendanceConnectorResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HikvisionConnectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_hikvision_event_can_be_synchronized(): void
    {
        /*
         * Simulate 101 events from the Hikvision device.
         *
         * The connector requests a maximum of 100 events at a time,
         * so this forces a second pagination request.
         */
        $events = [];

        for ($i = 1; $i <= 101; $i++) {
            $totalSeconds = ($i - 1) * 60;

            $hours = 7 + intdiv($totalSeconds, 3600);
            $minutes = intdiv($totalSeconds % 3600, 60);
            $seconds = $totalSeconds % 60;

            $events[] = [
                'employeeNoString' => '4465666',

                'time' => sprintf(
                    '2026-10-20T%02d:%02d:%02d+02:00',
                    $hours,
                    $minutes,
                    $seconds
                ),

                'serialNo' => sprintf(
                    'HV-%06d',
                    $i
                ),

                'major' => 5,
                'minor' => 75,
            ];
        }

        Http::fake(function ($request) use ($events) {
            $data = $request->data();

            $position = (int) (
                $data['AcsEventCond']['searchResultPosition']
                ?? 0
            );

            $batch = array_slice(
                $events,
                $position,
                100
            );

            return Http::response([
                'AcsEvent' => [
                    'InfoList' => $batch,
                ],
            ], 200);
        });

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
            'name' => 'Test Hikvision',
            'device_type' => 'biometric',
            'manufacturer' => 'Hikvision',
            'model' => 'Test Model',
            'connection_type' => 'api',
            'ip_address' => '192.168.1.100',
            'port' => 80,
            'timezone' => 'Africa/Lusaka',
            'sync_method' => 'pull',
            'username' => 'admin',
            'password' => Crypt::encryptString('test-password'),
            'is_active' => true,
        ]);

        AttendanceDeviceEmployee::create([
            'company_id' => $company->id,
            'attendance_device_id' => $device->id,
            'employee_id' => $employee->id,
            'device_user_id' => '4465666',
            'device_username' => 'Test Employee',
            'is_active' => true,
        ]);

        $resolver = app(
            AttendanceConnectorResolver::class
        );

        $connector = $resolver->resolve($device);

        /*
         * First synchronization.
         *
         * Expected:
         * 100 events from page 1
         * + 1 event from page 2
         * = 101 punches
         */
        $count = $connector->sync(
            $device,
            new \DateTimeImmutable('2026-10-20 00:00:00'),
            new \DateTimeImmutable('2026-10-20 23:59:59')
        );

        $this->assertSame(101, $count);

        /*
         * Verify the first event was stored.
         */
        $this->assertDatabaseHas('attendance_punches', [
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'attendance_device_id' => $device->id,
            'device_user_id' => '4465666',
            'external_id' => 'HV-000001',
            'punch_type' => 'unknown',
            'source' => 'device',
        ]);

        /*
         * Verify the final paginated event was also stored.
         */
        $this->assertDatabaseHas('attendance_punches', [
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'attendance_device_id' => $device->id,
            'device_user_id' => '4465666',
            'external_id' => 'HV-000101',
            'punch_type' => 'unknown',
            'source' => 'device',
        ]);

        /*
         * Confirm exactly 101 punches exist.
         */
        $this->assertSame(
            101,
            AttendancePunch::query()
                ->where('attendance_device_id', $device->id)
                ->count()
        );

        /*
         * Run the same synchronization again.
         *
         * All 101 events already exist, so nothing new
         * should be inserted.
         */
        $secondCount = $connector->sync(
            $device,
            new \DateTimeImmutable('2026-10-20 00:00:00'),
            new \DateTimeImmutable('2026-10-20 23:59:59')
        );

        $requests = Http::recorded();

        $this->assertGreaterThanOrEqual(2, $requests->count());

        $firstSearchId = $requests[0][0]
            ->data()['AcsEventCond']['searchID']
            ?? null;

        $secondSearchId = $requests[1][0]
            ->data()['AcsEventCond']['searchID']
            ?? null;

        $this->assertNotNull($firstSearchId);
        $this->assertSame($firstSearchId, $secondSearchId);

        $this->assertSame(0, $secondCount);

        /*
         * Confirm the second synchronization did not create
         * any duplicates.
         */
        $this->assertSame(
            101,
            AttendancePunch::query()
                ->where('attendance_device_id', $device->id)
                ->count()
        );
    }
}