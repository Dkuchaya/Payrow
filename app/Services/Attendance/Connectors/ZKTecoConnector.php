<?php

namespace App\Services\Attendance\Connectors;

use App\Models\AttendanceDevice;
use RuntimeException;

class ZKTecoConnector extends BaseAttendanceConnector
{
    public function testConnection(
        AttendanceDevice $device
    ): bool {
        throw new RuntimeException(
            'ZKTeco connector is not implemented yet.'
        );
    }

    public function fetchPunches(
        AttendanceDevice $device,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): iterable {
        throw new RuntimeException(
            'ZKTeco punch synchronization is not implemented yet.'
        );
    }

    public function normalizePunch(
        AttendanceDevice $device,
        array $rawPunch
    ): array {
        throw new RuntimeException(
            'ZKTeco punch normalization is not implemented yet.'
        );
    }
}