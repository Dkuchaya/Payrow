
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
}

interface SalaryStructure {
    id: number
    employee_id: number
    basic_salary: string | number
    currency: string
    pay_frequency: 'weekly' | 'biweekly' | 'monthly'
    effective_from: string
    effective_to?: string | null
    status: 'active' | 'inactive'
    notes?: string | null
    employee: Employee
}

const props = defineProps<{
    company: Company
    salaryStructure: SalaryStructure
}>()

const form = useForm({
    basic_salary: String(props.salaryStructure.basic_salary),
    currency: props.salaryStructure.currency,
    pay_frequency: props.salaryStructure.pay_frequency,
    effective_from: props.salaryStructure.effective_from.substring(0, 10),
    effective_to: props.salaryStructure.effective_to
        ? props.salaryStructure.effective_to.substring(0, 10)
        : '',
    status: props.salaryStructure.status,
    notes: props.salaryStructure.notes || '',
})

const employeeName = (employee: Employee) => {
    return [
        employee.first_name,
        employee.middle_name,
        employee.last_name,
    ].filter(Boolean).join(' ')
}

const submit = () => {
    form.put(
        route(
            'payroll.salary-structures.update',
            props.salaryStructure.id
        ),
        {
            preserveScroll: true,
        }
    )
}
</script>

<template>
    <Head title="Edit Salary Structure" />

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
                    Edit Salary Structure
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Update salary information for {{ company.name }}.
                </p>
            </div>

            <form
                @submit.prevent="submit"
                class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <!-- Employee information -->
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Employee
                    </p>

                    <p class="mt-1 font-medium text-gray-900">
                        {{ employeeName(salaryStructure.employee) }}
                    </p>

                    <p class="text-sm text-gray-500">
                        {{ salaryStructure.employee.employee_number }}
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
                        :disabled="form.processing"
                        class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ form.processing ? 'Updating...' : 'Update Salary Structure' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
