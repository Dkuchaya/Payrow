<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

interface ScheduleDay {
    id: number
    day_of_week: number
    is_working_day: boolean
    start_time: string | null
    end_time: string | null
    break_minutes: number
    grace_period_minutes: number | null
    early_departure_grace_minutes: number | null
    allow_overtime: boolean | null
}

interface Schedule {
    id: number
    name: string
    code: string
    description: string | null
    default_start_time: string | null
    default_end_time: string | null
    default_break_minutes: number
    grace_period_minutes: number
    early_departure_grace_minutes: number
    overtime_after_minutes: number
    allow_overtime: boolean
    is_active: boolean
    employee_assignments_count: number
    days: ScheduleDay[]
}

interface Company {
    id: number
    name: string
}

const props = defineProps<{
    company: Company
    schedule: Schedule
}>()

const dayNames = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday',
]

const formatDay = (day: number) => {
    return dayNames[day - 1] ?? `Day ${day}`
}

const formatTime = (time: string | null) => {
    if (!time) {
        return '—'
    }

    return time.substring(0, 5)
}
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <Link
                            :href="route('work-schedules.index')"
                            class="mb-2 inline-block text-sm text-gray-500 hover:text-gray-700"
                        >
                            ← Back to Work Schedules
                        </Link>

                        <h1 class="text-2xl font-bold text-gray-900">
                            {{ schedule.name }}
                        </h1>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ company.name }}
                            ·
                            Code:
                            <span class="font-medium text-gray-700">
                                {{ schedule.code }}
                            </span>
                        </p>
                    </div>

                    <span
                        class="inline-flex rounded-full px-3 py-1 text-sm font-medium"
                        :class="
                            schedule.is_active
                                ? 'bg-green-100 text-green-700'
                                : 'bg-gray-100 text-gray-600'
                        "
                    >
                        {{ schedule.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <!-- Description -->
                <div
                    v-if="schedule.description"
                    class="mb-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <h2 class="mb-2 text-sm font-semibold text-gray-900">
                        Description
                    </h2>

                    <p class="text-sm text-gray-600">
                        {{ schedule.description }}
                    </p>
                </div>

                <!-- Schedule Settings -->
                <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h2 class="font-semibold text-gray-900">
                            Schedule Settings
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 lg:grid-cols-4">

                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">
                                Default Start
                            </p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">
                                {{ formatTime(schedule.default_start_time) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">
                                Default End
                            </p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">
                                {{ formatTime(schedule.default_end_time) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">
                                Break
                            </p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">
                                {{ schedule.default_break_minutes }} min
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">
                                Grace Period
                            </p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">
                                {{ schedule.grace_period_minutes }} min
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">
                                Early Departure Grace
                            </p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">
                                {{ schedule.early_departure_grace_minutes }} min
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">
                                Overtime After
                            </p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">
                                {{ schedule.overtime_after_minutes }} min
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">
                                Overtime
                            </p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">
                                {{ schedule.allow_overtime ? 'Allowed' : 'Not Allowed' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">
                                Employees Assigned
                            </p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">
                                {{ schedule.employee_assignments_count }}
                            </p>
                        </div>

                    </div>
                </div>

                <!-- Weekly Schedule -->
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h2 class="font-semibold text-gray-900">
                            Weekly Schedule
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Day
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Status
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Start
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        End
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Break
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Overtime
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="day in schedule.days"
                                    :key="day.id"
                                >
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-gray-900">
                                        {{ formatDay(day.day_of_week) }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                            :class="
                                                day.is_working_day
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-gray-100 text-gray-600'
                                            "
                                        >
                                            {{
                                                day.is_working_day
                                                    ? 'Working Day'
                                                    : 'Off Day'
                                            }}
                                        </span>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                                        {{
                                            day.is_working_day
                                                ? formatTime(day.start_time)
                                                : '—'
                                        }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                                        {{
                                            day.is_working_day
                                                ? formatTime(day.end_time)
                                                : '—'
                                        }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                                        {{
                                            day.is_working_day
                                                ? `${day.break_minutes} min`
                                                : '—'
                                        }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                                        {{
                                            day.is_working_day
                                                ? (
                                                    day.allow_overtime ??
                                                    schedule.allow_overtime
                                                )
                                                    ? 'Allowed'
                                                    : 'Not Allowed'
                                                : '—'
                                        }}
                                    </td>
                                </tr>

                                <tr v-if="schedule.days.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-5 py-8 text-center text-sm text-gray-500"
                                    >
                                        No schedule days found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>