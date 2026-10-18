<?php

namespace App\Services\Attendance;

use App\Models\AttendanceDevice;
use App\Models\Company;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class AttendanceDeviceService
{
    public function __construct(
        protected AttendanceConnectorResolver $resolver
    ) {
    }

    public function create(
        Company $company,
        array $data
    ): AttendanceDevice {
        return DB::transaction(function () use ($company, $data) {
            $device = new AttendanceDevice();

            $device->company_id = $company->id;
            $device->name = $data['name'];
            $device->device_type = $data['device_type'] ?? 'biometric';
            $device->manufacturer = $data['manufacturer'] ?? null;
            $device->model = $data['model'] ?? null;
            $device->serial_number = $data['serial_number'] ?? null;
            $device->connection_type = $data['connection_type'] ?? 'api';
            $device->ip_address = $data['ip_address'] ?? null;
            $device->port = $data['port'] ?? null;
            $device->api_url = $data['api_url'] ?? null;

            $device->username = $data['username'] ?? null;

            if (!empty($data['password'])) {
                $device->password = Crypt::encryptString(
                    $data['password']
                );
            }

            $device->timezone = $data['timezone']
                ?? 'Africa/Lusaka';

            $device->sync_method = $data['sync_method'] ?? 'pull';
            $device->is_active = $data['is_active'] ?? true;
            $device->notes = $data['notes'] ?? null;

            $device->save();

            return $device;
        });
    }

    public function update(
        AttendanceDevice $device,
        array $data
    ): AttendanceDevice {
        return DB::transaction(function () use ($device, $data) {
            $device->name = $data['name'] ?? $device->name;
            $device->device_type =
                $data['device_type'] ?? $device->device_type;

            $device->manufacturer =
                $data['manufacturer'] ?? $device->manufacturer;

            $device->model =
                $data['model'] ?? $device->model;

            $device->serial_number =
                $data['serial_number'] ?? $device->serial_number;

            $device->connection_type =
                $data['connection_type']
                ?? $device->connection_type;

            $device->ip_address =
                $data['ip_address'] ?? $device->ip_address;

            $device->port =
                $data['port'] ?? $device->port;

            $device->api_url =
                $data['api_url'] ?? $device->api_url;

            $device->username =
                $data['username'] ?? $device->username;

            if (!empty($data['password'])) {
                $device->password = Crypt::encryptString(
                    $data['password']
                );
            }

            $device->timezone =
                $data['timezone']
                ?? $device->timezone;

            $device->sync_method =
                $data['sync_method']
                ?? $device->sync_method;

            $device->is_active =
                $data['is_active']
                ?? $device->is_active;

            $device->notes =
                $data['notes'] ?? $device->notes;

            $device->save();

            return $device->fresh();
        });
    }

    public function testConnection(
        AttendanceDevice $device
    ): bool {
        $connector = $this->resolver->resolve($device);

        return $connector->testConnection($device);
    }

    public function sync(
        AttendanceDevice $device,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): int {
        $connector = $this->resolver->resolve($device);

        return $connector->sync(
            $device,
            $from,
            $to
        );
    }
}