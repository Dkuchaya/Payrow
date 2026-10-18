<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

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

const props = defineProps<{
    company: Company;
    employees: Employee[];
}>();



const today = new Date().toISOString().split('T')[0];

const form = useForm({
    employee_id: '',
    attendance_date: today,
    clock_in_at: '',
    clock_out_at: '',
    status: 'present',
    adjustment_reason: '',
    notes: '',
});

const selectedEmployee = computed(() =>
    props.employees.find(
        employee => String(employee.id) === String(form.employee_id)
    )
);

const submit = () => {
    form.post(route('attendance.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Record Attendance" />

    <AppLayout>
        <div class="py-6">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">
                            Record Attendance
                        </h1>

                        <p class="mt-1 text-sm text-gray-500">
                            Manually record attendance for an employee.
                        </p>
                    </div>

                    <Link
                        :href="route('attendance.index')"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Back to Attendance
                    </Link>
                </div>

                <!-- Form -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">

                    <form @submit.prevent="submit" class="p-6">

                        <!-- Employee -->
                        <div class="mb-6">
                            <label
                                for="employee_id"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Employee <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="employee_id"
                                v-model="form.employee_id"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">
                                    Select employee
                                </option>

                                <option
                                    v-for="employee in employees"
                                    :key="employee.id"
                                    :value="employee.id"
                                >
                                    {{ employee.employee_number }}
                                    -
                                    {{ employee.first_name }}
                                    {{ employee.last_name }}
                                </option>
                            </select>

                            <p
                                v-if="form.errors.employee_id"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.employee_id }}
                            </p>
                        </div>

                        <!-- Selected Employee -->
                        <div
                            v-if="selectedEmployee"
                            class="mb-6 rounded-lg border border-indigo-100 bg-indigo-50 p-4"
                        >
                            <div class="text-sm text-gray-500">
                                Selected Employee
                            </div>

                            <div class="mt-1 text-base font-semibold text-gray-900">
                                {{ selectedEmployee.first_name }}
                                {{ selectedEmployee.last_name }}
                            </div>

                            <div class="text-sm text-gray-600">
                                Employee No:
                                {{ selectedEmployee.employee_number }}
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="mb-6">
                            <label
                                for="attendance_date"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Attendance Date
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="attendance_date"
                                v-model="form.attendance_date"
                                type="date"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <p
                                v-if="form.errors.attendance_date"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.attendance_date }}
                            </p>
                        </div>

                        <!-- Clock Times -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                            <!-- Clock In -->
                            <div>
                                <label
                                    for="clock_in_at"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Clock In
                                </label>

                                <input
                                    id="clock_in_at"
                                    v-model="form.clock_in_at"
                                    type="datetime-local"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Clock Out
                                </label>

                                <input
                                    id="clock_out_at"
                                    v-model="form.clock_out_at"
                                    type="datetime-local"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />

                                <p
                                    v-if="form.errors.clock_out_at"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.clock_out_at }}
                                </p>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mt-6">
                            <label
                                for="status"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Status
                            </label>

                            <select
                                id="status"
                                v-model="form.status"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="late">Late</option>
                                <option value="half_day">Half Day</option>
                                <option value="leave">Leave</option>
                                <option value="holiday">Holiday</option>
                                <option value="rest_day">Rest Day</option>
                            </select>
                        </div>

                        <!-- Adjustment Reason -->
                        <div class="mt-6">
                            <label
                                for="adjustment_reason"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Adjustment Reason
                            </label>

                            <textarea
                                id="adjustment_reason"
                                v-model="form.adjustment_reason"
                                rows="3"
                                placeholder="Why is this attendance being manually recorded or adjusted?"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>

                            <p
                                v-if="form.errors.adjustment_reason"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.adjustment_reason }}
                            </p>
                        </div>

                        <!-- Notes -->
                        <div class="mt-6">
                            <label
                                for="notes"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Notes
                            </label>

                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                placeholder="Additional notes..."
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>

                            <p
                                v-if="form.errors.notes"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.notes }}
                            </p>
                        </div>

                        <!-- Information -->
                        <div class="mt-6 rounded-lg bg-gray-50 p-4">
                            <h3 class="text-sm font-semibold text-gray-900">
                                Attendance Calculation
                            </h3>

                            <p class="mt-1 text-sm text-gray-600">
                                Scheduled hours, break time, late minutes,
                                early departure and overtime will be
                                calculated automatically using the employee's
                                assigned work schedule.
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-200 pt-6">

                            <Link
                                :href="route('attendance.index')"
                                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </Link>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {{
                                    form.processing
                                        ? 'Saving...'
                                        : 'Record Attendance'
                                }}
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </AppLayout>
</template>