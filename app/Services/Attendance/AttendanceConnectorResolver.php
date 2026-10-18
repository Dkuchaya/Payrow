<?php

namespace App\Services\Attendance;

use App\Contracts\Attendance\AttendanceConnectorInterface;
use App\Models\AttendanceDevice;
use App\Services\Attendance\Connectors\GenericApiConnector;
use App\Services\Attendance\Connectors\HikvisionConnector;
use App\Services\Attendance\Connectors\ZKTecoConnector;
use InvalidArgumentException;

class AttendanceConnectorResolver
{
    /**
     * Resolve the correct connector for an attendance device.
     */
    public function resolve(
        AttendanceDevice $device
    ): AttendanceConnectorInterface {
        $manufacturer = strtolower(
            trim((string) $device->manufacturer)
        );

        return match ($manufacturer) {
            'zkteco',
            'zktec' => app(ZKTecoConnector::class),

            'hikvision' => app(HikvisionConnector::class),

            'generic',
            'generic_api',
            'api' => app(GenericApiConnector::class),

            default => throw new InvalidArgumentException(
                "Unsupported attendance device manufacturer: {$device->manufacturer}"
            ),
        };
    }
}