<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'



interface Company {
    id: number
    name: string
}

interface PayrollPeriod {
    id: number
    name: string
    start_date: string
    end_date: string
    pay_date: string | null
    frequency: 'weekly' | 'biweekly' | 'monthly'
    status: 'open' | 'processing' | 'processed' | 'approved' | 'paid' | 'locked'
    notes?: string | null
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface PaginatedPeriods {
    data: PayrollPeriod[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    links: PaginationLink[]
}

defineProps<{
    company: Company
    periods: PaginatedPeriods
}>()

const createPayrollRun = (period: PayrollPeriod) => {
    router.post(
        route('payroll.periods.runs.store', period.id),
        {},
        {
            preserveScroll: true,
        }
    )
}


const formatDate = (date: string | null): string => {
    if (!date) {
        return '—'
    }

    // Extract YYYY-MM-DD, whether the value is a date or ISO timestamp.
    const datePart = String(date).substring(0, 10)

    const match = datePart.match(/^(\d{4})-(\d{2})-(\d{2})$/)

    if (!match) {
        return '—'
    }

    const [, year, month, day] = match

    const parsedDate = new Date(
        Number(year),
        Number(month) - 1,
        Number(day)
    )

   

    if (
        parsedDate.getFullYear() !== Number(year) ||
        parsedDate.getMonth() !== Number(month) - 1 ||
        parsedDate.getDate() !== Number(day)
    ) {
        return '—'
    }

    return parsedDate.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

const frequencyLabel = (frequency: PayrollPeriod['frequency']): string => {
    switch (frequency) {
        case 'weekly':
            return 'Weekly'
        case 'biweekly':
            return 'Biweekly'
        case 'monthly':
            return 'Monthly'
        default:
            return frequency
    }
}

const statusLabel = (status: PayrollPeriod['status']): string => {
    switch (status) {
        case 'open':
            return 'Open'
        case 'processing':
            return 'Processing'
        case 'processed':
            return 'Processed'
        case 'approved':
            return 'Approved'
        case 'paid':
            return 'Paid'
        case 'locked':
            return 'Locked'
        default:
            return status
    }
}

const statusClass = (status: PayrollPeriod['status']): string => {
    switch (status) {
        case 'open':
            return 'bg-slate-100 text-slate-700'
        case 'processing':
            return 'bg-amber-100 text-amber-800'
        case 'processed':
            return 'bg-blue-100 text-blue-800'
        case 'approved':
            return 'bg-indigo-100 text-indigo-800'
        case 'paid':
            return 'bg-emerald-100 text-emerald-800'
        case 'locked':
            return 'bg-purple-100 text-purple-800'
        default:
            return 'bg-slate-100 text-slate-700'
    }
}
</script>

<template>
    <Head title="Payroll Periods" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">
                        Payroll Periods
                    </h1>

                    <p class="mt-1 text-sm text-slate-600">
                        Manage payroll periods for {{ company.name }}.
                    </p>
                </div>

                <Link
                    :href="route('payroll.periods.create')"
                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700"
                >
                    Create Payroll Period
                </Link>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">
                        Total Periods
                    </p>

                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ periods.total }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">
                        Current Page
                    </p>

                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ periods.current_page }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">
                        Per Page
                    </p>

                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ periods.per_page }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">
                        Pages
                    </p>

                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ periods.last_page }}
                    </p>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Payroll Period
                                </th>

                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Period
                                </th>

                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Pay Date
                                </th>

                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Frequency
                                </th>

                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Status
                                </th>

                                <th
                                    class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr
                                v-for="period in periods.data"
                                :key="period.id"
                                class="hover:bg-slate-50"
                            >
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-medium text-slate-900">
                                        {{ period.name }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        ID #{{ period.id }}
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                    {{ formatDate(period.start_date) }}
                                    —
                                    {{ formatDate(period.end_date) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                    {{ formatDate(period.pay_date) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                    {{ frequencyLabel(period.frequency) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="statusClass(period.status)"
                                    >
                                        {{ statusLabel(period.status) }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                   <div v-if="period.status === 'open'" class="flex items-center justify-end gap-4">
                                    <Link
                                        :href="route('payroll.periods.edit', period.id)"
                                        class="font-medium text-indigo-600 hover:text-indigo-800"
                                    >
                                        Edit
                                    </Link>

                                    <button
                                        type="button"
                                        @click="createPayrollRun(period)"
                                        class="font-medium text-emerald-600 hover:text-emerald-800"
                                    >
                                        Create Run
                                    </button>
                                </div>

                                    <span
                                        v-else
                                        class="text-sm text-slate-400"
                                    >
                                        Locked for editing
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="periods.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-6 py-12 text-center"
                                >
                                    <p class="text-sm font-medium text-slate-900">
                                        No payroll periods found.
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Create your first payroll period to get started.
                                    </p>

                                    <Link
                                        :href="route('payroll.periods.create')"
                                        class="mt-4 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                    >
                                        Create Payroll Period
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="periods.last_page > 1"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-6 py-4"
                >
                    <p class="text-sm text-slate-500">
                        Page {{ periods.current_page }} of {{ periods.last_page }}
                    </p>

                    <div class="flex flex-wrap gap-1">
                        <Link
                            v-for="link in periods.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            class="rounded-lg px-3 py-1.5 text-sm transition"
                            :class="[
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : link.url
                                      ? 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                                      : 'cursor-not-allowed border border-slate-100 bg-slate-50 text-slate-400',
                            ]"
                            preserve-scroll
                            :preserve-state="true"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>