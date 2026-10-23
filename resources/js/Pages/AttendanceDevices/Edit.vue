<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
})


interface Company {
    id: number;
    name: string;
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
    username: string | null;
    timezone: string;
    sync_method: string;
    is_active: boolean;
    notes: string | null;
}

const props = defineProps<{
    company: Company;
    device: AttendanceDevice;
}>();

const form = useForm({
    name: props.device.name ?? '',
    device_type: props.device.device_type ?? 'biometric',
    manufacturer: props.device.manufacturer ?? '',
    model: props.device.model ?? '',
    serial_number: props.device.serial_number ?? '',
    connection_type: props.device.connection_type ?? 'api',
    ip_address: props.device.ip_address ?? '',
    port: props.device.port ? String(props.device.port) : '',
    api_url: props.device.api_url ?? '',
    username: props.device.username ?? '',
    password: '',
    timezone: props.device.timezone ?? 'Africa/Lusaka',
    sync_method: props.device.sync_method ?? 'pull',
    is_active: props.device.is_active ?? true,
    notes: props.device.notes ?? '',
});

const submit = () => {
    form.put(route('attendance-devices.update', props.device.id));
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <Link
                        :href="route('attendance-devices.index')"
                        class="hover:text-slate-700"
                    >
                        Attendance Devices
                    </Link>

                    <span>/</span>

                    <span>Edit Device</span>
                </div>

                <h1 class="mt-2 text-2xl font-bold text-slate-900">
                    Edit Device
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Update the configuration for {{ device.name }}.
                </p>
            </div>

            <Link
                :href="route('attendance-devices.show', device.id)"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                View Device
            </Link>
        </div>

        <form
            @submit.prevent="submit"
            class="space-y-6"
        >
            <!-- Device Information -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-900">
                        Device Information
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Basic information about the attendance device.
                    </p>
                </div>

                <div class="grid gap-5 p-6 md:grid-cols-2">
                    <!-- Name -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Device Name
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                            placeholder="Main Office Biometric"
                        />

                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Device Type -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Device Type
                        </label>

                        <select
                            v-model="form.device_type"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                        >
                            <option value="biometric">Biometric</option>
                            <option value="time_clock">Time Clock</option>
                            <option value="attendance_terminal">
                                Attendance Terminal
                            </option>
                            <option value="other">Other</option>
                        </select>

                        <p
                            v-if="form.errors.device_type"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.device_type }}
                        </p>
                    </div>

                    <!-- Manufacturer -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Manufacturer
                        </label>

                        <select
                            v-model="form.manufacturer"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                        >
                            <option value="">Select manufacturer</option>
                            <option value="ZKTeco">ZKTeco</option>
                            <option value="Hikvision">Hikvision</option>
                            <option value="Generic">Generic</option>
                            <option value="Other">Other</option>
                        </select>

                        <p
                            v-if="form.errors.manufacturer"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.manufacturer }}
                        </p>
                    </div>

                    <!-- Model -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Model
                        </label>

                        <input
                            v-model="form.model"
                            type="text"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                            placeholder="K40"
                        />

                        <p
                            v-if="form.errors.model"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.model }}
                        </p>
                    </div>

                    <!-- Serial Number -->
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Serial Number
                        </label>

                        <input
                            v-model="form.serial_number"
                            type="text"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                            placeholder="Device serial number"
                        />

                        <p
                            v-if="form.errors.serial_number"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.serial_number }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Connection Settings -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-900">
                        Connection Settings
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Configure how PAYROW communicates with the device.
                    </p>
                </div>

                <div class="grid gap-5 p-6 md:grid-cols-2">
                    <!-- Connection Type -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Connection Type
                        </label>

                        <select
                            v-model="form.connection_type"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                        >
                            <option value="api">API</option>
                            <option value="tcp">TCP/IP</option>
                            <option value="http">HTTP</option>
                            <option value="file">File Import</option>
                        </select>
                    </div>

                    <!-- IP -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            IP Address
                        </label>

                        <input
                            v-model="form.ip_address"
                            type="text"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                            placeholder="192.168.1.100"
                        />

                        <p
                            v-if="form.errors.ip_address"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.ip_address }}
                        </p>
                    </div>

                    <!-- Port -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Port
                        </label>

                        <input
                            v-model="form.port"
                            type="number"
                            min="1"
                            max="65535"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                            placeholder="4370"
                        />

                        <p
                            v-if="form.errors.port"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.port }}
                        </p>
                    </div>

                    <!-- API URL -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            API URL
                        </label>

                        <input
                            v-model="form.api_url"
                            type="url"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                            placeholder="https://device.example.com/api"
                        />

                        <p
                            v-if="form.errors.api_url"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.api_url }}
                        </p>
                    </div>

                    <!-- Username -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Username
                        </label>

                        <input
                            v-model="form.username"
                            type="text"
                            autocomplete="off"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                            placeholder="Device username"
                        />
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Password
                        </label>

                        <input
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                            placeholder="Leave blank to keep current password"
                        />

                        <p class="mt-1 text-xs text-slate-500">
                            Leave blank if you do not want to change the
                            existing password.
                        </p>

                        <p
                            v-if="form.errors.password"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Synchronization -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-900">
                        Synchronization
                    </h2>
                </div>

                <div class="grid gap-5 p-6 md:grid-cols-2">
                    <!-- Timezone -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Timezone
                        </label>

                        <select
                            v-model="form.timezone"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                        >
                            <option value="Africa/Lusaka">
                                Africa/Lusaka (CAT)
                            </option>
                            <option value="Africa/Johannesburg">
                                Africa/Johannesburg
                            </option>
                            <option value="UTC">
                                UTC
                            </option>
                        </select>

                        <p
                            v-if="form.errors.timezone"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.timezone }}
                        </p>
                    </div>

                    <!-- Sync Method -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Sync Method
                        </label>

                        <select
                            v-model="form.sync_method"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                        >
                            <option value="pull">
                                Pull from Device
                            </option>
                            <option value="push">
                                Device Push
                            </option>
                            <option value="manual">
                                Manual
                            </option>
                        </select>

                        <p
                            v-if="form.errors.sync_method"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.sync_method }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Status & Notes -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-900">
                        Status & Notes
                    </h2>
                </div>

                <div class="space-y-5 p-6">
                    <!-- Active -->
                    <label class="flex items-center gap-3">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-slate-700 focus:ring-slate-500"
                        />

                        <span>
                            <span class="block text-sm font-medium text-slate-700">
                                Device is active
                            </span>

                            <span class="block text-xs text-slate-500">
                                Active devices can be synchronized.
                            </span>
                        </span>
                    </label>

                    <!-- Notes -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Notes
                        </label>

                        <textarea
                            v-model="form.notes"
                            rows="4"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                            placeholder="Additional notes about this device..."
                        ></textarea>

                        <p
                            v-if="form.errors.notes"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.notes }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link
                    :href="route('attendance-devices.show', device.id)"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
        </form>
    </div>
</template>