<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'


interface Company {
    id: number
    name: string
    currency?: string
}

interface PayrollPeriod {
    id: number
    name: string
    start_date: string
    end_date: string
    pay_date: string | null
}

interface User {
    id: number
    name: string
}

interface PayrollRun {
    id: number
    run_number: string | null
    status: 'draft' | 'processing' | 'calculated' | 'approved' | 'paid' | 'cancelled'
    employee_count: number
    gross_pay: number | string
    total_deductions: number | string
    net_pay: number | string
    approved_at: string | null
    paid_at: string | null
    created_at: string
    notes: string | null
    payroll_period?: PayrollPeriod | null
    creator?: User | null
    approver?: User | null
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface PaginatedRuns {
    data: PayrollRun[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    links: PaginationLink[]
}

const props = defineProps<{
    company: Company
    runs: PaginatedRuns
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

const formatDate = (value: string | null | undefined): string => {
    if (!value) return '—'

    const datePart = value.substring(0, 10)
    const [year, month, day] = datePart.split('-').map(Number)

    if (!year || !month || !day) return '—'

    return new Date(year, month - 1, day).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

const formatMoney = (value: number | string | null | undefined): string => {
    const amount = Number(value ?? 0)

    return new Intl.NumberFormat('en-ZM', {
        style: 'currency',
        currency: props.company.currency || 'ZMW',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(amount) ? amount : 0)
}

const statusLabel = (status: PayrollRun['status']): string => {
    const labels: Record<PayrollRun['status'], string> = {
        draft: 'Draft',
        processing: 'Processing',
        calculated: 'Calculated',
        approved: 'Approved',
        paid: 'Paid',
        cancelled: 'Cancelled',
    }

    return labels[status]
}

const statusClass = (status: PayrollRun['status']): string => {
    const classes: Record<PayrollRun['status'], string> = {
        draft: 'bg-slate-100 text-slate-700',
        processing: 'bg-amber-100 text-amber-800',
        calculated: 'bg-blue-100 text-blue-800',
        approved: 'bg-indigo-100 text-indigo-800',
        paid: 'bg-emerald-100 text-emerald-800',
        cancelled: 'bg-red-100 text-red-800',
    }

    return classes[status]
}
</script>

<template>
    <Head title="Payroll Runs" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">
                        Payroll Runs
                    </h1>

                    <p class="mt-1 text-sm text-slate-600">
                        View and manage payroll runs for {{ company.name }}.
                    </p>
                </div>

                <Link
                    :href="route('payroll.periods.index')"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    View Payroll Periods
                </Link>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Total Payroll Runs</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ runs.total }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Current Page</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ runs.current_page }} of {{ runs.last_page }}
                    </p>
                </div>
            </div>

            <!-- Payroll Runs Table -->
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Run Number
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Payroll Period
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Employees
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Gross Pay
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Deductions
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Net Pay
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Status
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr
                                v-for="run in runs.data"
                                :key="run.id"
                                class="hover:bg-slate-50"
                            >
                                <td class="whitespace-nowrap px-6 py-4">
                                    <Link
                                        :href="route('payroll.runs.show', run.id)"
                                        class="font-semibold text-indigo-600 hover:text-indigo-800"
                                    >
                                        {{ run.run_number || `Run #${run.id}` }}
                                    </Link>

                                    <div class="mt-1 text-xs text-slate-500">
                                        Created {{ formatDate(run.created_at) }}
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-medium text-slate-900">
                                        {{ run.payroll_period?.name || 'Unknown period' }}
                                    </div>

                                    <div
                                        v-if="run.payroll_period"
                                        class="mt-1 text-xs text-slate-500"
                                    >
                                        {{ formatDate(run.payroll_period.start_date) }}
                                        —
                                        {{ formatDate(run.payroll_period.end_date) }}
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                    {{ run.employee_count }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                    {{ formatMoney(run.gross_pay) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                    {{ formatMoney(run.total_deductions) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-slate-900">
                                    {{ formatMoney(run.net_pay) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="statusClass(run.status)"
                                    >
                                        {{ statusLabel(run.status) }}
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="runs.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <p class="text-sm font-medium text-slate-900">
                                        No payroll runs found.
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Create a payroll run from an open payroll period to get started.
                                    </p>

                                    <Link
                                        :href="route('payroll.periods.index')"
                                        class="mt-4 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                    >
                                        View Payroll Periods
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="runs.last_page > 1"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-6 py-4"
                >
                    <p class="text-sm text-slate-500">
                        Page {{ runs.current_page }} of {{ runs.last_page }}
                    </p>

                    <div class="flex flex-wrap gap-1">
                        <Link
                            v-for="link in runs.links"
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
                            :preserve-scroll="true"
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
