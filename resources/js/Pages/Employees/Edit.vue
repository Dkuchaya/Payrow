<script setup lang="ts">
import { computed, reactive } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
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
    department_id?: number | null
    name: string
}

interface Supervisor {
    id: number
    employee_number: string
    first_name: string
    last_name: string
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
    department_id?: number | null
    position_id?: number | null
    supervisor_id?: number | null
    employment_type?: string | null
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
}

const props = defineProps<{
    employee: Employee
    departments: Department[]
    positions: Position[]
    supervisors: Supervisor[]
}>()

const form = useForm({
    first_name: props.employee.first_name ?? '',
    middle_name: props.employee.middle_name ?? '',
    last_name: props.employee.last_name ?? '',

    email: props.employee.email ?? '',
    phone: props.employee.phone ?? '',

    gender: props.employee.gender ?? '',
    date_of_birth: props.employee.date_of_birth ?? '',
    national_id: props.employee.national_id ?? '',

    address: props.employee.address ?? '',
    city: props.employee.city ?? '',
    country: props.employee.country ?? 'Zambia',

    department_id: props.employee.department_id ?? null,
    position_id: props.employee.position_id ?? null,
    supervisor_id: props.employee.supervisor_id ?? null,

    employment_type: props.employee.employment_type ?? '',
    hire_date: props.employee.hire_date ?? '',
    contract_start_date: props.employee.contract_start_date ?? '',
    contract_end_date: props.employee.contract_end_date ?? '',

    salary: props.employee.salary ?? '',
    salary_frequency: props.employee.salary_frequency ?? 'monthly',

    bank_name: props.employee.bank_name ?? '',
    bank_account_number: props.employee.bank_account_number ?? '',
    bank_branch: props.employee.bank_branch ?? '',

    emergency_contact_name:
        props.employee.emergency_contact_name ?? '',

    emergency_contact_phone:
        props.employee.emergency_contact_phone ?? '',

    emergency_contact_relationship:
        props.employee.emergency_contact_relationship ?? '',
})

const filteredPositions = computed(() => {
    if (!form.department_id) {
        return props.positions
    }

    return props.positions.filter(
        position =>
            position.department_id === form.department_id
    )
})

function submit() {
    form.put(`/employees/${props.employee.id}`)
}
</script>

<template>
    <div class="mx-auto max-w-6xl space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">

            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Edit Employee
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    {{ employee.first_name }}
                    {{ employee.last_name }}
                    · {{ employee.employee_number }}
                </p>
            </div>

            <Link
                :href="`/employees/${employee.id}`"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Cancel
            </Link>

        </div>


        <!-- Personal Information -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="font-semibold text-gray-900">
                    Personal Information
                </h2>
            </div>

            <div class="grid gap-5 p-6 md:grid-cols-3">

                <div>
                    <label class="form-label">
                        First Name
                    </label>

                    <input
                        v-model="form.first_name"
                        type="text"
                        class="form-input"
                    />

                    <p
                        v-if="form.errors.first_name"
                        class="form-error"
                    >
                        {{ form.errors.first_name }}
                    </p>
                </div>

                <div>
                    <label class="form-label">
                        Middle Name
                    </label>

                    <input
                        v-model="form.middle_name"
                        type="text"
                        class="form-input"
                    />
                </div>

                <div>
                    <label class="form-label">
                        Last Name
                    </label>

                    <input
                        v-model="form.last_name"
                        type="text"
                        class="form-input"
                    />
                </div>

                <div>
                    <label class="form-label">
                        Gender
                    </label>

                    <select
                        v-model="form.gender"
                        class="form-input"
                    >
                        <option value="">Select gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">
                        Date of Birth
                    </label>

                    <input
                        v-model="form.date_of_birth"
                        type="date"
                        class="form-input"
                    />
                </div>

                <div>
                    <label class="form-label">
                        National ID
                    </label>

                    <input
                        v-model="form.national_id"
                        type="text"
                        class="form-input"
                    />
                </div>

                <div>
                    <label class="form-label">
                        Email
                    </label>

                    <input
                        v-model="form.email"
                        type="email"
                        class="form-input"
                    />
                </div>

                <div>
                    <label class="form-label">
                        Phone
                    </label>

                    <input
                        v-model="form.phone"
                        type="text"
                        class="form-input"
                    />
                </div>

                <div class="md:col-span-3">
                    <label class="form-label">
                        Address
                    </label>

                    <textarea
                        v-model="form.address"
                        rows="2"
                        class="form-input"
                    />
                </div>

                <div>
                    <label class="form-label">
                        City
                    </label>

                    <input
                        v-model="form.city"
                        type="text"
                        class="form-input"
                    />
                </div>

                <div>
                    <label class="form-label">
                        Country
                    </label>

                    <input
                        v-model="form.country"
                        type="text"
                        class="form-input"
                    />
                </div>

            </div>
        </div>


        <!-- Employment -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="font-semibold text-gray-900">
                    Employment Information
                </h2>
            </div>

            <div class="grid gap-5 p-6 md:grid-cols-3">

                <div>
                    <label class="form-label">
                        Department
                    </label>

                    <select
                        v-model="form.department_id"
                        class="form-input"
                    >
                        <option :value="null">
                            Select department
                        </option>

                        <option
                            v-for="department in departments"
                            :key="department.id"
                            :value="department.id"
                        >
                            {{ department.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="form-label">
                        Position
                    </label>

                    <select
                        v-model="form.position_id"
                        class="form-input"
                    >
                        <option :value="null">
                            Select position
                        </option>

                        <option
                            v-for="position in filteredPositions"
                            :key="position.id"
                            :value="position.id"
                        >
                            {{ position.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="form-label">
                        Supervisor
                    </label>

                    <select
                        v-model="form.supervisor_id"
                        class="form-input"
                    >
                        <option :value="null">
                            No supervisor
                        </option>

                        <option
                            v-for="supervisor in supervisors"
                            :key="supervisor.id"
                            :value="supervisor.id"
                        >
                            {{ supervisor.first_name }}
                            {{ supervisor.last_name }}
                            — {{ supervisor.employee_number }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="form-label">
                        Employment Type
                    </label>

                    <select
                        v-model="form.employment_type"
                        class="form-input"
                    >
                        <option value="">Select type</option>
                        <option value="permanent">Permanent</option>
                        <option value="contract">Contract</option>
                        <option value="part_time">Part Time</option>
                        <option value="casual">Casual</option>
                        <option value="intern">Intern</option>
                        <option value="temporary">Temporary</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">
                        Hire Date
                    </label>

                    <input
                        v-model="form.hire_date"
                        type="date"
                        class="form-input"
                    />
                </div>

                <div>
                    <label class="form-label">
                        Contract Start
                    </label>

                    <input
                        v-model="form.contract_start_date"
                        type="date"
                        class="form-input"
                    />
                </div>

                <div>
                    <label class="form-label">
                        Contract End
                    </label>

                    <input
                        v-model="form.contract_end_date"
                        type="date"
                        class="form-input"
                    />
                </div>

            </div>
        </div>


        <!-- Payroll -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="font-semibold text-gray-900">
                    Payroll Information
                </h2>
            </div>

            <div class="grid gap-5 p-6 md:grid-cols-3">

                <div>
                    <label class="form-label">
                        Basic Salary
                    </label>

                    <input
                        v-model="form.salary"
                        type="number"
                        min="0"
                        step="0.01"
                        class="form-input"
                    />
                </div>

                <div>
                    <label class="form-label">
                        Salary Frequency
                    </label>

                    <select
                        v-model="form.salary_frequency"
                        class="form-input"
                    >
                        <option value="monthly">Monthly</option>
                        <option value="weekly">Weekly</option>
                        <option value="daily">Daily</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">
                        Bank Name
                    </label>

                    <input
                        v-model="form.bank_name"
                        type="text"
                        class="form-input"
                    />
                </div>

                <div>
                    <label class="form-label">
                        Account Number
                    </label>

                    <input
                        v-model="form.bank_account_number"
                        type="text"
                        class="form-input"
                    />
                </div>

                <div>
                    <label class="form-label">
                        Bank Branch
                    </label>

                    <input
                        v-model="form.bank_branch"
                        type="text"
                        class="form-input"
                    />
                </div>

            </div>
        </div>


        <!-- Emergency -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="font-semibold text-gray-900">
                    Emergency Contact
                </h2>
            </div>

            <div class="grid gap-5 p-6 md:grid-cols-3">

                <div>
                    <label class="form-label">
                        Name
                    </label>

                    <input
                        v-model="form.emergency_contact_name"
                        type="text"
                        class="form-input"
                    />
                </div>

                <div>
                    <label class="form-label">
                        Relationship
                    </label>

                    <input
                        v-model="form.emergency_contact_relationship"
                        type="text"
                        class="form-input"
                    />
                </div>

                <div>
                    <label class="form-label">
                        Phone
                    </label>

                    <input
                        v-model="form.emergency_contact_phone"
                        type="text"
                        class="form-input"
                    />
                </div>

            </div>
        </div>


        <!-- Actions -->
        <div class="flex justify-end gap-3">

            <Link
                :href="`/employees/${employee.id}`"
                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Cancel
            </Link>

            <button
                type="button"
                :disabled="form.processing"
                @click="submit"
                class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>

        </div>

    </div>
</template>

<style scoped>
.form-label {
    @apply mb-1.5 block text-sm font-medium text-gray-700;
}

.form-input {
    @apply w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20;
}

.form-error {
    @apply mt-1 text-xs text-red-600;
}
</style>