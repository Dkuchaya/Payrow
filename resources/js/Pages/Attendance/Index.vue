```vue
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
    date_from?: string | null
    date_to?: string | null
    employee_id?: number | string | null
    status?: string | null
    source?: string | null
}

interface AttendanceSummary {
    present: number
    late: number
    absent: number
    incomplete: number
    overtime_hours: number | string
}

const props = defineProps<{
    company: Company
    attendance: PaginatedAttendance
    employees: Employee[]
    filters: Filters
    summary: AttendanceSummary
}>()

const dateFrom = ref(props.filters.date_from ?? '')
const dateTo = ref(props.filters.date_to ?? '')

const employeeId = ref(
    props.filters.employee_id
        ? String(props.filters.employee_id)
        : ''
)

const status = ref(props.filters.status ?? '')
const source = ref(props.filters.source ?? '')

const isFiltering = ref(false)

const hasFilters = computed(() => {
    return Boolean(
        dateFrom.value ||
        dateTo.value ||
        employeeId.value ||
        status.value ||
        source.value
    )
})

const employeeName = (employee?: Employee | null) => {
    if (!employee) {
        return 'Unknown employee'
    }

    return `${employee.first_name} ${employee.last_name}`
}

const formatTime = (value: string | null) => {
    if (!value) {
        return '—'
    }

    const match = value.match(/T(\d{2}):(\d{2})/)

    if (!match) {
        return '—'
    }

    const hours = Number(match[1])
    const minutes = match[2]

    const period = hours >= 12 ? 'PM' : 'AM'
    const displayHour = hours % 12 || 12

    return `${displayHour}:${minutes} ${period}`
}

const formatHours = (value: number | string | null | undefined) => {
    if (value === null || value === undefined || value === '') {
        return '0.00'
    }

    const number = Number(value)

    if (Number.isNaN(number)) {
        return '0.00'
    }

    return number.toFixed(2)
}

const formatDate = (value: string | null | undefined) => {
    if (!value) {
        return '—'
    }

    const datePart = value.substring(0, 10)
    const [year, month, day] = datePart.split('-').map(Number)

    const date = new Date(year, month - 1, day)

    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

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

        case 'incomplete':
            return 'bg-orange-50 text-orange-700 ring-1 ring-orange-600/20'

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
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            employee_id: employeeId.value || undefined,
            status: status.value || undefined,
            source: source.value || undefined,
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
    dateFrom.value = ''
    dateTo.value = ''
    employeeId.value = ''
    status.value = ''
    source.value = ''

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
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <span>{{ company.name }}</span>
                    <span>/</span>
                    <span>Attendance</span>
                </div>

                <h1
                    class="mt-1 text-2xl font-semibold tracking-tight text-slate-900"
                >
                    Attendance
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Monitor employee attendance, working hours, lateness and
                    overtime.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
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
        </div>

        <!-- Filters -->
        <div
            class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
        >
            <div class="mb-4">
                <h2 class="text-sm font-semibold text-slate-900">
                    Attendance Filters
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    Filter attendance records by date, employee, status or
                    source.
                </p>
            </div>

            <div
                class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5"
            >
                <!-- Date From -->
                <div>
                    <label
                        for="attendance-date-from"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Date From
                    </label>

                    <input
                        id="attendance-date-from"
                        v-model="dateFrom"
                        type="date"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    />
                </div>

                <!-- Date To -->
                <div>
                    <label
                        for="attendance-date-to"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Date To
                    </label>

                    <input
                        id="attendance-date-to"
                        v-model="dateTo"
                        type="date"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    />
                </div>

                <!-- Employee -->
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

                <!-- Status -->
                <div>
                    <label
                        for="attendance-status"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Status
                    </label>

                    <select
                        id="attendance-status"
                        v-model="status"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    >
                        <option value="">
                            All statuses
                        </option>

                        <option value="present">
                            Present
                        </option>

                        <option value="late">
                            Late
                        </option>

                        <option value="absent">
                            Absent
                        </option>

                        <option value="incomplete">
                            Incomplete
                        </option>

                        <option value="leave">
                            Leave
                        </option>
                    </select>
                </div>

                <!-- Source -->
                <div>
                    <label
                        for="attendance-source"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Source
                    </label>

                    <select
                        id="attendance-source"
                        v-model="source"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    >
                        <option value="">
                            All sources
                        </option>

                        <option value="device">
                            Device
                        </option>

                        <option value="manual">
                            Manual
                        </option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                <button
                    v-if="hasFilters"
                    type="button"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    @click="clearFilters"
                >
                    Reset
                </button>

                <button
                    type="button"
                    :disabled="isFiltering"
                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                    @click="applyFilters"
                >
                    {{ isFiltering ? 'Filtering...' : 'Apply Filters' }}
                </button>
            </div>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">

            <!-- Present -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <p class="text-sm text-slate-500">
                    Present
                </p>

                <p class="mt-1 text-2xl font-semibold text-emerald-600">
                    {{ summary.present }}
                </p>
            </div>

            <!-- Late -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <p class="text-sm text-slate-500">
                    Late
                </p>

                <p class="mt-1 text-2xl font-semibold text-amber-600">
                    {{ summary.late }}
                </p>
            </div>

            <!-- Absent -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <p class="text-sm text-slate-500">
                    Absent
                </p>

                <p class="mt-1 text-2xl font-semibold text-red-600">
                    {{ summary.absent }}
                </p>
            </div>

            <!-- Incomplete -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <p class="text-sm text-slate-500">
                    Incomplete
                </p>

                <p class="mt-1 text-2xl font-semibold text-orange-600">
                    {{ summary.incomplete }}
                </p>
            </div>

            <!-- Overtime -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <p class="text-sm text-slate-500">
                    Overtime Hours
                </p>

                <p class="mt-1 text-2xl font-semibold text-indigo-600">
                    {{ formatHours(summary.overtime_hours) }}h
                </p>
            </div>
        </div>

        <!-- Attendance Table -->
        <div
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            <div class="overflow-x-auto">
                <table
                    class="min-w-[1200px] w-full divide-y divide-slate-200"
                >
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
                                class="w-[8%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
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
                                <div
                                    class="max-w-[180px] truncate font-medium text-slate-900"
                                >
                                    {{ employeeName(record.employee) }}
                                </div>

                                <div class="mt-0.5 text-xs text-slate-500">
                                    {{ record.employee?.employee_number ?? '—' }}
                                </div>
                            </td>

                            <!-- Date -->
                            <td
                                class="whitespace-nowrap px-3 py-3 text-sm text-slate-700"
                            >
                                {{ formatDate(record.attendance_date) }}
                            </td>

                            <!-- Clock In -->
                            <td
                                class="whitespace-nowrap px-3 py-3 text-sm text-slate-700"
                            >
                                {{ formatTime(record.clock_in_at) }}
                            </td>

                            <!-- Clock Out -->
                            <td
                                class="whitespace-nowrap px-3 py-3 text-sm text-slate-700"
                            >
                                {{ formatTime(record.clock_out_at) }}
                            </td>

                            <!-- Scheduled -->
                            <td
                                class="whitespace-nowrap px-3 py-3 text-sm text-slate-700"
                            >
                                {{ formatHours(record.scheduled_hours) }}h
                            </td>

                            <!-- Worked -->
                            <td
                                class="whitespace-nowrap px-3 py-3 text-sm font-medium text-slate-900"
                            >
                                {{ formatHours(record.worked_hours) }}h
                            </td>

                            <!-- Overtime -->
                            <td
                                class="whitespace-nowrap px-3 py-3 text-sm"
                            >
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
                            <td
                                class="whitespace-nowrap px-3 py-3 text-sm"
                            >
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
                            <td
                                class="whitespace-nowrap px-3 py-3 text-sm"
                            >
                                <span
                                    :class="
                                        Number(
                                            record.early_departure_minutes ?? 0
                                        ) > 0
                                            ? 'inline-flex rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700'
                                            : 'text-slate-600'
                                    "
                                >
                                    {{ record.early_departure_minutes ?? 0 }} min
                                </span>
                            </td>

                            <!-- Status -->
                            <td
                                class="whitespace-nowrap px-3 py-3"
                            >
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="statusClass(record.status)"
                                >
                                    {{ record.status }}
                                </span>
                            </td>

                            <!-- Source -->
                            <td
                                class="whitespace-nowrap px-3 py-3"
                            >
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
                            <td
                                class="whitespace-nowrap px-3 py-3"
                            >
                             <Link
                                :href="route('attendance.attendance.show', record.id)"
                                class="font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                View
                            </Link>

                                <Link
                                    :href="
                                        route(
                                            'attendance.edit',
                                            record.id
                                        )
                                    "
                                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                >
                                    Edit
                                </Link>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="attendance.data.length === 0">
                            <td
                                colspan="12"
                                class="px-6 py-14 text-center"
                            >
                                <div
                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100"
                                >
                                    <svg
                                        class="h-6 w-6 text-slate-400"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M8 7V5a4 4 0 018 0v2m-9 0h10a2 2 0 012 2v9a2 2 0 01-2 2H7a2 2 0 01-2-2V9a2 2 0 012-2zm3 4h4m-4 4h4"
                                        />
                                    </svg>
                                </div>

                                <h3
                                    class="mt-4 text-sm font-semibold text-slate-900"
                                >
                                    No attendance records found
                                </h3>

                                <p
                                    class="mt-1 text-sm text-slate-500"
                                >
                                    Try changing your filters or record
                                    attendance for an employee.
                                </p>
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
                        v-for="(link, index) in attendance.links"
                        :key="`${link.label}-${index}`"
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
```