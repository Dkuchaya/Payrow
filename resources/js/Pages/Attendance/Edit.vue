<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Company {
    id: number;
    name: string;
}

interface Employee {
    id: number;
    employee_number: string | null;
    first_name: string;
    last_name: string;
    employment_status: string;
}

interface AttendanceRecord {
    id: number;
    employee_id: number;
    attendance_date: string;
    clock_in_at: string | null;
    clock_out_at: string | null;
    status: string;
    adjustment_reason: string | null;
    notes: string | null;
    source: string;
    scheduled_hours: number | null;
    worked_hours: number | null;
    overtime_hours: number | null;
    late_minutes: number | null;
    early_departure_minutes: number | null;
}

const props = defineProps<{
    company: Company;
    attendance: AttendanceRecord & {
        employee?: Employee;
    };
    employee: Employee;
}>();

const employeeName = computed(() => {
    return `${props.employee.first_name} ${props.employee.last_name}`.trim();
});

const formatDateForInput = (
    value: string | null | undefined
): string => {
    if (!value) {
        return '';
    }

    return value.substring(0, 10);
};

const formatDateTimeForInput = (
    value: string | null | undefined
): string => {
    if (!value) {
        return '';
    }

    /*
     * Do not use new Date() here.
     * Attendance times are displayed exactly as stored so we
     * don't introduce a timezone conversion.
     */
    const match = value.match(
        /T(\d{2}):(\d{2})(?::\d{2})?/
    );

    if (!match) {
        return '';
    }

    return `${formatDateForInput(value)}T${match[1]}:${match[2]}`;
};

const form = useForm({
    attendance_date: formatDateForInput(
        props.attendance.attendance_date
    ),

    clock_in_at: formatDateTimeForInput(
        props.attendance.clock_in_at
    ),

    clock_out_at: formatDateTimeForInput(
        props.attendance.clock_out_at
    ),

    status: props.attendance.status || 'present',

    adjustment_reason:
        props.attendance.adjustment_reason || '',

    notes: props.attendance.notes || '',
});

const submit = () => {
    form.put(
        route(
            'attendance.update',
            props.attendance.id
        ),
        {
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <Head title="Edit Attendance" />

    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="mb-1 text-sm text-slate-500">
                    Attendance
                    <span class="mx-1">/</span>
                    Edit
                </div>

                <h1
                    class="text-2xl font-semibold tracking-tight text-slate-900"
                >
                    Edit Attendance
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Correct or adjust the employee's attendance record.
                </p>
            </div>

            <Link
                :href="route('attendance.index')"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                ← Back to Attendance
            </Link>
        </div>

        <!-- Employee summary -->
        <div
            class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Employee
                    </div>

                    <div class="mt-1 text-lg font-semibold text-slate-900">
                        {{ employeeName }}
                    </div>

                    <div class="mt-1 text-sm text-slate-500">
                        Employee No:
                        <span class="font-medium text-slate-700">
                            {{ employee.employee_number || '—' }}
                        </span>
                    </div>
                </div>

                <div class="rounded-lg bg-slate-50 px-4 py-3">
                    <div class="text-xs text-slate-500">
                        Attendance Source
                    </div>

                    <div
                        class="mt-1 text-sm font-semibold capitalize text-slate-900"
                    >
                        {{ attendance.source }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form
            @submit.prevent="submit"
            class="rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-900">
                    Attendance Details
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Update the attendance information below.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 p-5 md:grid-cols-2">
                <!-- Date -->
                <div>
                    <label
                        for="attendance_date"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Attendance Date
                    </label>

                    <input
                        id="attendance_date"
                        v-model="form.attendance_date"
                        type="date"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        :class="{
                            'border-red-400 focus:border-red-500 focus:ring-red-100':
                                form.errors.attendance_date,
                        }"
                    />

                    <p
                        v-if="form.errors.attendance_date"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.attendance_date }}
                    </p>
                </div>

                <!-- Status -->
                <div>
                    <label
                        for="status"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Status
                    </label>

                    <select
                        id="status"
                        v-model="form.status"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        :class="{
                            'border-red-400':
                                form.errors.status,
                        }"
                    >
                        <option value="present">
                            Present
                        </option>

                        <option value="absent">
                            Absent
                        </option>

                        <option value="late">
                            Late
                        </option>

                        <option value="half_day">
                            Half Day
                        </option>

                        <option value="leave">
                            Leave
                        </option>

                        <option value="holiday">
                            Holiday
                        </option>

                        <option value="rest_day">
                            Rest Day
                        </option>
                    </select>

                    <p
                        v-if="form.errors.status"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.status }}
                    </p>
                </div>

                <!-- Clock In -->
                <div>
                    <label
                        for="clock_in_at"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Clock In
                    </label>

                    <input
                        id="clock_in_at"
                        v-model="form.clock_in_at"
                        type="datetime-local"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        :class="{
                            'border-red-400':
                                form.errors.clock_in_at,
                        }"
                    />

                    <p
                        v-if="form.errors.clock_in_at"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.clock_in_at }}
                    </p>
                </div>

                <!-- Clock Out -->
                <div>
                    <label
                        for="clock_out_at"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Clock Out
                    </label>

                    <input
                        id="clock_out_at"
                        v-model="form.clock_out_at"
                        type="datetime-local"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        :class="{
                            'border-red-400':
                                form.errors.clock_out_at,
                        }"
                    />

                    <p
                        v-if="form.errors.clock_out_at"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.clock_out_at }}
                    </p>
                </div>

                <!-- Adjustment Reason -->
                <div class="md:col-span-2">
                    <label
                        for="adjustment_reason"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Adjustment Reason
                    </label>

                    <textarea
                        id="adjustment_reason"
                        v-model="form.adjustment_reason"
                        rows="3"
                        placeholder="Explain why this attendance record is being changed..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        :class="{
                            'border-red-400 focus:border-red-500 focus:ring-red-100':
                                form.errors.adjustment_reason,
                        }"
                    ></textarea>

                    <p class="mt-1 text-xs text-slate-500">
                        Recommended when correcting an existing attendance record.
                    </p>

                    <p
                        v-if="form.errors.adjustment_reason"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.adjustment_reason }}
                    </p>
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <label
                        for="notes"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Notes
                    </label>

                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        placeholder="Additional notes..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        :class="{
                            'border-red-400 focus:border-red-500 focus:ring-red-100':
                                form.errors.notes,
                        }"
                    ></textarea>

                    <p
                        v-if="form.errors.notes"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.notes }}
                    </p>
                </div>
            </div>

            <!-- Existing calculated values -->
            <div
                class="border-t border-slate-200 bg-slate-50 px-5 py-5"
            >
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-slate-900">
                        Current Calculated Values
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        These values will be recalculated automatically when
                        you save the changes.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <div
                        class="rounded-lg border border-slate-200 bg-white p-3"
                    >
                        <div class="text-xs text-slate-500">
                            Scheduled
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ attendance.scheduled_hours ?? 0 }}h
                        </div>
                    </div>

                    <div
                        class="rounded-lg border border-slate-200 bg-white p-3"
                    >
                        <div class="text-xs text-slate-500">
                            Worked
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ attendance.worked_hours ?? 0 }}h
                        </div>
                    </div>

                    <div
                        class="rounded-lg border border-slate-200 bg-white p-3"
                    >
                        <div class="text-xs text-slate-500">
                            Overtime
                        </div>

                        <div class="mt-1 font-semibold text-amber-700">
                            {{ attendance.overtime_hours ?? 0 }}h
                        </div>
                    </div>

                    <div
                        class="rounded-lg border border-slate-200 bg-white p-3"
                    >
                        <div class="text-xs text-slate-500">
                            Late
                        </div>

                        <div class="mt-1 font-semibold text-red-700">
                            {{ attendance.late_minutes ?? 0 }} min
                        </div>
                    </div>

                    <div
                        class="rounded-lg border border-slate-200 bg-white p-3"
                    >
                        <div class="text-xs text-slate-500">
                            Early
                        </div>

                        <div class="mt-1 font-semibold text-orange-700">
                            {{ attendance.early_departure_minutes ?? 0 }} min
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div
                class="flex flex-col-reverse gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:justify-end"
            >
                <Link
                    :href="route('attendance.index')"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <svg
                        v-if="form.processing"
                        class="mr-2 h-4 w-4 animate-spin"
                        viewBox="0 0 24 24"
                        fill="none"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />

                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                        />
                    </svg>

                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
        </form>
    </div>
</template>