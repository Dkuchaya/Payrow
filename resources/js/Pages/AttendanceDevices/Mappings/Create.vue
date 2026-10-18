<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

interface Company {
    id: number;
    name: string;
}

interface AttendanceDevice {
    id: number;
    name: string;
    manufacturer: string | null;
    model: string | null;
}

interface Employee {
    id: number;
    employee_number: string;
    first_name: string;
    last_name: string;
}

const props = defineProps<{
    company: Company;
    device: AttendanceDevice;
    employees: Employee[];
}>();

const form = useForm({
    employee_id: '',
    device_user_id: '',
    device_username: '',
    is_active: true,
    notes: '',
});

const selectedEmployee = computed(() =>
    props.employees.find(
        (employee) => String(employee.id) === String(form.employee_id)
    )
);

const submit = () => {
    form.post(
        route('attendance-devices.mappings.store', props.device.id),
        {
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <Head :title="`Map Employee — ${device.name}`" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <Link
                    :href="route('attendance-devices.mappings.index', device.id)"
                    class="text-sm text-slate-500 transition hover:text-slate-700"
                >
                    ← Employee Mappings
                </Link>

                <h1 class="mt-2 text-2xl font-semibold text-slate-900">
                    Map Employee
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Link a PAYROW employee to a user on {{ device.name }}.
                </p>
            </div>

            <Link
                :href="route('attendance-devices.show', device.id)"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
                View Device
            </Link>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Attendance device
            </p>
            <p class="mt-1 font-semibold text-slate-900">{{ device.name }}</p>
            <p class="mt-1 text-sm text-slate-500">
                {{ device.manufacturer || 'Unknown manufacturer' }}
                <span v-if="device.model">· {{ device.model }}</span>
            </p>
        </div>

        <form
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
            @submit.prevent="submit"
        >
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Mapping Details</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Choose an employee, then enter the matching identifier configured on the attendance device.
                </p>
            </div>

            <div class="space-y-6 p-5">
                <div>
                    <label
                        for="employee_id"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Employee <span class="text-red-600">*</span>
                    </label>

                    <select
                        id="employee_id"
                        v-model="form.employee_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        :class="{
                            'border-red-400 focus:border-red-500 focus:ring-red-100': form.errors.employee_id,
                        }"
                    >
                        <option value="">Select an employee</option>
                        <option
                            v-for="employee in employees"
                            :key="employee.id"
                            :value="employee.id"
                        >
                            {{ employee.employee_number }} — {{ employee.first_name }} {{ employee.last_name }}
                        </option>
                    </select>

                    <p v-if="form.errors.employee_id" class="mt-1 text-sm text-red-600">
                        {{ form.errors.employee_id }}
                    </p>

                    <p v-else-if="!employees.length" class="mt-1 text-sm text-amber-700">
                        Every active employee is already mapped to this device.
                    </p>
                </div>

                <div
                    v-if="selectedEmployee"
                    class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Selected employee
                    </p>
                    <p class="mt-1 font-medium text-slate-900">
                        {{ selectedEmployee.first_name }} {{ selectedEmployee.last_name }}
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        Employee no. {{ selectedEmployee.employee_number }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label
                            for="device_user_id"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Device User ID <span class="text-red-600">*</span>
                        </label>

                        <input
                            id="device_user_id"
                            v-model="form.device_user_id"
                            type="text"
                            maxlength="150"
                            autocomplete="off"
                            placeholder="e.g. 00125"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                            :class="{
                                'border-red-400 focus:border-red-500 focus:ring-red-100': form.errors.device_user_id,
                            }"
                        />

                        <p class="mt-1 text-xs text-slate-500">
                            The identifier assigned to this employee on the device.
                        </p>
                        <p v-if="form.errors.device_user_id" class="mt-1 text-sm text-red-600">
                            {{ form.errors.device_user_id }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="device_username"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Device Username
                        </label>

                        <input
                            id="device_username"
                            v-model="form.device_username"
                            type="text"
                            maxlength="150"
                            autocomplete="off"
                            placeholder="Optional name stored on the device"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                            :class="{
                                'border-red-400 focus:border-red-500 focus:ring-red-100': form.errors.device_username,
                            }"
                        />

                        <p v-if="form.errors.device_username" class="mt-1 text-sm text-red-600">
                            {{ form.errors.device_username }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="inline-flex items-center gap-3 text-sm text-slate-700">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-slate-300 text-slate-900 focus:ring-slate-500"
                        />
                        <span>
                            <span class="font-medium">Active mapping</span>
                            <span class="block text-xs text-slate-500">
                                Use this mapping when processing attendance events from the device.
                            </span>
                        </span>
                    </label>
                    <p v-if="form.errors.is_active" class="mt-1 text-sm text-red-600">
                        {{ form.errors.is_active }}
                    </p>
                </div>

                <div>
                    <label
                        for="notes"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Notes
                    </label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="4"
                        maxlength="2000"
                        placeholder="Optional notes about this mapping..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        :class="{
                            'border-red-400 focus:border-red-500 focus:ring-red-100': form.errors.notes,
                        }"
                    />
                    <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                        {{ form.errors.notes }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:justify-end">
                <Link
                    :href="route('attendance-devices.mappings.index', device.id)"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing || !employees.length"
                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{ form.processing ? 'Saving...' : 'Create Mapping' }}
                </button>
            </div>
        </form>
    </div>
</template>
