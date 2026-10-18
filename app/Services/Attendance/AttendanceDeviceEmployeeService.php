<?php

namespace App\Services\Attendance;

use App\Models\AttendanceDevice;
use App\Models\AttendanceDeviceEmployee;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AttendanceDeviceEmployeeService
{
    public function create(
        Company $company,
        AttendanceDevice $device,
        array $data
    ): AttendanceDeviceEmployee {
        $this->validateDevice($company, $device);

        $employee = Employee::query()
            ->where('company_id', $company->id)
            ->where('id', $data['employee_id'])
            ->firstOrFail();

        $exists = AttendanceDeviceEmployee::query()
            ->where('attendance_device_id', $device->id)
            ->where('employee_id', $employee->id)
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException(
                'This employee is already mapped to this device.'
            );
        }

        $deviceUserExists = AttendanceDeviceEmployee::query()
            ->where('attendance_device_id', $device->id)
            ->where('device_user_id', $data['device_user_id'])
            ->exists();

        if ($deviceUserExists) {
            throw new InvalidArgumentException(
                'This device user ID is already mapped on this device.'
            );
        }

        return DB::transaction(function () use (
            $company,
            $device,
            $employee,
            $data
        ) {
            return AttendanceDeviceEmployee::create([
                'company_id' => $company->id,
                'attendance_device_id' => $device->id,
                'employee_id' => $employee->id,
                'device_user_id' => $data['device_user_id'],
                'device_username' => $data['device_username'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function update(
        Company $company,
        AttendanceDeviceEmployee $mapping,
        array $data
    ): AttendanceDeviceEmployee {
        $this->validateMapping($company, $mapping);

        $employee = Employee::query()
            ->where('company_id', $company->id)
            ->where('id', $data['employee_id'])
            ->firstOrFail();

        $duplicateEmployee = AttendanceDeviceEmployee::query()
            ->where('attendance_device_id', $mapping->attendance_device_id)
            ->where('employee_id', $employee->id)
            ->whereKeyNot($mapping->id)
            ->exists();

        if ($duplicateEmployee) {
            throw new InvalidArgumentException(
                'This employee is already mapped to this device.'
            );
        }

        $duplicateDeviceUser = AttendanceDeviceEmployee::query()
            ->where('attendance_device_id', $mapping->attendance_device_id)
            ->where('device_user_id', $data['device_user_id'])
            ->whereKeyNot($mapping->id)
            ->exists();

        if ($duplicateDeviceUser) {
            throw new InvalidArgumentException(
                'This device user ID is already mapped on this device.'
            );
        }

        $mapping->update([
            'employee_id' => $employee->id,
            'device_user_id' => $data['device_user_id'],
            'device_username' => $data['device_username'] ?? null,
            'is_active' => $data['is_active'] ?? $mapping->is_active,
            'notes' => $data['notes'] ?? null,
        ]);

        return $mapping->fresh([
            'employee',
            'device',
        ]);
    }

    public function toggle(
        Company $company,
        AttendanceDeviceEmployee $mapping
    ): AttendanceDeviceEmployee {
        $this->validateMapping($company, $mapping);

        $mapping->update([
            'is_active' => !$mapping->is_active,
        ]);

        return $mapping->fresh();
    }

    protected function validateDevice(
        Company $company,
        AttendanceDevice $device
    ): void {
        if ((int) $device->company_id !== (int) $company->id) {
            throw new InvalidArgumentException(
                'Attendance device does not belong to the active company.'
            );
        }
    }

    protected function validateMapping(
        Company $company,
        AttendanceDeviceEmployee $mapping
    ): void {
        if ((int) $mapping->company_id !== (int) $company->id) {
            throw new InvalidArgumentException(
                'Device mapping does not belong to the active company.'
            );
        }

        $device = AttendanceDevice::query()
            ->where('company_id', $company->id)
            ->whereKey($mapping->attendance_device_id)
            ->exists();

        if (!$device) {
            throw new InvalidArgumentException(
                'Attendance device does not belong to the active company.'
            );
        }
    }
}