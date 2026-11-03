<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
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
    basic_salary: string | number
    currency: string
    pay_frequency: 'weekly' | 'biweekly' | 'monthly'
    effective_from: string
    effective_to?: string | null
    status: 'active' | 'inactive'
    employee: Employee
}

interface PaginatedSalaryStructures {
    data: SalaryStructure[]
    current_page: number
    last_page: number
    total: number
}

const props = defineProps<{
    company: Company
    salaryStructures: PaginatedSalaryStructures
}>()

const employeeName = (employee: Employee) => {
    return [
        employee.first_name,
        employee.middle_name,
        employee.last_name,
    ]
        .filter(Boolean)
        .join(' ')
}

const formatDate = (value?: string | null) => {
    if (!value) {
        return '—'
    }

    const datePart = value.substring(0, 10)
    const [year, month, day] = datePart.split('-').map(Number)

    if (!year || !month || !day) {
        return '—'
    }

    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(year, month - 1, day))
}

const formatSalary = (salary: string | number, currency: string) => {
    return `${currency} ${Number(salary).toLocaleString('en-ZM', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`
}

const payFrequencyLabel = (frequency: SalaryStructure['pay_frequency']) => {
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

const statusClass = (status: SalaryStructure['status']) => {
    return status === 'active'
        ? 'bg-emerald-100 text-emerald-800'
        : 'bg-gray-100 text-gray-700'
}

const deactivate = (salaryStructure: SalaryStructure) => {
    if (salaryStructure.status !== 'active') {
        return
    }

    router.post(
        route(
            'payroll.salary-structures.deactivate',
            salaryStructure.id
        ),
        {},
        {
            preserveScroll: true,
        }
    )
}
</script>

<template>
    <Head title="Salary Structures" />

    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        Salary Structures
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Manage employee salary structures for
                        {{ company.name }}.
                    </p>
                </div>

                <Link
                    :href="route('payroll.salary-structures.create')"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                >
                    Add Salary Structure
                </Link>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Employee
                                </th>

                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Basic Salary
                                </th>

                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Frequency
                                </th>

                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Effective From
                                </th>

                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Effective To
                                </th>

                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Status
                                </th>

                                <th
                                    class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr
                                v-for="salaryStructure in salaryStructures.data"
                                :key="salaryStructure.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="font-medium text-gray-900">
                                        {{ employeeName(salaryStructure.employee) }}
                                    </div>

                                    <div class="text-sm text-gray-500">
                                        {{ salaryStructure.employee.employee_number }}
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                    {{ formatSalary(
                                        salaryStructure.basic_salary,
                                        salaryStructure.currency
                                    ) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                    {{ payFrequencyLabel(salaryStructure.pay_frequency) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                    {{ formatDate(salaryStructure.effective_from) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                    {{ formatDate(salaryStructure.effective_to) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="statusClass(salaryStructure.status)"
                                    >
                                        {{ salaryStructure.status === 'active'
                                            ? 'Active'
                                            : 'Inactive' }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <div class="flex justify-end gap-4">
                                        <Link
                                            :href="
                                                route(
                                                    'payroll.salary-structures.edit',
                                                    salaryStructure.id
                                                )
                                            "
                                            class="font-medium text-emerald-600 hover:text-emerald-800"
                                        >
                                            Edit
                                        </Link>

                                        <button
                                            v-if="salaryStructure.status === 'active'"
                                            type="button"
                                            @click="deactivate(salaryStructure)"
                                            class="font-medium text-red-600 hover:text-red-800"
                                        >
                                            Deactivate
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="salaryStructures.data.length === 0">
                                <td
                                    colspan="7"
                                    class="px-6 py-12 text-center text-sm text-gray-500"
                                >
                                    No salary structures have been created yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="salaryStructures.last_page > 1"
                    class="flex items-center justify-between border-t border-gray-200 px-6 py-4"
                >
                    <p class="text-sm text-gray-500">
                        Page {{ salaryStructures.current_page }}
                        of {{ salaryStructures.last_page }}
                    </p>

                    <div class="flex gap-2">
                        <Link
                            v-if="salaryStructures.current_page > 1"
                            :href="
                                route(
                                    'payroll.salary-structures.index',
                                    { page: salaryStructures.current_page - 1 }
                                )
                            "
                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                            Previous
                        </Link>

                        <Link
                            v-if="salaryStructures.current_page < salaryStructures.last_page"
                            :href="
                                route(
                                    'payroll.salary-structures.index',
                                    { page: salaryStructures.current_page + 1 }
                                )
                            "
                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                            Next
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>