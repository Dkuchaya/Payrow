<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

const props = defineProps<{
    departments: Array<{
        id: number
        name: string
    }>

    positions: Array<{
        id: number
        department_id: number | null
        name: string
    }>

    supervisors: Array<{
        id: number
        employee_number: string
        first_name: string
        last_name: string
    }>

    usage: {
        employee_count: number
        employee_limit: number | null
        remaining_seats: number | null
        unlimited: boolean
        limit_reached: boolean
        plan: {
            name: string
        } | null
    }
}>()

const form = useForm({
    employee_number: '',
    national_id: '',

    first_name: '',
    middle_name: '',
    last_name: '',
    preferred_name: '',
    gender: '',
    date_of_birth: '',
    marital_status: '',
    nationality: 'Zambian',

    email: '',
    phone: '',
    alternative_phone: '',
    address: '',
    city: '',
    country: 'Zambia',

    hire_date: '',
    probation_end_date: '',
    confirmation_date: '',
    contract_start_date: '',
    contract_end_date: '',

    employment_type: 'permanent',
    employment_status: 'active',

    department_id: '',
    position_id: '',
    supervisor_id: '',

    basic_salary: '',
    salary_currency: 'ZMW',
    pay_frequency: 'monthly',

    bank_name: '',
    bank_branch: '',
    bank_account_name: '',
    bank_account_number: '',
    bank_branch_code: '',

    tpin: '',
    napsa_number: '',
    nhima_number: '',

    emergency_contact_name: '',
    emergency_contact_relationship: '',
    emergency_contact_phone: '',
    emergency_contact_address: '',

    notes: '',
})

const activeSection = ref('personal')

const filteredPositions = computed(() => {
    if (!form.department_id) {
        return props.positions
    }

    return props.positions.filter(
        position =>
            position.department_id === Number(form.department_id)
    )
})

function submit() {
    form.post(route('employees.store'))
}

function nextSection(section: string) {
    activeSection.value = section
    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    })
}

function fullSupervisorName(supervisor: {
    first_name: string
    last_name: string
}) {
    return `${supervisor.first_name} ${supervisor.last_name}`
}
</script>

<template>
    <div class="mx-auto max-w-6xl space-y-6">

        <!-- Header -->
        <div>
            <Link
                :href="route('employees.index')"
                class="text-sm font-medium text-blue-600 hover:text-blue-800"
            >
                ← Back to Employees
            </Link>

            <h1 class="mt-3 text-2xl font-bold text-gray-900">
                Add Employee
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Create a new employee record for the active company.
            </p>
        </div>

        <!-- Seat information -->
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Employee Seats
                    </p>

                    <p class="mt-1 text-lg font-semibold text-gray-900">
                        {{ usage.employee_count }}

                        <span class="text-sm font-normal text-gray-500">
                            <template v-if="usage.unlimited">
                                / Unlimited
                            </template>

                            <template v-else>
                                / {{ usage.employee_limit }}
                            </template>
                        </span>
                    </p>
                </div>

                <div
                    v-if="usage.plan"
                    class="text-sm text-gray-500"
                >
                    Plan:
                    <span class="font-semibold text-gray-800">
                        {{ usage.plan.name }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Section Navigation -->
        <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="flex min-w-max border-b">
                <button
                    type="button"
                    @click="nextSection('personal')"
                    :class="activeSection === 'personal'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="border-b-2 px-5 py-4 text-sm font-medium"
                >
                    Personal
                </button>

                <button
                    type="button"
                    @click="nextSection('contact')"
                    :class="activeSection === 'contact'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="border-b-2 px-5 py-4 text-sm font-medium"
                >
                    Contact
                </button>

                <button
                    type="button"
                    @click="nextSection('employment')"
                    :class="activeSection === 'employment'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="border-b-2 px-5 py-4 text-sm font-medium"
                >
                    Employment
                </button>

                <button
                    type="button"
                    @click="nextSection('payroll')"
                    :class="activeSection === 'payroll'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="border-b-2 px-5 py-4 text-sm font-medium"
                >
                    Payroll
                </button>

                <button
                    type="button"
                    @click="nextSection('banking')"
                    :class="activeSection === 'banking'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="border-b-2 px-5 py-4 text-sm font-medium"
                >
                    Banking
                </button>

                <button
                    type="button"
                    @click="nextSection('statutory')"
                    :class="activeSection === 'statutory'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="border-b-2 px-5 py-4 text-sm font-medium"
                >
                    Statutory
                </button>

                <button
                    type="button"
                    @click="nextSection('emergency')"
                    :class="activeSection === 'emergency'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="border-b-2 px-5 py-4 text-sm font-medium"
                >
                    Emergency
                </button>
            </div>
        </div>

        <form
            @submit.prevent="submit"
            class="space-y-6"
        >

            <!-- PERSONAL -->
            <div
                v-show="activeSection === 'personal'"
                class="space-y-6"
            >
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Personal Information
                        </h2>

                        <p class="text-sm text-gray-500">
                            Basic identification and personal details.
                        </p>
                    </div>

                    <div class="grid gap-6 md:grid-cols-3">

                        <div>
                            <label class="label">Employee Number *</label>
                            <input
                                v-model="form.employee_number"
                                type="text"
                                class="input"
                                placeholder="EMP001"
                            />
                            <p v-if="form.errors.employee_number" class="error">
                                {{ form.errors.employee_number }}
                            </p>
                        </div>

                        <div>
                            <label class="label">National ID</label>
                            <input
                                v-model="form.national_id"
                                type="text"
                                class="input"
                                placeholder="123456/78/1"
                            />
                        </div>

                        <div>
                            <label class="label">Nationality</label>
                            <input
                                v-model="form.nationality"
                                type="text"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">First Name *</label>
                            <input
                                v-model="form.first_name"
                                type="text"
                                class="input"
                            />
                            <p v-if="form.errors.first_name" class="error">
                                {{ form.errors.first_name }}
                            </p>
                        </div>

                        <div>
                            <label class="label">Middle Name</label>
                            <input
                                v-model="form.middle_name"
                                type="text"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">Last Name *</label>
                            <input
                                v-model="form.last_name"
                                type="text"
                                class="input"
                            />
                            <p v-if="form.errors.last_name" class="error">
                                {{ form.errors.last_name }}
                            </p>
                        </div>

                        <div>
                            <label class="label">Preferred Name</label>
                            <input
                                v-model="form.preferred_name"
                                type="text"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">Gender</label>
                            <select v-model="form.gender" class="input">
                                <option value="">Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="label">Date of Birth</label>
                            <input
                                v-model="form.date_of_birth"
                                type="date"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">Marital Status</label>
                            <select
                                v-model="form.marital_status"
                                class="input"
                            >
                                <option value="">Select status</option>
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="divorced">Divorced</option>
                                <option value="widowed">Widowed</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        type="button"
                        @click="nextSection('contact')"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        Next: Contact →
                    </button>
                </div>
            </div>

            <!-- CONTACT -->
            <div
                v-show="activeSection === 'contact'"
                class="space-y-6"
            >
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h2 class="mb-6 text-lg font-semibold text-gray-900">
                        Contact Information
                    </h2>

                    <div class="grid gap-6 md:grid-cols-2">

                        <div>
                            <label class="label">Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="input"
                            />
                            <p v-if="form.errors.email" class="error">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label class="label">Phone</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="input"
                                placeholder="+260..."
                            />
                        </div>

                        <div>
                            <label class="label">Alternative Phone</label>
                            <input
                                v-model="form.alternative_phone"
                                type="text"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">City</label>
                            <input
                                v-model="form.city"
                                type="text"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">Country</label>
                            <input
                                v-model="form.country"
                                type="text"
                                class="input"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="label">Address</label>
                            <textarea
                                v-model="form.address"
                                rows="3"
                                class="input"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between">
                    <button
                        type="button"
                        @click="nextSection('personal')"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700"
                    >
                        ← Back
                    </button>

                    <button
                        type="button"
                        @click="nextSection('employment')"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white"
                    >
                        Next: Employment →
                    </button>
                </div>
            </div>

            <!-- EMPLOYMENT -->
            <div
                v-show="activeSection === 'employment'"
                class="space-y-6"
            >
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h2 class="mb-6 text-lg font-semibold text-gray-900">
                        Employment Details
                    </h2>

                    <div class="grid gap-6 md:grid-cols-3">

                        <div>
                            <label class="label">Hire Date</label>
                            <input
                                v-model="form.hire_date"
                                type="date"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">Probation End Date</label>
                            <input
                                v-model="form.probation_end_date"
                                type="date"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">Confirmation Date</label>
                            <input
                                v-model="form.confirmation_date"
                                type="date"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">Contract Start</label>
                            <input
                                v-model="form.contract_start_date"
                                type="date"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">Contract End</label>
                            <input
                                v-model="form.contract_end_date"
                                type="date"
                                class="input"
                            />
                            <p v-if="form.errors.contract_end_date" class="error">
                                {{ form.errors.contract_end_date }}
                            </p>
                        </div>

                        <div>
                            <label class="label">Employment Type *</label>
                            <select
                                v-model="form.employment_type"
                                class="input"
                            >
                                <option value="permanent">Permanent</option>
                                <option value="contract">Contract</option>
                                <option value="temporary">Temporary</option>
                                <option value="casual">Casual</option>
                                <option value="intern">Intern</option>
                                <option value="part_time">Part Time</option>
                            </select>
                        </div>

                        <div>
                            <label class="label">Employment Status *</label>
                            <select
                                v-model="form.employment_status"
                                class="input"
                            >
                                <option value="active">Active</option>
                                <option value="on_leave">On Leave</option>
                                <option value="suspended">Suspended</option>
                                <option value="separated">Separated</option>
                            </select>
                        </div>

                        <div>
                            <label class="label">Department</label>
                            <select
                                v-model="form.department_id"
                                class="input"
                            >
                                <option value="">
                                    Select department
                                </option>

                                <option
                                    v-for="department in departments"
                                    :key="department.id"
                                    :value="String(department.id)"
                                >
                                    {{ department.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="label">Position</label>
                            <select
                                v-model="form.position_id"
                                class="input"
                            >
                                <option value="">
                                    Select position
                                </option>

                                <option
                                    v-for="position in filteredPositions"
                                    :key="position.id"
                                    :value="String(position.id)"
                                >
                                    {{ position.name }}
                                </option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="label">Supervisor</label>

                            <select
                                v-model="form.supervisor_id"
                                class="input"
                            >
                                <option value="">
                                    No supervisor
                                </option>

                                <option
                                    v-for="supervisor in supervisors"
                                    :key="supervisor.id"
                                    :value="String(supervisor.id)"
                                >
                                    {{ fullSupervisorName(supervisor) }}
                                    — {{ supervisor.employee_number }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between">
                    <button
                        type="button"
                        @click="nextSection('contact')"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700"
                    >
                        ← Back
                    </button>

                    <button
                        type="button"
                        @click="nextSection('payroll')"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white"
                    >
                        Next: Payroll →
                    </button>
                </div>
            </div>

            <!-- PAYROLL -->
            <div
                v-show="activeSection === 'payroll'"
                class="space-y-6"
            >
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h2 class="mb-6 text-lg font-semibold text-gray-900">
                        Salary & Payroll
                    </h2>

                    <div class="grid gap-6 md:grid-cols-3">

                        <div>
                            <label class="label">Basic Salary *</label>

                            <input
                                v-model="form.basic_salary"
                                type="number"
                                min="0"
                                step="0.01"
                                class="input"
                                placeholder="0.00"
                            />

                            <p v-if="form.errors.basic_salary" class="error">
                                {{ form.errors.basic_salary }}
                            </p>
                        </div>

                        <div>
                            <label class="label">Currency *</label>

                            <select
                                v-model="form.salary_currency"
                                class="input"
                            >
                                <option value="ZMW">ZMW</option>
                                <option value="USD">USD</option>
                                <option value="ZAR">ZAR</option>
                                <option value="GBP">GBP</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>

                        <div>
                            <label class="label">Pay Frequency *</label>

                            <select
                                v-model="form.pay_frequency"
                                class="input"
                            >
                                <option value="monthly">Monthly</option>
                                <option value="weekly">Weekly</option>
                                <option value="bi_weekly">Bi-weekly</option>
                                <option value="daily">Daily</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between">
                    <button
                        type="button"
                        @click="nextSection('employment')"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700"
                    >
                        ← Back
                    </button>

                    <button
                        type="button"
                        @click="nextSection('banking')"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white"
                    >
                        Next: Banking →
                    </button>
                </div>
            </div>

            <!-- BANKING -->
            <div
                v-show="activeSection === 'banking'"
                class="space-y-6"
            >
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h2 class="mb-6 text-lg font-semibold text-gray-900">
                        Banking Information
                    </h2>

                    <div class="grid gap-6 md:grid-cols-2">

                        <div>
                            <label class="label">Bank Name</label>
                            <input
                                v-model="form.bank_name"
                                type="text"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">Bank Branch</label>
                            <input
                                v-model="form.bank_branch"
                                type="text"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">Account Name</label>
                            <input
                                v-model="form.bank_account_name"
                                type="text"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">Account Number</label>
                            <input
                                v-model="form.bank_account_number"
                                type="text"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">Branch Code</label>
                            <input
                                v-model="form.bank_branch_code"
                                type="text"
                                class="input"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex justify-between">
                    <button
                        type="button"
                        @click="nextSection('payroll')"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700"
                    >
                        ← Back
                    </button>

                    <button
                        type="button"
                        @click="nextSection('statutory')"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white"
                    >
                        Next: Statutory →
                    </button>
                </div>
            </div>

            <!-- STATUTORY -->
            <div
                v-show="activeSection === 'statutory'"
                class="space-y-6"
            >
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h2 class="mb-6 text-lg font-semibold text-gray-900">
                        Statutory Information
                    </h2>

                    <div class="grid gap-6 md:grid-cols-3">

                        <div>
                            <label class="label">TPIN</label>
                            <input
                                v-model="form.tpin"
                                type="text"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">NAPSA Number</label>
                            <input
                                v-model="form.napsa_number"
                                type="text"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">NHIMA Number</label>
                            <input
                                v-model="form.nhima_number"
                                type="text"
                                class="input"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex justify-between">
                    <button
                        type="button"
                        @click="nextSection('banking')"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700"
                    >
                        ← Back
                    </button>

                    <button
                        type="button"
                        @click="nextSection('emergency')"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white"
                    >
                        Next: Emergency →
                    </button>
                </div>
            </div>

            <!-- EMERGENCY -->
            <div
                v-show="activeSection === 'emergency'"
                class="space-y-6"
            >
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h2 class="mb-6 text-lg font-semibold text-gray-900">
                        Emergency Contact
                    </h2>

                    <div class="grid gap-6 md:grid-cols-2">

                        <div>
                            <label class="label">Contact Name</label>
                            <input
                                v-model="form.emergency_contact_name"
                                type="text"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">Relationship</label>
                            <input
                                v-model="form.emergency_contact_relationship"
                                type="text"
                                class="input"
                                placeholder="Spouse, Parent, Sibling..."
                            />
                        </div>

                        <div>
                            <label class="label">Phone</label>
                            <input
                                v-model="form.emergency_contact_phone"
                                type="text"
                                class="input"
                            />
                        </div>

                        <div>
                            <label class="label">Address</label>
                            <textarea
                                v-model="form.emergency_contact_address"
                                rows="3"
                                class="input"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">
                        Notes
                    </h2>

                    <textarea
                        v-model="form.notes"
                        rows="5"
                        class="input"
                        placeholder="Additional employee notes..."
                    ></textarea>
                </div>

                <!-- Final Actions -->
                <div class="flex justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <button
                        type="button"
                        @click="nextSection('statutory')"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700"
                    >
                        ← Back
                    </button>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? 'Creating Employee...'
                                : 'Create Employee'
                        }}
                    </button>
                </div>
            </div>

        </form>
    </div>
</template>

<style scoped>
.label {
    @apply block text-sm font-medium text-gray-700;
}

.input {
    @apply mt-1 block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500;
}

.error {
    @apply mt-1 text-sm text-red-600;
}
</style>