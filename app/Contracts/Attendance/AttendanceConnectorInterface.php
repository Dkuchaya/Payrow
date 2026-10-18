<?php

namespace App\Contracts\Attendance;

use App\Models\AttendanceDevice;

interface AttendanceConnectorInterface
{
    /**
     * Test whether the attendance device can be reached.
     */
    public function testConnection(
        AttendanceDevice $device
    ): bool;

    /**
     * Fetch raw punches/events from the device.
     *
     * @return iterable<int, array<string, mixed>>
     */
    public function fetchPunches(
        AttendanceDevice $device,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): iterable;

    /**
     * Normalize one vendor-specific punch into PAYROW's
     * standard punch structure.
     *
     * @param array<string, mixed> $rawPunch
     * @return array<string, mixed>
     */
    public function normalizePunch(
        AttendanceDevice $device,
        array $rawPunch
    ): array;

    /**
     * Synchronize punches from the device into
     * attendance_punches.
     */
    public function sync(
        AttendanceDevice $device,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): int;
}