<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Department {
    id: number
    name: string
}

interface Position {
    id: number
    name: string
}

interface Supervisor {
    id: number
    first_name?: string
    last_name?: string
    employee_number?: string
}

interface User {
    id: number
    name: string
    email: string
}

interface Employee {
    id: number
    employee_number: string

    first_name: string
    middle_name?: string | null
    last_name: string

    email?: string | null
    phone?: string | null

    gender?: string | null
    date_of_birth?: string | null
    national_id?: string | null

    address?: string | null
    city?: string | null
    country?: string | null

    employment_type?: string | null
    status?: string | null
    hire_date?: string | null
    contract_start_date?: string | null
    contract_end_date?: string | null

    salary?: number | string | null
    salary_frequency?: string | null

    bank_name?: string | null
    bank_account_number?: string | null
    bank_branch?: string | null

    emergency_contact_name?: string | null
    emergency_contact_phone?: string | null
    emergency_contact_relationship?: string | null

    department?: Department | null
    position?: Position | null
    supervisor?: Supervisor | null
    user?: User | null
}

const props = defineProps<{
    employee: Employee
}>()

const employee = computed(() => props.employee)

const fullName = computed(() => {
    return [
        employee.value.first_name,
        employee.value.middle_name,
        employee.value.last_name,
    ]
        .filter(Boolean)
        .join(' ')
})

const initials = computed(() => {
    const first = employee.value.first_name?.charAt(0) ?? ''
    const last = employee.value.last_name?.charAt(0) ?? ''

    return `${first}${last}`.toUpperCase()
})

const statusLabel = computed(() => {
    return formatLabel(employee.value.status)
})

const employmentTypeLabel = computed(() => {
    return formatLabel(employee.value.employment_type)
})

function formatLabel(value?: string | null) {
    if (!value) {
        return 'Not specified'
    }

    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, char => char.toUpperCase())
}

function formatDate(value?: string | null) {
    if (!value) {
        return 'Not specified'
    }

    return new Date(value).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

function formatCurrency(value?: number | string | null) {
    if (value === null || value === undefined || value === '') {
        return 'Not specified'
    }

    return new Intl.NumberFormat('en-ZM', {
        style: 'currency',
        currency: 'ZMW',
        minimumFractionDigits: 2,
    }).format(Number(value))
}

function statusClass(status?: string | null) {
    switch (status) {
        case 'active':
            return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'

        case 'on_leave':
            return 'bg-amber-50 text-amber-700 ring-amber-600/20'

        case 'suspended':
            return 'bg-orange-50 text-orange-700 ring-orange-600/20'

        case 'separated':
            return 'bg-red-50 text-red-700 ring-red-600/20'

        default:
            return 'bg-gray-50 text-gray-700 ring-gray-600/20'
    }
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div class="flex items-center gap-4">

                <!-- Avatar -->
                <div
                    class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-xl font-bold text-blue-700"
                >
                    {{ initials }}
                </div>

                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900">
                            {{ fullName }}
                        </h1>

                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                            :class="statusClass(employee.status)"
                        >
                            {{ statusLabel }}
                        </span>
                    </div>

                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500">
                        <span>
                            Employee #{{ employee.employee_number }}
                        </span>

                        <span v-if="employee.position">
                            {{ employee.position.name }}
                        </span>

                        <span v-if="employee.department">
                            {{ employee.department.name }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-wrap gap-2">

                <Link
                    href="/employees"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Back
                </Link>

                <Link
                    v-if="employee.status !== 'separated'"
                    :href="`/employees/${employee.id}/separation/create`"
                    class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                >
                    Start Separation
                </Link>

                <Link
                    :href="`/employees/${employee.id}/edit`"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                >
                    Edit Employee
                </Link>

                <Link
                :href="`/employees/${employee.id}/work-schedules`"
                class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100"
            >
                Work Schedule
            </Link>

            </div>
        </div>


        <!-- Overview Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Employee Number</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">
                    {{ employee.employee_number }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Department</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">
                    {{ employee.department?.name ?? 'Not assigned' }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Position</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">
                    {{ employee.position?.name ?? 'Not assigned' }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Employment Type</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">
                    {{ employmentTypeLabel }}
                </p>
            </div>

        </div>


        <!-- Personal Information -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Personal Information
                </h2>
            </div>

            <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-3">

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Full Name
                    </p>

                    <p class="mt-1 text-sm font-medium text-gray-900">
                        {{ fullName }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Gender
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ formatLabel(employee.gender) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Date of Birth
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ formatDate(employee.date_of_birth) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        National ID
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ employee.national_id ?? 'Not specified' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Email
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ employee.email ?? 'Not specified' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Phone
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ employee.phone ?? 'Not specified' }}
                    </p>
                </div>

                <div class="sm:col-span-2 lg:col-span-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Address
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ employee.address ?? 'Not specified' }}
                    </p>
                </div>

            </div>
        </div>


        <!-- Employment Information -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Employment Information
                </h2>
            </div>

            <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-3">

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Department
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ employee.department?.name ?? 'Not assigned' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Position
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ employee.position?.name ?? 'Not assigned' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Employment Type
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ employmentTypeLabel }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Hire Date
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ formatDate(employee.hire_date) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Contract Start
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ formatDate(employee.contract_start_date) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Contract End
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ formatDate(employee.contract_end_date) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Supervisor
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{
                            employee.supervisor
                                ? `${employee.supervisor.first_name ?? ''} ${employee.supervisor.last_name ?? ''}`.trim()
                                : 'Not assigned'
                        }}
                    </p>
                </div>

            </div>
        </div>


        <!-- Payroll Information -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Payroll Information
                </h2>
            </div>

            <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-3">

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Salary
                    </p>

                    <p class="mt-1 text-lg font-semibold text-gray-900">
                        {{ formatCurrency(employee.salary) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Salary Frequency
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ formatLabel(employee.salary_frequency) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Bank
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ employee.bank_name ?? 'Not specified' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Account Number
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ employee.bank_account_number ?? 'Not specified' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Branch
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ employee.bank_branch ?? 'Not specified' }}
                    </p>
                </div>

            </div>
        </div>


        <!-- Emergency Contact -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Emergency Contact
                </h2>
            </div>

            <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-3">

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Name
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ employee.emergency_contact_name ?? 'Not specified' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Relationship
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ employee.emergency_contact_relationship ?? 'Not specified' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Phone
                    </p>

                    <p class="mt-1 text-sm text-gray-900">
                        {{ employee.emergency_contact_phone ?? 'Not specified' }}
                    </p>
                </div>

            </div>
        </div>


        <!-- Future Modules -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

            <h2 class="text-lg font-semibold text-gray-900">
                Employee Modules
            </h2>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">

                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="font-medium text-gray-900">Payroll</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Salary, payslips and payroll history.
                    </p>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="font-medium text-gray-900">Leave</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Leave balances and requests.
                    </p>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="font-medium text-gray-900">Attendance</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Attendance and work history.
                    </p>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="font-medium text-gray-900">Documents</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Employee documents and generated records.
                    </p>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="font-medium text-gray-900">Loans</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Loans, advances and repayment history.
                    </p>
                </div>

            </div>
        </div>

    </div>
</template>