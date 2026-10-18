<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
})


interface Company {
    id: number
    name: string
}

defineProps<{
    company: Company
}>()

const form = useForm({
    name: '',
    device_type: 'biometric',
    manufacturer: '',
    model: '',
    serial_number: '',
    connection_type: 'api',
    ip_address: '',
    port: '',
    api_url: '',
    username: '',
    password: '',
    timezone: 'Africa/Lusaka',
    sync_method: 'pull',
    is_active: true,
    notes: '',
})

const submit = () => {
    form.post(
        route('attendance-devices.store')
    )
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div>
            <Link
                :href="route('attendance-devices.index')"
                class="text-sm font-medium text-slate-600 hover:text-slate-900"
            >
                ← Back to Attendance Devices
            </Link>

            <h1 class="mt-3 text-2xl font-bold text-slate-900">
                Add Attendance Device
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Connect a biometric or attendance device to PAYROW.
            </p>
        </div>

        <!-- Form -->
        <form
            @submit.prevent="submit"
            class="space-y-6"
        >

            <!-- Basic Information -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <h2 class="text-lg font-semibold text-slate-900">
                    Device Information
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Basic information about the attendance device.
                </p>

                <div class="mt-6 grid gap-5 md:grid-cols-2">

                    <!-- Name -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Device Name
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Main Office Biometric"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                            :class="{
                                'border-red-400': form.errors.name
                            }"
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
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Device Type
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            v-model="form.device_type"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >
                            <option value="biometric">
                                Biometric
                            </option>

                            <option value="time_clock">
                                Time Clock
                            </option>

                            <option value="api">
                                API Integration
                            </option>

                            <option value="other">
                                Other
                            </option>
                        </select>
                    </div>

                    <!-- Manufacturer -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Manufacturer
                        </label>

                        <select
                            v-model="form.manufacturer"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >
                            <option value="">
                                Select Manufacturer
                            </option>

                            <option value="ZKTeco">
                                ZKTeco
                            </option>

                            <option value="Hikvision">
                                Hikvision
                            </option>

                            <option value="Generic">
                                Generic API
                            </option>

                            <option value="Other">
                                Other
                            </option>
                        </select>
                    </div>

                    <!-- Model -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Model
                        </label>

                        <input
                            v-model="form.model"
                            type="text"
                            placeholder="e.g. ZKTeco K40"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        />
                    </div>

                    <!-- Serial -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Serial Number
                        </label>

                        <input
                            v-model="form.serial_number"
                            type="text"
                            placeholder="Device serial number"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        />
                    </div>

                    <!-- Timezone -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Timezone
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            v-model="form.timezone"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >
                            <option value="Africa/Lusaka">
                                Africa/Lusaka (CAT)
                            </option>

                            <option value="Africa/Johannesburg">
                                Africa/Johannesburg
                            </option>

                            <option value="Africa/Nairobi">
                                Africa/Nairobi
                            </option>

                            <option value="UTC">
                                UTC
                            </option>
                        </select>
                    </div>

                </div>
            </div>

            <!-- Connection -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <h2 class="text-lg font-semibold text-slate-900">
                    Connection Settings
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Configure how PAYROW communicates with the device.
                </p>

                <div class="mt-6 grid gap-5 md:grid-cols-2">

                    <!-- Connection Type -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Connection Type
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            v-model="form.connection_type"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >
                            <option value="api">
                                API
                            </option>

                            <option value="tcp">
                                TCP/IP
                            </option>

                            <option value="http">
                                HTTP
                            </option>

                            <option value="file">
                                File Import
                            </option>
                        </select>
                    </div>

                    <!-- Sync Method -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Sync Method
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            v-model="form.sync_method"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >
                            <option value="pull">
                                Pull from Device
                            </option>

                            <option value="push">
                                Push from Device
                            </option>

                            <option value="manual">
                                Manual Import
                            </option>
                        </select>
                    </div>

                    <!-- IP -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            IP Address
                        </label>

                        <input
                            v-model="form.ip_address"
                            type="text"
                            placeholder="192.168.1.100"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
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
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Port
                        </label>

                        <input
                            v-model="form.port"
                            type="number"
                            min="1"
                            max="65535"
                            placeholder="4370"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        />
                    </div>

                    <!-- API URL -->
                    <div class="md:col-span-2">
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            API URL
                        </label>

                        <input
                            v-model="form.api_url"
                            type="url"
                            placeholder="https://device.example.com/api"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
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
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Username
                        </label>

                        <input
                            v-model="form.username"
                            type="text"
                            autocomplete="off"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        />
                    </div>

                    <!-- Password -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Password
                        </label>

                        <input
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        />

                        <p class="mt-1 text-xs text-slate-500">
                            The password is encrypted before being stored.
                        </p>
                    </div>

                </div>
            </div>

            <!-- Status & Notes -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <h2 class="text-lg font-semibold text-slate-900">
                    Status & Notes
                </h2>

                <div class="mt-6 space-y-5">

                    <!-- Active -->
                    <label
                        class="flex cursor-pointer items-center gap-3"
                    >
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500"
                        />

                        <span>
                            <span
                                class="block text-sm font-medium text-slate-800"
                            >
                                Device is active
                            </span>

                            <span
                                class="block text-xs text-slate-500"
                            >
                                Active devices can be synchronized.
                            </span>
                        </span>
                    </label>

                    <!-- Notes -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Notes
                        </label>

                        <textarea
                            v-model="form.notes"
                            rows="4"
                            placeholder="Additional information about this device..."
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        />
                    </div>

                </div>
            </div>

            <!-- Actions -->
            <div
                class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
            >
                <Link
                    :href="route('attendance-devices.index')"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{
                        form.processing
                            ? 'Saving...'
                            : 'Save Device'
                    }}
                </button>
            </div>

        </form>
    </div>
</template>