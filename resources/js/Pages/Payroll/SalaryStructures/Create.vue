
<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

interface Company {
    id: number
    name: string
}

interface Employee {
    id: number
    employee_number: string
    first_name: string
    middle_name?: string | null
    last_name: string
    basic_salary?: string | number | null
    salary_currency?: string | null
    pay_frequency?: 'weekly' | 'biweekly' | 'monthly' | null
}

const props = defineProps<{
    company: Company
    employees: Employee[]
}>()

const form = useForm({
    employee_id: '',
    basic_salary: '',
    currency: 'ZMW',
    pay_frequency: 'monthly',
    effective_from: new Date().toISOString().substring(0, 10),
    effective_to: '',
    status: 'active',
    notes: '',
})

const employeeName = (employee: Employee) => {
    return [
        employee.first_name,
        employee.middle_name,
        employee.last_name,
    ].filter(Boolean).join(' ')
}

const updateEmployeeDefaults = () => {
    const employee = props.employees.find(
        item => item.id === Number(form.employee_id)
    )

    if (!employee) return

    form.basic_salary = employee.basic_salary != null
        ? String(employee.basic_salary)
        : ''

    form.currency = employee.salary_currency || 'ZMW'
    form.pay_frequency = employee.pay_frequency || 'monthly'
}

const submit = () => {
    form.post(route('payroll.salary-structures.store'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Create Salary Structure" />

    <AppLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <div>
                <Link
                    :href="route('payroll.salary-structures.index')"
                    class="text-sm font-medium text-emerald-600 hover:text-emerald-800"
                >
                    ← Back to Salary Structures
                </Link>

                <h1 class="mt-3 text-2xl font-semibold text-gray-900">
                    Create Salary Structure
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Set up an employee's basic salary and effective dates
                    for {{ company.name }}.
                </p>
            </div>

            <form
                @submit.prevent="submit"
                class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <!-- Employee -->
                <div>
                    <label
                        for="employee_id"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Employee <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="employee_id"
                        v-model="form.employee_id"
                        @change="updateEmployeeDefaults"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        :class="{ 'border-red-500': form.errors.employee_id }"
                        required
                    >
                        <option value="" disabled>Select an employee</option>

                        <option
                            v-for="employee in employees"
                            :key="employee.id"
                            :value="String(employee.id)"
                        >
                            {{ employee.employee_number }} —
                            {{ employeeName(employee) }}
                        </option>
                    </select>

                    <p
                        v-if="form.errors.employee_id"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.employee_id }}
                    </p>

                    <p
                        v-if="employees.length === 0"
                        class="mt-2 text-sm text-amber-600"
                    >
                        No active employees are available in this company.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <!-- Basic Salary -->
                    <div>
                        <label
                            for="basic_salary"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Basic Salary <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="basic_salary"
                            v-model="form.basic_salary"
                            type="number"
                            min="0"
                            step="0.01"
                            required
                            placeholder="e.g. 8000.00"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                            :class="{ 'border-red-500': form.errors.basic_salary }"
                        />

                        <p
                            v-if="form.errors.basic_salary"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.basic_salary }}
                        </p>
                    </div>

                    <!-- Currency -->
                    <div>
                        <label
                            for="currency"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Currency <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="currency"
                            v-model="form.currency"
                            required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option value="ZMW">ZMW — Zambian Kwacha</option>
                            <option value="USD">USD — US Dollar</option>
                            <option value="ZAR">ZAR — South African Rand</option>
                        </select>

                        <p
                            v-if="form.errors.currency"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.currency }}
                        </p>
                    </div>

                    <!-- Pay Frequency -->
                    <div>
                        <label
                            for="pay_frequency"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Pay Frequency <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="pay_frequency"
                            v-model="form.pay_frequency"
                            required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option value="weekly">Weekly</option>
                            <option value="biweekly">Biweekly</option>
                            <option value="monthly">Monthly</option>
                        </select>

                        <p
                            v-if="form.errors.pay_frequency"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.pay_frequency }}
                        </p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label
                            for="status"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Status <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="status"
                            v-model="form.status"
                            required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>

                        <p
                            v-if="form.errors.status"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.status }}
                        </p>
                    </div>

                    <!-- Effective From -->
                    <div>
                        <label
                            for="effective_from"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Effective From <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="effective_from"
                            v-model="form.effective_from"
                            type="date"
                            required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        />

                        <p
                            v-if="form.errors.effective_from"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.effective_from }}
                        </p>
                    </div>

                    <!-- Effective To -->
                    <div>
                        <label
                            for="effective_to"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Effective To
                        </label>

                        <input
                            id="effective_to"
                            v-model="form.effective_to"
                            type="date"
                            :min="form.effective_from"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        />

                        <p
                            v-if="form.errors.effective_to"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.effective_to }}
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Leave blank if this salary structure has no end date.
                        </p>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label
                        for="notes"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Notes
                    </label>

                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        placeholder="Optional salary structure notes..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                    />

                    <p
                        v-if="form.errors.notes"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.notes }}
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-5">
                    <Link
                        :href="route('payroll.salary-structures.index')"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing || employees.length === 0"
                        class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Salary Structure' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
