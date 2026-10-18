<?php

namespace App\Services\Attendance\Connectors;

use App\Models\AttendanceDevice;
use RuntimeException;

class GenericApiConnector extends BaseAttendanceConnector
{
    public function testConnection(
        AttendanceDevice $device
    ): bool {
        throw new RuntimeException(
            'Generic attendance API connector is not implemented yet.'
        );
    }

    public function fetchPunches(
        AttendanceDevice $device,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): iterable {
        throw new RuntimeException(
            'Generic attendance API synchronization is not implemented yet.'
        );
    }

    public function normalizePunch(
        AttendanceDevice $device,
        array $rawPunch
    ): array {
        throw new RuntimeException(
            'Generic attendance API normalization is not implemented yet.'
        );
    }
}