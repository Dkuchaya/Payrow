<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
})


interface Company {
    id: number;
    name: string;
}

interface Employee {
    id: number;
    employee_number: string;
    first_name: string;
    last_name: string;
}

interface EmployeeMapping {
    id: number;
    device_user_id: string;
    device_username: string | null;
    is_active: boolean;
    employee: Employee;
}

interface AttendancePunch {
    id: number;
    punch_date: string;
    punched_at: string;
    punch_type: string;
    source: string;
    device_user_id: string | null;
    is_processed: boolean;
    employee: Employee;
}

interface AttendanceDevice {
    id: number;
    name: string;
    device_type: string;
    manufacturer: string | null;
    model: string | null;
    serial_number: string | null;
    connection_type: string;
    ip_address: string | null;
    port: number | null;
    api_url: string | null;
    timezone: string;
    sync_method: string;
    last_synced_at: string | null;
    is_active: boolean;
    notes: string | null;
    employee_mappings: EmployeeMapping[];
}

const props = defineProps<{
    company: Company;
    device: AttendanceDevice;
    recentPunches: AttendancePunch[];
}>();

const formatDateTime = (value: string | null) => {
    if (!value) {
        return 'Never';
    }

    return new Date(value).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const employeeName = (employee: Employee) => {
    return `${employee.first_name} ${employee.last_name}`;
};

const punchTypeLabel = (value: string) => {
    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="mb-2">
                    <Link
                        :href="route('attendance-devices.index')"
                        class="text-sm text-slate-500 hover:text-slate-700"
                    >
                        ← Attendance Devices
                    </Link>
                </div>

                <h1 class="text-2xl font-semibold text-slate-900">
                    {{ device.name }}
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    {{ device.manufacturer || 'Unknown manufacturer' }}

                    <span v-if="device.model">
                        · {{ device.model }}
                    </span>
                </p>
            </div>

            <div class="flex gap-2">
                <Link
                    :href="
                        route(
                            'attendance-devices.edit',
                            device.id
                        )
                    "
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Edit Device
                </Link>

                <Link
                    :href="
                        route(
                            'attendance-devices.mappings.index',
                            device.id
                        )
                    "
                    class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800"
                >
                    Manage Employees
                </Link>
            </div>
        </div>

        <!-- Device information -->
        <div
            class="grid grid-cols-1 gap-6 lg:grid-cols-2"
        >
            <div
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <h2
                    class="text-lg font-semibold text-slate-900"
                >
                    Device Information
                </h2>

                <dl class="mt-5 divide-y divide-slate-100">
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-slate-500">
                            Status
                        </dt>

                        <dd>
                            <span
                                v-if="device.is_active"
                                class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700"
                            >
                                Active
                            </span>

                            <span
                                v-else
                                class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600"
                            >
                                Inactive
                            </span>
                        </dd>
                    </div>

                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-slate-500">
                            Device Type
                        </dt>

                        <dd class="text-sm font-medium text-slate-800">
                            {{ device.device_type }}
                        </dd>
                    </div>

                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-slate-500">
                            Manufacturer
                        </dt>

                        <dd class="text-sm font-medium text-slate-800">
                            {{ device.manufacturer || '—' }}
                        </dd>
                    </div>

                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-slate-500">
                            Model
                        </dt>

                        <dd class="text-sm font-medium text-slate-800">
                            {{ device.model || '—' }}
                        </dd>
                    </div>

                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-slate-500">
                            Serial Number
                        </dt>

                        <dd class="text-sm font-medium text-slate-800">
                            {{ device.serial_number || '—' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <h2
                    class="text-lg font-semibold text-slate-900"
                >
                    Connection
                </h2>

                <dl class="mt-5 divide-y divide-slate-100">
                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-slate-500">
                            Connection Type
                        </dt>

                        <dd class="text-sm font-medium text-slate-800">
                            {{ device.connection_type }}
                        </dd>
                    </div>

                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-slate-500">
                            IP Address
                        </dt>

                        <dd class="text-sm font-medium text-slate-800">
                            {{ device.ip_address || '—' }}
                        </dd>
                    </div>

                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-slate-500">
                            Port
                        </dt>

                        <dd class="text-sm font-medium text-slate-800">
                            {{ device.port || '—' }}
                        </dd>
                    </div>

                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-slate-500">
                            Timezone
                        </dt>

                        <dd class="text-sm font-medium text-slate-800">
                            {{ device.timezone }}
                        </dd>
                    </div>

                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-slate-500">
                            Sync Method
                        </dt>

                        <dd class="text-sm font-medium text-slate-800">
                            {{ device.sync_method }}
                        </dd>
                    </div>

                    <div class="flex justify-between py-3">
                        <dt class="text-sm text-slate-500">
                            Last Sync
                        </dt>

                        <dd class="text-sm font-medium text-slate-800">
                            {{
                                formatDateTime(
                                    device.last_synced_at
                                )
                            }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Employee mappings -->
        <div
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            <div
                class="flex items-center justify-between border-b border-slate-200 px-6 py-4"
            >
                <div>
                    <h2
                        class="font-semibold text-slate-900"
                    >
                        Employee Mappings
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Employees registered on this attendance device.
                    </p>
                </div>

                <Link
                    :href="
                        route(
                            'attendance-devices.mappings.index',
                            device.id
                        )
                    "
                    class="text-sm font-medium text-slate-700 hover:text-slate-900"
                >
                    Manage
                </Link>
            </div>

            <div
                v-if="device.employee_mappings?.length"
                class="overflow-x-auto"
            >
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Employee
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Device User ID
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Device Username
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="mapping in device.employee_mappings"
                            :key="mapping.id"
                        >
                            <td class="px-6 py-3">
                                <div class="font-medium text-slate-900">
                                    {{ employeeName(mapping.employee) }}
                                </div>

                                <div class="text-xs text-slate-500">
                                    {{ mapping.employee.employee_number }}
                                </div>
                            </td>

                            <td class="px-6 py-3 text-sm text-slate-700">
                                {{ mapping.device_user_id }}
                            </td>

                            <td class="px-6 py-3 text-sm text-slate-700">
                                {{ mapping.device_username || '—' }}
                            </td>

                            <td class="px-6 py-3">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        mapping.is_active
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-slate-100 text-slate-600'
                                    "
                                >
                                    {{
                                        mapping.is_active
                                            ? 'Active'
                                            : 'Inactive'
                                    }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-else
                class="px-6 py-12 text-center"
            >
                <p class="text-sm font-medium text-slate-700">
                    No employee mappings
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    Map PAYROW employees to users registered on this device.
                </p>
            </div>
        </div>

        <!-- Recent punches -->
        <div
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-900">
                    Recent Punches
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Latest attendance events received from this device.
                </p>
            </div>

            <div
                v-if="recentPunches.length"
                class="overflow-x-auto"
            >
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Employee
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Date / Time
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Type
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Device User
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Processing
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="punch in recentPunches"
                            :key="punch.id"
                        >
                            <td class="px-6 py-3">
                                <div class="font-medium text-slate-900">
                                    {{ employeeName(punch.employee) }}
                                </div>

                                <div class="text-xs text-slate-500">
                                    {{ punch.employee.employee_number }}
                                </div>
                            </td>

                            <td class="px-6 py-3 text-sm text-slate-700">
                                {{ formatDateTime(punch.punched_at) }}
                            </td>

                            <td class="px-6 py-3 text-sm text-slate-700">
                                {{ punchTypeLabel(punch.punch_type) }}
                            </td>

                            <td class="px-6 py-3 text-sm text-slate-700">
                                {{ punch.device_user_id || '—' }}
                            </td>

                            <td class="px-6 py-3">
                                <span
                                    v-if="punch.is_processed"
                                    class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700"
                                >
                                    Processed
                                </span>

                                <span
                                    v-else
                                    class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700"
                                >
                                    Pending
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-else
                class="px-6 py-12 text-center text-sm text-slate-500"
            >
                No punches have been received from this device yet.
            </div>
        </div>

        <!-- Notes -->
        <div
            v-if="device.notes"
            class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            <h2 class="font-semibold text-slate-900">
                Notes
            </h2>

            <p class="mt-3 whitespace-pre-line text-sm text-slate-600">
                {{ device.notes }}
            </p>
        </div>
    </div>
</template>