<?php

namespace App\Services\Attendance\Connectors;

use App\Contracts\Attendance\AttendanceConnectorInterface;
use App\Models\AttendanceDevice;
use App\Models\AttendanceDeviceEmployee;
use App\Models\AttendancePunch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class BaseAttendanceConnector implements AttendanceConnectorInterface
{
    public function sync(
        AttendanceDevice $device,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): int {
        $count = 0;

        foreach ($this->fetchPunches($device, $from, $to) as $rawPunch) {
            $normalized = $this->normalizePunch(
                $device,
                $rawPunch
            );

            $deviceUserId = $normalized['device_user_id'] ?? null;

            if (!$deviceUserId) {
                continue;
            }

            $mapping = AttendanceDeviceEmployee::query()
                ->where('company_id', $device->company_id)
                ->where('attendance_device_id', $device->id)
                ->where('device_user_id', $deviceUserId)
                ->where('is_active', true)
                ->first();

            if (!$mapping) {
                continue;
            }

            $punchedAt = Carbon::parse(
                $normalized['punched_at'],
                $device->timezone ?: config('app.timezone')
            );

            $externalId = $normalized['external_id'] ?? null;

            if ($externalId) {
                $exists = AttendancePunch::query()
                    ->where('attendance_device_id', $device->id)
                    ->where('external_id', $externalId)
                    ->exists();

                if ($exists) {
                    continue;
                }
            }

            AttendancePunch::create([
                'company_id' => $device->company_id,
                'employee_id' => $mapping->employee_id,
                'attendance_device_id' => $device->id,

                'punch_date' => $punchedAt->toDateString(),
                'punched_at' => $punchedAt,

                'punch_type' => $normalized['punch_type'] ?? 'unknown',
                'source' => $normalized['source'] ?? 'device',

                'external_id' => $externalId,
                'device_user_id' => $deviceUserId,

                'raw_data' => $normalized['raw_data'] ?? $rawPunch,

                'is_processed' => false,
            ]);

            $count++;
        }

        $device->update([
            'last_synced_at' => now(),
        ]);

        return $count;
    }
}