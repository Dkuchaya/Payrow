<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Company {
    id: number
    name: string
}

interface Employee {
    id: number
    employee_number: string
    first_name: string
    middle_name?: string | null
    last_name: string
}

interface WorkSchedule {
    id: number
    name: string
    code: string
}

interface Assignment {
    id: number
    work_schedule_id: number
    work_schedule: WorkSchedule | null
    effective_from: string | null
    effective_to: string | null
    is_primary: boolean
    notes: string | null
}

const props = defineProps<{
    company: Company
    employee: Employee
    assignment: Assignment
    workSchedules: WorkSchedule[]
}>()

const employeeName = computed(() => {
    return [
        props.employee.first_name,
        props.employee.middle_name,
        props.employee.last_name,
    ]
        .filter(Boolean)
        .join(' ')
})

const form = ref({
    work_schedule_id: String(props.assignment.work_schedule_id),
    effective_from: props.assignment.effective_from ?? '',
    effective_to: props.assignment.effective_to ?? '',
    is_primary: props.assignment.is_primary,
    notes: props.assignment.notes ?? '',
})

const processing = ref(false)

const submit = () => {
    processing.value = true

    router.put(
        route(
            'employees.work-schedules.update',
            {
                employee: props.employee.id,
                assignment: props.assignment.id,
            }
        ),
        form.value,
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false
            },
        }
    )
}
</script>

<template>
    <Head title="Edit Work Schedule Assignment" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <Link
                        :href="route('employees.index')"
                        class="hover:text-gray-700"
                    >
                        Employees
                    </Link>

                    <span>/</span>

                    <Link
                        :href="route(
                            'employees.work-schedules.index',
                            props.employee.id
                        )"
                        class="hover:text-gray-700"
                    >
                        Work Schedules
                    </Link>

                    <span>/</span>

                    <span class="text-gray-700">
                        Edit
                    </span>
                </div>

                <h1 class="mt-2 text-2xl font-bold text-gray-900">
                    Edit Work Schedule Assignment
                </h1>

                <p class="mt-1 text-sm text-gray-600">
                    {{ employeeName }}
                    <span class="text-gray-400">•</span>
                    {{ employee.employee_number }}
                </p>
            </div>

            <Link
                :href="route(
                    'employees.work-schedules.index',
                    props.employee.id
                )"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
            >
                Back to Schedule History
            </Link>
        </div>

        <!-- Form -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Schedule Assignment
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Update the employee's work schedule and effective period.
                </p>
            </div>

            <form
                @submit.prevent="submit"
                class="space-y-6 p-6"
            >
                <!-- Work Schedule -->
                <div>
                    <label
                        for="work_schedule_id"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Work Schedule
                    </label>

                    <select
                        id="work_schedule_id"
                        v-model="form.work_schedule_id"
                        required
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">
                            Select work schedule
                        </option>

                        <option
                            v-for="schedule in workSchedules"
                            :key="schedule.id"
                            :value="String(schedule.id)"
                        >
                            {{ schedule.name }} ({{ schedule.code }})
                        </option>
                    </select>
                </div>

                <!-- Dates -->
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label
                            for="effective_from"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Effective From
                        </label>

                        <input
                            id="effective_from"
                            v-model="form.effective_from"
                            type="date"
                            required
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label
                            for="effective_to"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Effective To
                        </label>

                        <input
                            id="effective_to"
                            v-model="form.effective_to"
                            type="date"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />

                        <p class="mt-1 text-xs text-gray-500">
                            Leave blank if the schedule has no end date.
                        </p>
                    </div>
                </div>

                <!-- Primary -->
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <label class="flex items-start gap-3">
                        <input
                            v-model="form.is_primary"
                            type="checkbox"
                            class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />

                        <span>
                            <span class="block text-sm font-medium text-gray-900">
                                Primary schedule
                            </span>

                            <span class="mt-1 block text-sm text-gray-500">
                                Make this the employee's primary work schedule.
                            </span>
                        </span>
                    </label>
                </div>

                <!-- Notes -->
                <div>
                    <label
                        for="notes"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Notes
                    </label>

                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="4"
                        maxlength="2000"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Optional notes about this schedule assignment..."
                    ></textarea>

                    <p class="mt-1 text-xs text-gray-500">
                        Maximum 2000 characters.
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-6 sm:flex-row sm:justify-end">
                    <Link
                        :href="route(
                            'employees.work-schedules.index',
                            props.employee.id
                        )"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        :disabled="processing"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span v-if="processing">
                            Saving...
                        </span>

                        <span v-else>
                            Save Changes
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>