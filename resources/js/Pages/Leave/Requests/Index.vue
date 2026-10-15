<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Company {
    id: number
    name: string
}

interface LeaveType {
    id: number
    name: string
    code: string
    is_paid: boolean
    requires_balance: boolean
}

interface Employee {
    id: number
    employee_number: string | null
    first_name: string
    middle_name: string | null
    last_name: string
}

interface Employee {
    id: number
    employee_number: string | null
    name: string
}

interface LeaveRequest {
    id: number
    employee: Employee
    leave_type: LeaveType
    start_date: string
    end_date: string
    days: number
    reason: string | null
    status: string
    submitted_by: string | null
    approved_by: string | null
    approved_at: string | null
    rejection_reason: string | null
    return_date: string | null
    created_at: string | null
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface PaginatedRequests {
    data: LeaveRequest[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    links: PaginationLink[]
}

interface Stats {
    total: number
    pending: number
    approved: number
    rejected: number
    cancelled: number
}

const props = defineProps<{
    company: Company
    requests: PaginatedRequests
    leaveTypes: LeaveType[]
    employees: Employee[]
    stats: Stats
    filters: {
        search?: string
        status?: string
        leave_type_id?: number | string
        employee_id?: number | string
        date_from?: string
        date_to?: string
    }
}>()

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')
const leaveTypeId = ref(
    props.filters.leave_type_id
        ? String(props.filters.leave_type_id)
        : ''
)

const employeeId = ref(
    props.filters.employee_id
        ? String(props.filters.employee_id)
        : ''
)

const dateFrom = ref(props.filters.date_from ?? '')
const dateTo = ref(props.filters.date_to ?? '')

let searchTimer: ReturnType<typeof setTimeout> | null = null

const applyFilters = () => {
    router.get(
        route('leave.requests.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            leave_type_id: leaveTypeId.value || undefined,
            employee_id: employeeId.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}

watch(search, () => {
    if (searchTimer) {
        clearTimeout(searchTimer)
    }

    searchTimer = setTimeout(() => {
        applyFilters()
    }, 400)
})

watch(
    [status, leaveTypeId, employeeId, dateFrom, dateTo],
    () => {
        applyFilters()
    }
)

const clearFilters = () => {
    search.value = ''
    status.value = ''
    leaveTypeId.value = ''
    employeeId.value = ''
    dateFrom.value = ''
    dateTo.value = ''
}

const hasFilters = computed(() => {
    return !!(
        search.value ||
        status.value ||
        leaveTypeId.value ||
        employeeId.value ||
        dateFrom.value ||
        dateTo.value
    )
})

const statusClasses = (value: string) => {
    switch (value) {
        case 'approved':
            return 'bg-green-50 text-green-700 ring-green-600/20'

        case 'pending':
            return 'bg-yellow-50 text-yellow-700 ring-yellow-600/20'

        case 'rejected':
            return 'bg-red-50 text-red-700 ring-red-600/20'

        case 'cancelled':
            return 'bg-gray-100 text-gray-600 ring-gray-500/20'

        default:
            return 'bg-gray-50 text-gray-600 ring-gray-500/20'
    }
}

const formatStatus = (value: string) => {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, char => char.toUpperCase())
}

const viewRequest = (id: number) => {
    router.visit(
        route('leave.requests.show', id)
    )
}

const createRequest = () => {
    router.visit(
        route('leave.requests.create')
    )
}

const goToPage = (url: string | null) => {
    if (!url) return

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <div class="p-6">

            <!-- Header -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        Leave Requests
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Manage employee leave requests for {{ company.name }}.
                    </p>
                </div>

                <button
                    type="button"
                    @click="createRequest"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>

                    Create Leave Request
                </button>
            </div>

            <!-- Statistics -->
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">

                <!-- Total -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">
                                Total Requests
                            </p>

                            <p class="mt-2 text-3xl font-semibold text-gray-900">
                                {{ stats.total }}
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50">
                            <svg
                                class="h-6 w-6 text-blue-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Pending -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">
                                Pending
                            </p>

                            <p class="mt-2 text-3xl font-semibold text-yellow-600">
                                {{ stats.pending }}
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-yellow-50">
                            <svg
                                class="h-6 w-6 text-yellow-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Approved -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">
                                Approved
                            </p>

                            <p class="mt-2 text-3xl font-semibold text-green-600">
                                {{ stats.approved }}
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-green-50">
                            <svg
                                class="h-6 w-6 text-green-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Rejected -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">
                                Rejected
                            </p>

                            <p class="mt-2 text-3xl font-semibold text-red-600">
                                {{ stats.rejected }}
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-red-50">
                            <svg
                                class="h-6 w-6 text-red-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Cancelled -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">
                                Cancelled
                            </p>

                            <p class="mt-2 text-3xl font-semibold text-gray-600">
                                {{ stats.cancelled }}
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-gray-100">
                            <svg
                                class="h-6 w-6 text-gray-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 6l12 12M6 18L18 6"
                                />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Card -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <!-- Filters -->
                <div class="border-b border-gray-200 p-5">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                        <!-- Search -->
                        <div class="relative flex-1">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21 21l-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"
                                />
                            </svg>

                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search employee..."
                                class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                        </div>

                        <!-- Status -->
                        <select
                            v-model="status"
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">
                                All Statuses
                            </option>

                            <option value="pending">
                                Pending
                            </option>

                            <option value="approved">
                                Approved
                            </option>

                            <option value="rejected">
                                Rejected
                            </option>

                            <option value="cancelled">
                                Cancelled
                            </option>
                        </select>

                        <!-- Leave Type -->
                        <select
                            v-model="leaveTypeId"
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">
                                All Leave Types
                            </option>

                            <option
                                v-for="type in leaveTypes"
                                :key="type.id"
                                :value="String(type.id)"
                            >
                                {{ type.name }}
                            </option>
                        </select>

                        <!-- Employee -->
                        <select
                            v-model="employeeId"
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">
                                All Employees
                            </option>

                            <option
                                v-for="employee in employees"
                                :key="employee.id"
                                :value="String(employee.id)"
                            >
                                {{
                                    employee.employee_number
                                        ? `${employee.employee_number} — ${employee.first_name} ${employee.last_name}`
                                        : `${employee.first_name} ${employee.last_name}`
                                }}
                            </option>
                        </select>

                        <!-- Date From -->
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <!-- Date To -->
                        <input
                            v-model="dateTo"
                            type="date"
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <!-- Clear -->
                        <button
                            v-if="hasFilters"
                            type="button"
                            @click="clearFilters"
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                        >
                            Clear
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Employee
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Leave Type
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Dates
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Days
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Status
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr
                                v-for="item in requests.data"
                                :key="item.id"
                                class="transition hover:bg-gray-50"
                            >
                                <!-- Employee -->
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="font-medium text-gray-900">
                                        {{ item.employee.name }}
                                    </div>

                                    <div
                                        v-if="item.employee.employee_number"
                                        class="mt-0.5 text-xs text-gray-500"
                                    >
                                        {{ item.employee.employee_number }}
                                    </div>
                                </td>

                                <!-- Leave Type -->
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="font-medium text-gray-800">
                                        {{ item.leave_type.name }}
                                    </div>

                                    <div class="mt-0.5 text-xs text-gray-500">
                                        {{ item.leave_type.code }}
                                    </div>
                                </td>

                                <!-- Dates -->
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="text-sm text-gray-800">
                                        {{ item.start_date }}
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        to {{ item.end_date }}
                                    </div>
                                </td>

                                <!-- Days -->
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="font-semibold text-gray-900">
                                        {{ item.days }}
                                    </span>

                                    <span class="ml-1 text-xs text-gray-500">
                                        days
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                        :class="statusClasses(item.status)"
                                    >
                                        {{ formatStatus(item.status) }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <button
                                        type="button"
                                        @click="viewRequest(item.id)"
                                        class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                    >
                                        View
                                    </button>
                                </td>
                            </tr>

                            <!-- Empty -->
                            <tr v-if="requests.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-5 py-16 text-center"
                                >
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                                        <svg
                                            class="h-7 w-7 text-gray-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"
                                            />
                                        </svg>
                                    </div>

                                    <h3 class="mt-4 text-sm font-semibold text-gray-900">
                                        No leave requests found
                                    </h3>

                                    <p class="mt-1 text-sm text-gray-500">
                                        Create a leave request to get started.
                                    </p>

                                    <button
                                        type="button"
                                        @click="createRequest"
                                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                    >
                                        <span>+</span>
                                        Create Leave Request
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="requests.last_page > 1"
                    class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-gray-500">
                        Showing
                        <span class="font-medium text-gray-700">
                            {{ requests.data.length }}
                        </span>
                        of
                        <span class="font-medium text-gray-700">
                            {{ requests.total }}
                        </span>
                        requests
                    </p>

                    <div class="flex flex-wrap gap-1">
                        <button
                            v-for="(link, index) in requests.links"
                            :key="index"
                            type="button"
                            :disabled="!link.url"
                            @click="goToPage(link.url)"
                            class="rounded-lg px-3 py-1.5 text-sm transition"
                            :class="[
                                link.active
                                    ? 'bg-blue-600 text-white'
                                    : 'text-gray-600 hover:bg-gray-100',
                                !link.url
                                    ? 'cursor-not-allowed opacity-40'
                                    : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>