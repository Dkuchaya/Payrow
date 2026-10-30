
<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
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
    email?: string | null
}

interface AttendanceDevice {
    id: number
    name: string
}

interface User {
    id: number
    name: string
    email?: string
}

interface AttendanceRecord {
    id: number
    attendance_date: string
    clock_in_at: string | null
    clock_out_at: string | null
    scheduled_hours: number | string
    worked_hours: number | string
    overtime_hours: number | string
    break_minutes: number
    late_minutes: number
    early_departure_minutes: number
    status: string
    source: string
    is_manual_adjustment?: boolean
    adjustment_reason?: string | null
    notes?: string | null
    adjusted_at?: string | null
    employee?: Employee
    device?: AttendanceDevice
    adjustedBy?: User
}

interface AttendanceRecord {
    id: number
    attendance_date: string
    clock_in_at: string | null
    clock_out_at: string | null
    scheduled_hours: number | string | null
    worked_hours: number | string | null
    overtime_hours: number | string | null
    break_minutes: number | null
    late_minutes: number | null
    early_departure_minutes: number | null
    status: string
    source: string
    is_manual_adjustment?: boolean
    adjustment_reason?: string | null
    notes?: string | null
    adjusted_at?: string | null
    employee?: Employee | null
    device?: AttendanceDevice | null
    adjustedBy?: User

}

const props = defineProps<{
    company: Company
    record: AttendanceRecord
}>()

const employeeName = computed(() => {
    if (!props.record.employee) {
        return 'Unknown employee'
    }

    return `${props.record.employee.first_name} ${props.record.employee.last_name}`
})

const formatDate = (value: string | null | undefined) => {
    if (!value) return '—'

    const datePart = value.substring(0, 10)
    const [year, month, day] = datePart.split('-').map(Number)

    return new Date(year, month - 1, day).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    })
}

const formatTime = (value: string | null | undefined) => {
    if (!value) return '—'

    const match = value.match(/(?:T|\s)(\d{2}):(\d{2})/)

    if (!match) return value

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

    return Number.isFinite(number) ? number.toFixed(2) : '0.00'
}

const formatMinutes = (value: number | null | undefined) => {
    return `${value ?? 0} min`
}

const formatDateTime = (value: string | null | undefined) => {
    if (!value) return '—'

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) return value

    return date.toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const printAttendance = () => {
    window.print()
}

const statusClass = (status: string) => {
    switch (status?.toLowerCase()) {
        case 'present':
            return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
        case 'late':
            return 'bg-amber-50 text-amber-700 ring-amber-600/20'
        case 'absent':
            return 'bg-red-50 text-red-700 ring-red-600/20'
        case 'leave':
            return 'bg-blue-50 text-blue-700 ring-blue-600/20'
        case 'incomplete':
            return 'bg-orange-50 text-orange-700 ring-orange-600/20'
        default:
            return 'bg-slate-50 text-slate-700 ring-slate-600/20'
    }
}

const sourceLabel = computed(() => {
    return props.record.source
        ? props.record.source.charAt(0).toUpperCase() +
              props.record.source.slice(1)
        : 'Unknown'
})
</script>

<template>
    <Head title="Attendance Details" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <span>{{ company.name }}</span>
                    <span>/</span>
                    <Link
                        :href="route('attendance.index')"
                        class="hover:text-slate-900"
                    >
                        Attendance
                    </Link>
                    <span>/</span>
                    <span>Details</span>
                </div>

                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">
                    Attendance Details
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Attendance record for {{ employeeName }}.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="route('attendance.index')"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Back to Attendance
                </Link>

            <button
                type="button"
                @click="printAttendance"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                Print
            </button>

                <Link
                    :href="route('attendance.edit', record.id)"
                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800"
                >
                    Edit Record
                </Link>
            </div>
        </div>

        <!-- Employee information -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">
                    Employee Information
                </h2>

                <span
                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset"
                    :class="statusClass(record.status)"
                >
                    {{ record.status }}
                </span>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Employee Name
                    </p>
                    <p class="mt-1 font-medium text-slate-900">
                        {{ employeeName }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Employee Number
                    </p>
                    <p class="mt-1 font-medium text-slate-900">
                        {{ record.employee?.employee_number ?? '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Email Address
                    </p>
                    <p class="mt-1 font-medium text-slate-900">
                        {{ record.employee?.email ?? '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Attendance Date
                    </p>
                    <p class="mt-1 font-medium text-slate-900">
                        {{ formatDate(record.attendance_date) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Record ID
                    </p>
                    <p class="mt-1 font-medium text-slate-900">
                        #{{ record.id }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Recording Source
                    </p>
                    <p class="mt-1 font-medium text-slate-900">
                        {{ sourceLabel }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Attendance times -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-base font-semibold text-slate-900">
                Attendance Times
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-5">
                    <p class="text-sm font-medium text-emerald-700">
                        Clock In
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-900">
                        {{ formatTime(record.clock_in_at) }}
                    </p>
                    <p class="mt-1 text-xs text-emerald-700">
                        Employee arrival time
                    </p>
                </div>

                <div class="rounded-lg border border-blue-100 bg-blue-50 p-5">
                    <p class="text-sm font-medium text-blue-700">
                        Clock Out
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-blue-900">
                        {{ formatTime(record.clock_out_at) }}
                    </p>
                    <p class="mt-1 text-xs text-blue-700">
                        Employee departure time
                    </p>
                </div>
            </div>
        </div>

        <!-- Working hours -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-base font-semibold text-slate-900">
                Working Hours
            </h2>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs font-medium text-slate-500">
                        Scheduled Hours
                    </p>
                    <p class="mt-2 text-xl font-semibold text-slate-900">
                        {{ formatHours(record.scheduled_hours) }}h
                    </p>
                </div>

                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs font-medium text-slate-500">
                        Worked Hours
                    </p>
                    <p class="mt-2 text-xl font-semibold text-slate-900">
                        {{ formatHours(record.worked_hours) }}h
                    </p>
                </div>

                <div class="rounded-lg bg-amber-50 p-4">
                    <p class="text-xs font-medium text-amber-700">
                        Overtime
                    </p>
                    <p class="mt-2 text-xl font-semibold text-amber-900">
                        {{ formatHours(record.overtime_hours) }}h
                    </p>
                </div>

                <div class="rounded-lg bg-red-50 p-4">
                    <p class="text-xs font-medium text-red-700">
                        Late Arrival
                    </p>
                    <p class="mt-2 text-xl font-semibold text-red-900">
                        {{ formatMinutes(record.late_minutes) }}
                    </p>
                </div>

                <div class="rounded-lg bg-orange-50 p-4">
                    <p class="text-xs font-medium text-orange-700">
                        Early Departure
                    </p>
                    <p class="mt-2 text-xl font-semibold text-orange-900">
                        {{ formatMinutes(record.early_departure_minutes) }}
                    </p>
                </div>
            </div>

            <div class="mt-4 border-t border-slate-100 pt-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Break Duration
                </p>
                <p class="mt-1 font-medium text-slate-900">
                    {{ formatMinutes(record.break_minutes) }}
                </p>
            </div>
        </div>

        <!-- Device information -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-base font-semibold text-slate-900">
                Recording Information
            </h2>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Source
                    </p>
                    <p class="mt-1 font-medium text-slate-900">
                        {{ sourceLabel }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Attendance Device
                    </p>
                    <p class="mt-1 font-medium text-slate-900">
                        {{ record.device?.name ?? 'Not applicable' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Manual Adjustment
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-900">
                        {{ record.is_manual_adjustment ? 'Yes' : 'No' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Manual adjustment -->
<div
    v-if="record.is_manual_adjustment"
    class="rounded-lg border border-amber-200 bg-amber-50 p-5"
>
    <h3 class="text-sm font-semibold text-amber-900">
        Manual Adjustment
    </h3>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-amber-700">
                Adjusted By
            </p>

            <p class="mt-1 text-sm text-amber-950">
                {{ record.adjustedBy?.name ?? 'Unknown user' }}
            </p>

            <p
                v-if="record.adjustedBy?.email"
                class="mt-0.5 text-xs text-amber-700"
            >
                {{ record.adjustedBy.email }}
            </p>
        </div>

        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-amber-700">
                Adjusted At
            </p>

            <p class="mt-1 text-sm text-amber-950">
                {{ formatDateTime(record.adjusted_at) }}
            </p>
        </div>
    </div>

    <div
        v-if="record.adjustment_reason"
        class="mt-4 border-t border-amber-200 pt-4"
    >
        <p class="text-xs font-medium uppercase tracking-wide text-amber-700">
            Adjustment Reason
        </p>

        <p class="mt-1 text-sm text-amber-950">
            {{ record.adjustment_reason }}
        </p>
    </div>
</div>

        <!-- Notes -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">
                Notes
            </h2>

            <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">
                {{ record.notes || 'No notes have been added to this attendance record.' }}
            </p>
        </div>
    </div>
</template>
