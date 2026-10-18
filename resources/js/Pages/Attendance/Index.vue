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
    last_name: string
}

interface AttendanceRecord {
    id: number
    attendance_date: string
    clock_in_at: string | null
    clock_out_at: string | null
    scheduled_hours: number | string | null
    worked_hours: number | string | null
    overtime_hours: number | string | null
    late_minutes: number | null
    early_departure_minutes: number | null
    status: string
    source: string
    employee?: Employee | null
    device?: {
        id: number
        name: string
    } | null
    
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface PaginatedAttendance {
    data: AttendanceRecord[]
    current_page: number
    last_page: number
    from: number | null
    to: number | null
    total: number
    links: PaginationLink[]
}

interface Filters {
    date?: string | null
    employee_id?: number | string | null
}

const props = defineProps<{
    company: Company
    attendance: PaginatedAttendance
    employees: Employee[]
    filters: Filters
}>()

const date = ref(props.filters.date ?? '')
const employeeId = ref(
    props.filters.employee_id
        ? String(props.filters.employee_id)
        : ''
)

const isFiltering = ref(false)

const hasFilters = computed(() => {
    return Boolean(date.value || employeeId.value)
})

const employeeName = (employee?: Employee | null) => {
    if (!employee) {
        return 'Unknown employee'
    }

    return `${employee.first_name} ${employee.last_name}`
}

const formatTime = (value: string | null) => {
    if (!value) {
        return '—';
    }

    const match = value.match(/T(\d{2}):(\d{2})/);

    if (!match) {
        return '—';
    }

    const hours = Number(match[1]);
    const minutes = match[2];

    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHour = hours % 12 || 12;

    return `${displayHour}:${minutes} ${period}`;
};

const formatHours = (value: number | string | null) => {
    if (value === null || value === undefined || value === '') {
        return '0.00'
    }

    return Number(value).toFixed(2)
}

const formatDate = (value: string | null | undefined) => {
    if (!value) {
        return '—';
    }

    const datePart = value.substring(0, 10);

    const [year, month, day] = datePart.split('-').map(Number);

    const date = new Date(year, month - 1, day);

    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const statusClass = (status: string) => {
    switch (status.toLowerCase()) {
        case 'present':
            return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20'

        case 'late':
            return 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20'

        case 'absent':
            return 'bg-red-50 text-red-700 ring-1 ring-red-600/20'

        case 'leave':
            return 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20'

        default:
            return 'bg-slate-50 text-slate-700 ring-1 ring-slate-600/20'
    }
}

const sourceClass = (source: string) => {
    return source.toLowerCase() === 'device'
        ? 'bg-indigo-50 text-indigo-700'
        : 'bg-slate-100 text-slate-700'
}

const applyFilters = () => {
    isFiltering.value = true

    router.get(
        route('attendance.index'),
        {
            date: date.value || undefined,
            employee_id: employeeId.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                isFiltering.value = false
            },
        }
    )
}

const clearFilters = () => {
    date.value = ''
    employeeId.value = ''

    applyFilters()
}

const goToPage = (url: string | null) => {
    if (!url) {
        return
    }

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Attendance" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <span>{{ company.name }}</span>
                    <span>/</span>
                    <span>Attendance</span>
                </div>

                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
                    Attendance
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Monitor employee attendance, working hours, lateness and overtime.
                </p>
            </div>

            <Link
                :href="route('work-schedules.index')"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                Work Schedules
            </Link>

            <Link
                :href="route('attendance.create')"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800"
            >
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 5v14M5 12h14"
                    />
                </svg>

                Record Attendance
            </Link>
        </div>

        <!-- Filters -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label
                        for="attendance-date"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Date
                    </label>

                    <input
                        id="attendance-date"
                        v-model="date"
                        type="date"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    />
                </div>

                <div>
                    <label
                        for="attendance-employee"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Employee
                    </label>

                    <select
                        id="attendance-employee"
                        v-model="employeeId"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    >
                        <option value="">
                            All employees
                        </option>

                        <option
                            v-for="employee in employees"
                            :key="employee.id"
                            :value="employee.id"
                        >
                            {{ employee.employee_number }} —
                            {{ employee.first_name }}
                            {{ employee.last_name }}
                        </option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button
                        type="button"
                        :disabled="isFiltering"
                        class="inline-flex flex-1 items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="applyFilters"
                    >
                        {{ isFiltering ? 'Filtering...' : 'Apply Filters' }}
                    </button>

                    <button
                        v-if="hasFilters"
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        @click="clearFilters"
                    >
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm text-slate-500">
                    Records
                </p>

                <p class="mt-1 text-2xl font-semibold text-slate-900">
                    {{ attendance.total }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm text-slate-500">
                    Showing
                </p>

                <p class="mt-1 text-2xl font-semibold text-slate-900">
                    {{ attendance.from ?? 0 }}–{{ attendance.to ?? 0 }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm text-slate-500">
                    Current Page
                </p>

                <p class="mt-1 text-2xl font-semibold text-slate-900">
                    {{ attendance.current_page }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm text-slate-500">
                    Pages
                </p>

                <p class="mt-1 text-2xl font-semibold text-slate-900">
                    {{ attendance.last_page }}
                </p>
            </div>
        </div>

        <!-- Table -->
       <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-[1100px] w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th
                        class="w-[16%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                    >
                        Employee
                    </th>

                    <th
                        class="w-[10%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                    >
                        Date
                    </th>

                    <th
                        class="w-[9%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                    >
                        Clock In
                    </th>

                    <th
                        class="w-[9%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                    >
                        Clock Out
                    </th>

                    <th
                        class="w-[8%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                    >
                        Scheduled
                    </th>

                    <th
                        class="w-[8%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                    >
                        Worked
                    </th>

                    <th
                        class="w-[8%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                    >
                        Overtime
                    </th>

                    <th
                        class="w-[8%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                    >
                        Late
                    </th>

                    <th
                        class="w-[10%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                    >
                        Early
                    </th>

                    <th
                        class="w-[7%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                    >
                        Status
                    </th>

                    <th
                        class="w-[7%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                    >
                        Source
                    </th>
                    <th
                        class="w-[7%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                    >
                        Actions
                    </th>
                </tr>
            </thead>

           <tbody class="divide-y divide-slate-100 bg-white">
    <tr
        v-for="record in attendance.data"
        :key="record.id"
        class="transition hover:bg-slate-50"
    >
        <!-- Employee -->
        <td class="px-3 py-3">
            <div class="max-w-[180px] truncate font-medium text-slate-900">
                {{ employeeName(record.employee) }}
            </div>

            <div class="mt-0.5 text-xs text-slate-500">
                {{ record.employee?.employee_number ?? '—' }}
            </div>
        </td>

        <!-- Date -->
        <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-700">
            {{ formatDate(record.attendance_date) }}
        </td>

        <!-- Clock In -->
        <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-700">
            {{ formatTime(record.clock_in_at) }}
        </td>

        <!-- Clock Out -->
        <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-700">
            {{ formatTime(record.clock_out_at) }}
        </td>

        <!-- Scheduled -->
        <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-700">
            {{ formatHours(record.scheduled_hours) }}h
        </td>

        <!-- Worked -->
        <td class="whitespace-nowrap px-3 py-3 text-sm font-medium text-slate-900">
            {{ formatHours(record.worked_hours) }}h
        </td>

        <!-- Overtime -->
        <td class="whitespace-nowrap px-3 py-3 text-sm">
            <span
                :class="
                    Number(record.overtime_hours) > 0
                        ? 'inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700'
                        : 'text-slate-600'
                "
            >
                {{ formatHours(record.overtime_hours) }}h
            </span>
        </td>

        <!-- Late -->
        <td class="whitespace-nowrap px-3 py-3 text-sm">
            <span
                :class="
                    Number(record.late_minutes ?? 0) > 0
                        ? 'inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700'
                        : 'text-slate-600'
                "
            >
                {{ record.late_minutes ?? 0 }} min
            </span>
        </td>

        <!-- Early -->
        <td class="whitespace-nowrap px-3 py-3 text-sm">
            <span
                :class="
                    Number(record.early_departure_minutes ?? 0) > 0
                        ? 'inline-flex rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700'
                        : 'text-slate-600'
                "
            >
                {{ record.early_departure_minutes ?? 0 }} min
            </span>
        </td>

        <!-- Status -->
        <td class="whitespace-nowrap px-3 py-3">
            <span
                class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                :class="statusClass(record.status)"
            >
                {{ record.status }}
            </span>
        </td>

        <!-- Source -->
        <td class="whitespace-nowrap px-3 py-3">
            <span
                class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                :class="sourceClass(record.source)"
            >
                {{ record.source }}
            </span>

            <div
                v-if="record.device"
                class="mt-1 text-xs text-slate-500"
            >
                {{ record.device.name }}
            </div>
        </td>

        <!-- Actions -->
        <td class="whitespace-nowrap px-3 py-3">
            <Link
                :href="route('attendance.edit', record.id)"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
            >
                Edit
            </Link>
        </td>
    </tr>

    <tr v-if="attendance.data.length === 0">
        <td colspan="12" class="px-6 py-14 text-center">
            <!-- existing empty state -->
        </td>
    </tr>
</tbody>
        </table>
    </div>


            <!-- Pagination -->
            <div
                v-if="attendance.last_page > 1"
                class="flex flex-col gap-3 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-sm text-slate-500">
                    Showing
                    <span class="font-medium text-slate-700">
                        {{ attendance.from ?? 0 }}
                    </span>
                    to
                    <span class="font-medium text-slate-700">
                        {{ attendance.to ?? 0 }}
                    </span>
                    of
                    <span class="font-medium text-slate-700">
                        {{ attendance.total }}
                    </span>
                    records
                </p>

                <div class="flex flex-wrap gap-1">
                    <button
                        v-for="link in attendance.links"
                        :key="link.label"
                        type="button"
                        :disabled="!link.url"
                        class="min-w-9 rounded-lg border px-3 py-2 text-sm transition"
                        :class="[
                            link.active
                                ? 'border-slate-900 bg-slate-900 text-white'
                                : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                            !link.url
                                ? 'cursor-not-allowed opacity-40'
                                : '',
                        ]"
                        @click="goToPage(link.url)"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </div>
</template>