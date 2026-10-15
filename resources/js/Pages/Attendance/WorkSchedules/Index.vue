<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Day {
    day_of_week: number
    is_working_day: boolean
    start_time: string | null
    end_time: string | null
    break_minutes: number
}

interface Schedule {
    id: number
    name: string
    code: string
    description: string | null
    default_start_time: string
    default_end_time: string
    default_break_minutes: number
    grace_period_minutes: number
    early_departure_grace_minutes: number
    overtime_after_minutes: number
    allow_overtime: boolean
    is_active: boolean
    employee_assignments_count: number
    days: Day[]
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

const props = defineProps<{
    company: {
        id: number
        name: string
    }
    schedules: {
        data: Schedule[]
        links: PaginationLink[]
        current_page: number
        last_page: number
        total: number
    }
    filters: {
        search: string
        status: string
    }
}>()

const search = ref(props.filters.search || '')
const status = ref(props.filters.status || '')

const applyFilters = () => {
    router.get(
        route('work-schedules.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        }
    )
}

const clearFilters = () => {
    search.value = ''
    status.value = ''

    router.get(
        route('work-schedules.index'),
        {},
        {
            preserveState: true,
            replace: true,
        }
    )
}

const dayNames = [
    '',
    'Mon',
    'Tue',
    'Wed',
    'Thu',
    'Fri',
    'Sat',
    'Sun',
]

const workingDays = (schedule: Schedule) =>
    schedule.days.filter(day => day.is_working_day)

const formatTime = (time: string | null) => {
    if (!time) return '-'

    return time.substring(0, 5)
}
</script>

<template>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    Work Schedules
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Configure working hours, breaks, grace periods and overtime.
                </p>
            </div>

            <Link
                :href="route('work-schedules.create')"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
                + New Schedule
            </Link>
        </div>

        <!-- Filters -->
        <div class="bg-white border rounded-xl p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Search
                    </label>

                    <input
                        v-model="search"
                        type="text"
                        placeholder="Schedule name or code..."
                        class="w-full rounded-lg border-gray-300"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Status
                    </label>

                    <select
                        v-model="status"
                        class="w-full rounded-lg border-gray-300"
                        @change="applyFilters"
                    >
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button
                        type="button"
                        class="px-4 py-2 bg-gray-900 text-white rounded-lg"
                        @click="applyFilters"
                    >
                        Filter
                    </button>

                    <button
                        type="button"
                        class="px-4 py-2 border rounded-lg"
                        @click="clearFilters"
                    >
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Schedules -->
        <div class="bg-white border rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                Schedule
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                Working Days
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                Default Hours
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                Grace
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                Employees
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                Status
                            </th>

                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        <tr
                            v-for="schedule in schedules.data"
                            :key="schedule.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-4">
                                <Link
                                    :href="route('work-schedules.show', schedule.id)"
                                    class="font-medium text-blue-600 hover:underline"
                                >
                                    {{ schedule.name }}
                                </Link>

                                <div class="text-xs text-gray-500">
                                    {{ schedule.code }}
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm">
                                <div class="flex gap-1">
                                    <span
                                        v-for="day in workingDays(schedule)"
                                        :key="day.day_of_week"
                                        class="px-2 py-1 text-xs rounded bg-gray-100"
                                    >
                                        {{ dayNames[day.day_of_week] }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm">
                                {{ formatTime(schedule.default_start_time) }}
                                -
                                {{ formatTime(schedule.default_end_time) }}

                                <div class="text-xs text-gray-500">
                                    Break:
                                    {{ schedule.default_break_minutes }} min
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm">
                                {{ schedule.grace_period_minutes }} min
                            </td>

                            <td class="px-6 py-4 text-sm">
                                {{ schedule.employee_assignments_count }}
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full"
                                    :class="
                                        schedule.is_active
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-gray-100 text-gray-600'
                                    "
                                >
                                    {{
                                        schedule.is_active
                                            ? 'Active'
                                            : 'Inactive'
                                    }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center gap-4">
                                <Link
                                    :href="route('work-schedules.show', schedule.id)"
                                    class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                >
                                    View
                                </Link>

                                <Link
                                    :href="route('work-schedules.edit', schedule.id)"
                                    class="text-sm font-medium text-gray-600 hover:text-gray-900"
                                >
                                    Edit
                                </Link>
                            </div>
                            </td>
                        </tr>

                        <tr v-if="!schedules.data.length">
                            <td
                                colspan="7"
                                class="px-6 py-12 text-center text-gray-500"
                            >
                                No work schedules found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="schedules.last_page > 1"
                class="px-6 py-4 border-t flex flex-wrap gap-2"
            >
                <Link
                    v-for="link in schedules.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="px-3 py-2 rounded border text-sm"
                    :class="{
                        'bg-blue-600 text-white border-blue-600': link.active,
                        'text-gray-400 pointer-events-none': !link.url,
                    }"
                    v-html="link.label"
                    preserve-state
                />
            </div>
        </div>
    </div>
</template>