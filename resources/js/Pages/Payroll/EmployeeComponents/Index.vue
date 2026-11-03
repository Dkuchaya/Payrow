<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
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
    preferred_name?: string | null
}

interface PayrollComponent {
    id: number
    name: string
    code: string
    type: 'allowance' | 'deduction'
    calculation_type: 'fixed' | 'percentage'
    default_amount: string | number
}

interface Assignment {
    id: number
    amount: string | number
    percentage: string | number | null
    effective_from: string
    effective_to: string | null
    is_active: boolean
    notes: string | null
    payroll_component: PayrollComponent
}

interface PaginatedAssignments {
    data: Assignment[]
    links: Array<{
        url: string | null
        label: string
        active: boolean
    }>
    current_page: number
    last_page: number
    total: number
}

const props = defineProps<{
    company: Company
    employee: Employee
    assignments: PaginatedAssignments
    components: PayrollComponent[]
}>()

const showForm = ref(false)
const editingAssignment = ref<Assignment | null>(null)

const form = useForm({
    payroll_component_id: '',
    amount: 0,
    percentage: null as number | null,
    effective_from: '',
    effective_to: '',
    is_active: true,
    notes: '',
})

const employeeName = computed(() => {
    if (props.employee.preferred_name) {
        return props.employee.preferred_name
    }

    return [
        props.employee.first_name,
        props.employee.middle_name,
        props.employee.last_name,
    ]
        .filter(Boolean)
        .join(' ')
})

const selectedComponent = computed(() => {
    return props.components.find(
        component => component.id === Number(form.payroll_component_id)
    ) ?? null
})

const openCreateForm = () => {
    editingAssignment.value = null

    form.reset()
    form.clearErrors()

    form.payroll_component_id = ''
    form.amount = 0
    form.percentage = null
    form.effective_from = new Date().toISOString().split('T')[0]
    form.effective_to = ''
    form.is_active = true
    form.notes = ''

    showForm.value = true
}

const openEditForm = (assignment: Assignment) => {
    editingAssignment.value = assignment
    form.clearErrors()

    form.payroll_component_id = String(assignment.payroll_component.id)
    form.amount = Number(assignment.amount)
    form.percentage = assignment.percentage !== null
        ? Number(assignment.percentage)
        : null
    form.effective_from = assignment.effective_from
    form.effective_to = assignment.effective_to ?? ''
    form.is_active = assignment.is_active
    form.notes = assignment.notes ?? ''

    showForm.value = true
}

const handleComponentChange = () => {
    if (!selectedComponent.value) {
        return
    }

    if (selectedComponent.value.calculation_type === 'fixed') {
        form.amount = Number(selectedComponent.value.default_amount)
        form.percentage = null
    } else {
        form.amount = 0
        form.percentage = Number(selectedComponent.value.default_amount)
    }
}

const submitForm = () => {
    if (editingAssignment.value) {
        form.put(
            route(
                'payroll.employee-components.update',
                editingAssignment.value.id
            ),
            {
                preserveScroll: true,
                onSuccess: () => {
                    showForm.value = false
                },
            }
        )
    } else {
        form.post(
            route(
                'payroll.employee-components.store',
                props.employee.id
            ),
            {
                preserveScroll: true,
                onSuccess: () => {
                    showForm.value = false
                },
            }
        )
    }
}

const deactivateAssignment = (assignment: Assignment) => {
    if (!confirm(`Deactivate ${assignment.payroll_component.name}?`)) {
        return
    }

    router.post(
        route(
            'payroll.employee-components.deactivate',
            assignment.id
        ),
        {},
        {
            preserveScroll: true,
        }
    )
}

const formatAmount = (amount: string | number) => {
    return Number(amount).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
}

const formatDate = (date: string | null | undefined) => {
    if (!date) return '—';

    const parsed = new Date(date);

    if (Number.isNaN(parsed.getTime())) {
        return '—';
    }

    return parsed.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head :title="`${employeeName} - Payroll Components`" />

    <AppLayout>
        <div class="space-y-6 p-6">

            <!-- Header -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <div class="mb-2">
                        <Link
                            :href="route('employees.index')"
                            class="text-sm text-gray-500 hover:text-gray-700"
                        >
                            ← Back to Employees
                        </Link>
                    </div>

                    <h1 class="text-2xl font-semibold text-gray-900">
                        Payroll Components
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ employeeName }}
                        <span class="mx-1">•</span>
                        {{ employee.employee_number }}
                    </p>
                </div>

                <button
                    type="button"
                    @click="openCreateForm"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    Assign Component
                </button>
            </div>

            <!-- Employee information -->
            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs font-medium uppercase text-gray-500">
                            Employee
                        </p>
                        <p class="mt-1 font-medium text-gray-900">
                            {{ employeeName }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase text-gray-500">
                            Employee Number
                        </p>
                        <p class="mt-1 text-gray-900">
                            {{ employee.employee_number }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase text-gray-500">
                            Company
                        </p>
                        <p class="mt-1 text-gray-900">
                            {{ company.name }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Assignments -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Component
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Type
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Value
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Effective From
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Effective To
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Status
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="assignment in assignments.data"
                                :key="assignment.id"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">
                                        {{ assignment.payroll_component.name }}
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        {{ assignment.payroll_component.code }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-sm capitalize text-gray-700">
                                    {{ assignment.payroll_component.type }}
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <span
                                        v-if="assignment.payroll_component.calculation_type === 'percentage'"
                                    >
                                        {{ formatAmount(assignment.percentage ?? 0) }}%
                                    </span>

                                    <span v-else>
                                        {{ formatAmount(assignment.amount) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ formatDate(assignment.effective_from) }}
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ formatDate(assignment.effective_to) }}
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        :class="assignment.is_active
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-gray-100 text-gray-600'"
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                    >
                                        {{ assignment.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right text-sm">
                                    <button
                                        type="button"
                                        @click="openEditForm(assignment)"
                                        class="font-medium text-indigo-600 hover:text-indigo-800"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        v-if="assignment.is_active"
                                        type="button"
                                        @click="deactivateAssignment(assignment)"
                                        class="ml-3 font-medium text-red-600 hover:text-red-800"
                                    >
                                        Deactivate
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="assignments.data.length === 0">
                                <td
                                    colspan="7"
                                    class="px-6 py-10 text-center text-sm text-gray-500"
                                >
                                    No payroll components assigned to this employee.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4">
                    <p class="text-sm text-gray-500">
                        {{ assignments.total }} assignment(s)
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in assignments.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            :class="[
                                'rounded border px-3 py-1 text-sm',
                                link.active
                                    ? 'border-indigo-600 bg-indigo-600 text-white'
                                    : 'border-gray-300 text-gray-700',
                                !link.url
                                    ? 'pointer-events-none opacity-40'
                                    : '',
                            ]"
                        />
                    </div>
                </div>
            </div>

            <!-- Form modal -->
            <div
                v-if="showForm"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/40 p-4"
            >
                <div class="my-8 w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl">

                    <div class="mb-5 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ editingAssignment
                                ? 'Edit Payroll Component'
                                : 'Assign Payroll Component'
                            }}
                        </h2>

                        <button
                            type="button"
                            @click="showForm = false"
                            class="text-2xl text-gray-400 hover:text-gray-700"
                        >
                            &times;
                        </button>
                    </div>

                    <form
                        @submit.prevent="submitForm"
                        class="space-y-4"
                    >
                        <!-- Component -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Payroll Component
                            </label>

                            <select
                                v-model="form.payroll_component_id"
                                :disabled="!!editingAssignment"
                                @change="handleComponentChange"
                                class="w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-100"
                                required
                            >
                                <option value="">
                                    Select component
                                </option>

                                <option
                                    v-for="component in components"
                                    :key="component.id"
                                    :value="component.id"
                                >
                                    {{ component.name }}
                                    ({{ component.type }})
                                </option>
                            </select>

                            <p
                                v-if="form.errors.payroll_component_id"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.payroll_component_id }}
                            </p>
                        </div>

                        <!-- Value -->
                        <div class="grid gap-4 sm:grid-cols-2">

                            <div v-if="selectedComponent?.calculation_type === 'fixed'">
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Amount
                                </label>

                                <input
                                    v-model.number="form.amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full rounded-lg border-gray-300 text-sm"
                                    required
                                />

                                <p
                                    v-if="form.errors.amount"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.amount }}
                                </p>
                            </div>

                            <div v-if="selectedComponent?.calculation_type === 'percentage'">
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Percentage (%)
                                </label>

                                <input
                                    v-model.number="form.percentage"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    class="w-full rounded-lg border-gray-300 text-sm"
                                    required
                                />

                                <p
                                    v-if="form.errors.percentage"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.percentage }}
                                </p>
                            </div>

                        </div>

                        <!-- Dates -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Effective From
                                </label>

                                <input
                                    v-model="form.effective_from"
                                    type="date"
                                    class="w-full rounded-lg border-gray-300 text-sm"
                                    required
                                />

                                <p
                                    v-if="form.errors.effective_from"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.effective_from }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Effective To
                                </label>

                                <input
                                    v-model="form.effective_to"
                                    type="date"
                                    class="w-full rounded-lg border-gray-300 text-sm"
                                />

                                <p
                                    v-if="form.errors.effective_to"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.effective_to }}
                                </p>
                            </div>
                        </div>

                        <!-- Status -->
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-gray-300"
                            />
                            Active
                        </label>

                        <!-- Notes -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Notes
                            </label>

                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="w-full rounded-lg border-gray-300 text-sm"
                            />
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 border-t pt-4">
                            <button
                                type="button"
                                @click="showForm = false"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Saving...' : 'Save Assignment' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>