<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Separation {
    id: number
    separation_type: string
    notice_date?: string | null
    effective_date: string
    last_working_date?: string | null
    reason?: string | null
    status: string
    employee: {
        id: number
        employee_number: string
        first_name: string
        middle_name?: string | null
        last_name: string
        department?: {
            name: string
        } | null
        position?: {
            name: string
        } | null
    }
}

interface PaginatedData {
    data: Separation[]
    current_page: number
    last_page: number
    from: number | null
    to: number | null
    total: number
    links: Array<{
        url: string | null
        label: string
        active: boolean
    }>
}

const props = defineProps<{
    separations: PaginatedData
    stats: {
        total: number
        pending: number
        approved: number
        clearance: number
        final_payroll: number
        completed: number
    }
    filters: {
        search: string
        status: string
    }
}>()

const search = ref(props.filters.search || '')
const status = ref(props.filters.status || 'all')

function applyFilters() {
    router.get(
        '/employee-separations',
        {
            search: search.value || undefined,
            status: status.value !== 'all' ? status.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}

function resetFilters() {
    search.value = ''
    status.value = 'all'

    router.get('/employee-separations')
}

function fullName(employee: Separation['employee']) {
    return [
        employee.first_name,
        employee.middle_name,
        employee.last_name,
    ]
        .filter(Boolean)
        .join(' ')
}

function typeLabel(type: string) {
    return type
        .replace(/_/g, ' ')
        .replace(/\b\w/g, letter => letter.toUpperCase())
}

function statusLabel(value: string) {
    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, letter => letter.toUpperCase())
}

function statusClass(value: string) {
    const classes: Record<string, string> = {
        pending: 'bg-amber-100 text-amber-800',
        approved: 'bg-blue-100 text-blue-800',
        clearance: 'bg-purple-100 text-purple-800',
        final_payroll: 'bg-indigo-100 text-indigo-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    }

    return classes[value] || 'bg-gray-100 text-gray-700'
}

function formatDate(value?: string | null) {
    if (!value) return '—'

    return new Date(value).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Employee Separations
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Manage employee separation requests and final settlement workflow.
                </p>
            </div>

            <Link
                href="/employees"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Employees
            </Link>
        </div>

        <!-- Statistics -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">

            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Total</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">
                    {{ stats.total }}
                </p>
            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Pending</p>
                <p class="mt-2 text-2xl font-bold text-amber-600">
                    {{ stats.pending }}
                </p>
            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Approved</p>
                <p class="mt-2 text-2xl font-bold text-blue-600">
                    {{ stats.approved }}
                </p>
            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Clearance</p>
                <p class="mt-2 text-2xl font-bold text-purple-600">
                    {{ stats.clearance }}
                </p>
            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Final Payroll</p>
                <p class="mt-2 text-2xl font-bold text-indigo-600">
                    {{ stats.final_payroll }}
                </p>
            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Completed</p>
                <p class="mt-2 text-2xl font-bold text-green-600">
                    {{ stats.completed }}
                </p>
            </div>

        </div>

        <!-- Filters -->
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <div class="grid gap-4 md:grid-cols-3">

                <div>
                    <label class="form-label">
                        Search
                    </label>

                    <input
                        v-model="search"
                        type="text"
                        class="form-input"
                        placeholder="Employee name or number..."
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div>
                    <label class="form-label">
                        Status
                    </label>

                    <select
                        v-model="status"
                        class="form-input"
                    >
                        <option value="all">
                            All statuses
                        </option>

                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="clearance">Clearance</option>
                        <option value="final_payroll">Final Payroll</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                        @click="applyFilters"
                    >
                        Filter
                    </button>

                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        @click="resetFilters"
                    >
                        Reset
                    </button>
                </div>

            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border bg-white shadow-sm">

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">

                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-heading">Employee</th>
                            <th class="table-heading">Separation Type</th>
                            <th class="table-heading">Effective Date</th>
                            <th class="table-heading">Last Working Day</th>
                            <th class="table-heading">Status</th>
                            <th class="table-heading text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">

                        <tr
                            v-for="separation in separations.data"
                            :key="separation.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="table-cell">
                                <div class="font-medium text-gray-900">
                                    {{ fullName(separation.employee) }}
                                </div>

                                <div class="text-xs text-gray-500">
                                    {{ separation.employee.employee_number }}
                                </div>
                            </td>

                            <td class="table-cell">
                                {{ typeLabel(separation.separation_type) }}
                            </td>

                            <td class="table-cell">
                                {{ formatDate(separation.effective_date) }}
                            </td>

                            <td class="table-cell">
                                {{ formatDate(separation.last_working_date) }}
                            </td>

                            <td class="table-cell">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="statusClass(separation.status)"
                                >
                                    {{ statusLabel(separation.status) }}
                                </span>
                            </td>

                            <td class="table-cell text-right">
                                <Link
                                    :href="`/employee-separations/${separation.id}`"
                                    class="font-medium text-blue-600 hover:text-blue-800"
                                >
                                    View
                                </Link>
                            </td>
                        </tr>

                        <tr v-if="!separations.data.length">
                            <td
                                colspan="6"
                                class="px-6 py-12 text-center"
                            >
                                <p class="font-medium text-gray-900">
                                    No separation requests found
                                </p>

                                <p class="mt-1 text-sm text-gray-500">
                                    Separation requests will appear here once submitted.
                                </p>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="separations.last_page > 1"
                class="flex items-center justify-between border-t px-5 py-4"
            >
                <p class="text-sm text-gray-500">
                    Showing {{ separations.from }}–{{ separations.to }}
                    of {{ separations.total }}
                </p>

                <div class="flex gap-1">
                    <template
                        v-for="link in separations.links"
                        :key="link.label"
                    >
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-md border px-3 py-1.5 text-sm"
                            :class="link.active
                                ? 'bg-blue-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-50'"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.form-label {
    @apply mb-1.5 block text-sm font-medium text-gray-700;
}

.form-input {
    @apply w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100;
}

.table-heading {
    @apply px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500;
}

.table-cell {
    @apply px-6 py-4 text-sm text-gray-700;
}
</style>