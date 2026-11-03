
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
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
    frequency: string
}

interface User {
    id: number
    name: string
}
interface PayrollRun {
    id: number
    run_number: string | null
    status: string

    apply_attendance_rules: boolean
    deduct_unpaid_absence: boolean
    deduct_unpaid_leave: boolean
    deduct_late_time: boolean
    deduct_early_departure: boolean
    pay_overtime: boolean

    employee_count: number
    gross_pay: string | number
    total_deductions: string | number
    net_pay: string | number
    approved_at: string | null
    paid_at: string | null
    created_at: string
    notes: string | null
    payroll_period: PayrollPeriod
    creator: User | null
    approver: User | null
    items: PayrollRunItem[]
}

interface PayrollRunItemComponent {
    id: number
    name: string
    type: 'allowance' | 'deduction'
    calculation_type: 'fixed' | 'percentage'
    amount: string | number
    percentage: string | number | null
    calculated_amount: string | number
    is_taxable: boolean
}

interface PayrollRunItem {
    id: number
    basic_salary: string | number
    total_allowances: string | number
    gross_pay: string | number
    total_deductions: string | number
    net_pay: string | number
    status: string

    employee: {
        id: number
        employee_number: string
        first_name: string
        middle_name?: string | null
        last_name: string
    }

    components?: PayrollRunItemComponent[]
}

const props = defineProps<{
    company: Company
    run: PayrollRun
    
}>()

const addEmployees = () => {
    router.post(
        route('payroll.runs.employees.store', props.run.id),
        {},
        {
            preserveScroll: true,
        }
    )
}


const applyAttendanceRules = ref(props.run.apply_attendance_rules)
const deductUnpaidAbsence = ref(props.run.deduct_unpaid_absence)
const deductUnpaidLeave = ref(props.run.deduct_unpaid_leave)
const deductLateTime = ref(props.run.deduct_late_time)
const deductEarlyDeparture = ref(props.run.deduct_early_departure)
const payOvertime = ref(props.run.pay_overtime)

watch(applyAttendanceRules, (enabled) => {
    if (!enabled) {
        deductUnpaidAbsence.value = false
        deductUnpaidLeave.value = false
        deductLateTime.value = false
        deductEarlyDeparture.value = false
        payOvertime.value = false
    }
})

const savePayrollOptions = () => {
    router.patch(
        route('payroll.runs.options.update', props.run.id),
        {
            apply_attendance_rules: applyAttendanceRules.value,
            deduct_unpaid_absence: applyAttendanceRules.value
                ? deductUnpaidAbsence.value
                : false,
            deduct_unpaid_leave: applyAttendanceRules.value
                ? deductUnpaidLeave.value
                : false,
            deduct_late_time: applyAttendanceRules.value
                ? deductLateTime.value
                : false,
            deduct_early_departure: applyAttendanceRules.value
                ? deductEarlyDeparture.value
                : false,
            pay_overtime: applyAttendanceRules.value
                ? payOvertime.value
                : false,
        },
        {
            preserveScroll: true,
        }
    )
}

const calculatePayroll = () => {
    if (!confirm('Calculate this payroll run now? This will calculate allowances, deductions, gross pay and net pay.')) {
        return
    }

    router.post(
        route('payroll.runs.calculate', props.run.id),
        {},
        {
            preserveScroll: true,
        }
    )
}

const removeEmployee = (item: PayrollRunItem) => {
    if (!confirm(
        `Remove ${item.employee.first_name} ${item.employee.last_name} from this payroll run?`
    )) {
        return
    }

    router.delete(
        route(
            'payroll.runs.employees.destroy',
            {
                payrollRun: props.run.id,
                payrollRunItem: item.id,
            }
        ),
        {
            preserveScroll: true,
        }
    )
}

const formatMoney = (amount: string | number) => {
    return new Intl.NumberFormat('en-ZM', {
        style: 'currency',
        currency: props.company.currency || 'ZMW',
        minimumFractionDigits: 2,
    }).format(Number(amount || 0))
}

const formatDate = (value: string | null) => {
    if (!value) return '—'

    const datePart = value.substring(0, 10)
    const [year, month, day] = datePart.split('-').map(Number)

    if (!year || !month || !day) return '—'

    return new Date(year, month - 1, day).toLocaleDateString('en-ZM', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

const formatDateTime = (value: string | null) => {
    if (!value) return '—'

    return new Date(value).toLocaleString('en-ZM', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}


const statusClass = (status: string) => {
    const classes: Record<string, string> = {
        draft: 'bg-slate-100 text-slate-700',
        processing: 'bg-blue-100 text-blue-700',
        calculated: 'bg-indigo-100 text-indigo-700',
        approved: 'bg-emerald-100 text-emerald-700',
        paid: 'bg-green-100 text-green-700',
        cancelled: 'bg-red-100 text-red-700',
    }

    return classes[status] || 'bg-slate-100 text-slate-700'
}

const statusLabel = (status: string) => {
    return status.charAt(0).toUpperCase() + status.slice(1)
}


</script>

<template>
    <Head :title="`Payroll Run ${run.run_number || run.id}`" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="mb-2">
                        <Link
                            :href="route('payroll.runs.index')"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                        >
                            ← Back to Payroll Runs
                        </Link>
                    </div>

                    <h1 class="text-2xl font-bold text-slate-900">
                        {{ run.run_number || `Payroll Run #${run.id}` }}
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ run.payroll_period?.name || 'Payroll Period' }}
                    </p>
                </div>

                <span
                    class="inline-flex w-fit items-center rounded-full px-3 py-1 text-sm font-medium"
                    :class="statusClass(run.status)"
                >
                    {{ statusLabel(run.status) }}
                </span>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Employees</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">
                        {{ run.employee_count }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Gross Pay</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">
                        {{ formatMoney(run.gross_pay) }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Total Deductions</p>
                    <p class="mt-2 text-2xl font-bold text-red-600">
                        {{ formatMoney(run.total_deductions) }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Net Pay</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-700">
                        {{ formatMoney(run.net_pay) }}
                    </p>
                </div>
            </div>

            <!-- Payroll Period -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">Payroll Period</h2>
                </div>

                <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="text-sm text-slate-500">Period Name</p>
                        <p class="mt-1 font-medium text-slate-900">
                            {{ run.payroll_period?.name || '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Start Date</p>
                        <p class="mt-1 font-medium text-slate-900">
                            {{ formatDate(run.payroll_period?.start_date) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">End Date</p>
                        <p class="mt-1 font-medium text-slate-900">
                            {{ formatDate(run.payroll_period?.end_date) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Pay Date</p>
                        <p class="mt-1 font-medium text-slate-900">
                            {{ formatDate(run.payroll_period?.pay_date) }}
                        </p>
                    </div>
                </div>
            </div>


                        <!-- Attendance & Payroll Rules -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-5 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                Attendance & Payroll Rules
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Choose whether attendance data should affect payroll calculations.
                            </p>
                        </div>

                        <button
                            v-if="run.status === 'draft'"
                            type="button"
                            @click="savePayrollOptions"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition"
                        >
                            Save Options
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Master switch -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">
                                Apply attendance rules
                            </h3>

                            <p class="text-sm text-gray-500">
                                Enable attendance-based payroll adjustments for this run.
                            </p>
                        </div>

                        <button
                            type="button"
                            :disabled="run.status !== 'draft'"
                            @click="applyAttendanceRules = !applyAttendanceRules"
                            :class="[
                                'relative inline-flex h-6 w-11 items-center rounded-full transition',
                                applyAttendanceRules
                                    ? 'bg-indigo-600'
                                    : 'bg-gray-300',
                                run.status !== 'draft'
                                    ? 'cursor-not-allowed opacity-50'
                                    : 'cursor-pointer'
                            ]"
                        >
                            <span
                                :class="[
                                    'inline-block h-4 w-4 transform rounded-full bg-white transition',
                                    applyAttendanceRules
                                        ? 'translate-x-6'
                                        : 'translate-x-1'
                                ]"
                            />
                        </button>
                    </div>

                    <!-- Individual rules -->
                    <div
                        :class="[
                            'border-t border-gray-200 pt-5 space-y-4',
                            !applyAttendanceRules ? 'opacity-50' : ''
                        ]"
                    >
                        <!-- Unpaid absence -->
                        <label class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-medium text-gray-800">
                                    Deduct unpaid absence
                                </span>

                                <p class="text-xs text-gray-500">
                                    Deduct pay for scheduled working days with no attendance or paid leave.
                                </p>
                            </div>

                            <input
                                v-model="deductUnpaidAbsence"
                                type="checkbox"
                                :disabled="!applyAttendanceRules || run.status !== 'draft'"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                        </label>

                        <!-- Unpaid leave -->
                        <label class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-medium text-gray-800">
                                    Deduct unpaid leave
                                </span>

                                <p class="text-xs text-gray-500">
                                    Deduct pay for approved unpaid leave days.
                                </p>
                            </div>

                            <input
                                v-model="deductUnpaidLeave"
                                type="checkbox"
                                :disabled="!applyAttendanceRules || run.status !== 'draft'"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                        </label>

                        <!-- Late -->
                        <label class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-medium text-gray-800">
                                    Deduct late time
                                </span>

                                <p class="text-xs text-gray-500">
                                    Deduct pay based on recorded late minutes.
                                </p>
                            </div>

                            <input
                                v-model="deductLateTime"
                                type="checkbox"
                                :disabled="!applyAttendanceRules || run.status !== 'draft'"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                        </label>

                        <!-- Early departure -->
                        <label class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-medium text-gray-800">
                                    Deduct early departure
                                </span>

                                <p class="text-xs text-gray-500">
                                    Deduct pay based on recorded early departure minutes.
                                </p>
                            </div>

                            <input
                                v-model="deductEarlyDeparture"
                                type="checkbox"
                                :disabled="!applyAttendanceRules || run.status !== 'draft'"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                        </label>

                        <!-- Overtime -->
                        <label class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-medium text-gray-800">
                                    Pay overtime
                                </span>

                                <p class="text-xs text-gray-500">
                                    Include approved overtime hours in payroll calculations.
                                </p>
                            </div>

                            <input
                                v-model="payOvertime"
                                type="checkbox"
                                :disabled="!applyAttendanceRules || run.status !== 'draft'"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                        </label>
                    </div>

                    <!-- Informational message -->
                    <div
                        v-if="!applyAttendanceRules"
                        class="rounded-lg bg-gray-50 border border-gray-200 p-4"
                    >
                        <p class="text-sm text-gray-600">
                            Attendance will be recorded and displayed for this payroll run,
                            but it will not change employee pay.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Payroll Employees -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-900">Payroll Employees</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ run.items?.length || 0 }} employee(s) in this payroll run
                        </p>
                    </div>

                    <button
                        v-if="run.status === 'draft'"
                        type="button"
                        @click="addEmployees"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Add Employees
                    </button>
                     <button
                        v-if="run.status === 'draft' && run.items?.length"
                        type="button"
                        @click="calculatePayroll"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                    >
                        Calculate Payroll
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Employee
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Basic Salary
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Allowances
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Components
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Gross Pay
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Deductions
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Net Pay
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="item in run.items || []" :key="item.id">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <p class="font-medium text-slate-900">
                                        {{ item.employee.first_name }}
                                        {{ item.employee.middle_name || '' }}
                                        {{ item.employee.last_name }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ item.employee.employee_number }}
                                    </p>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                    {{ formatMoney(item.basic_salary) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                    {{ formatMoney(item.total_allowances) }}
                                </td>

                               <td class="px-6 py-4">
                                    <div
                                        v-if="item.components?.length"
                                        class="space-y-1"
                                    >
                                        <div
                                            v-for="component in item.components"
                                            :key="component.id"
                                            class="flex items-center gap-2 text-sm"
                                        >
                                            <span
                                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                                :class="
                                                    component.type === 'allowance'
                                                        ? 'bg-emerald-100 text-emerald-700'
                                                        : 'bg-red-100 text-red-700'
                                                "
                                            >
                                                {{ component.type === 'allowance' ? 'Allowance' : 'Deduction' }}
                                            </span>

                                            <span class="text-slate-700">
                                                {{ component.name }}
                                            </span>

                                            <span class="font-medium text-slate-900">
                                                {{ formatMoney(component.calculated_amount) }}
                                            </span>
                                        </div>
                                    </div>

                                    <span
                                        v-else
                                        class="text-sm text-slate-400"
                                    >
                                        No components
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                    {{ formatMoney(item.gross_pay) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-red-600">
                                    {{ formatMoney(item.total_deductions) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-emerald-700">
                                    {{ formatMoney(item.net_pay) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <button
                                        v-if="run.status === 'draft'"
                                        type="button"
                                        class="font-medium text-red-600 hover:text-red-800"
                                        @click="removeEmployee(item)"
                                    >
                                        Remove
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!run.items || run.items.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center">
                                    <p class="font-medium text-slate-700">
                                        No employees added yet
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Click Add Employees to populate this payroll run.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Run Information -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">Run Information</h2>
                </div>

                <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <p class="text-sm text-slate-500">Created By</p>
                        <p class="mt-1 font-medium text-slate-900">
                            {{ run.creator?.name || '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Created At</p>
                        <p class="mt-1 font-medium text-slate-900">
                            {{ formatDateTime(run.created_at) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Approved By</p>
                        <p class="mt-1 font-medium text-slate-900">
                            {{ run.approver?.name || 'Not yet approved' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Approved At</p>
                        <p class="mt-1 font-medium text-slate-900">
                            {{ formatDateTime(run.approved_at) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Paid At</p>
                        <p class="mt-1 font-medium text-slate-900">
                            {{ formatDateTime(run.paid_at) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Frequency</p>
                        <p class="mt-1 font-medium capitalize text-slate-900">
                            {{ run.payroll_period?.frequency || '—' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">Notes</h2>
                </div>

                <div class="p-6">
                    <p v-if="run.notes" class="whitespace-pre-line text-sm text-slate-700">
                        {{ run.notes }}
                    </p>

                    <p v-else class="text-sm text-slate-400">
                        No notes have been added to this payroll run.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
