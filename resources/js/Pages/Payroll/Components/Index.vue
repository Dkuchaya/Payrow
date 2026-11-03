<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

interface Company {
    id: number
    name: string
}

interface PayrollComponent {
    id: number
    name: string
    code: string
    type: 'allowance' | 'deduction'
    calculation_type: 'fixed' | 'percentage'
    default_amount: string | number
    is_taxable: boolean
    is_recurring: boolean
    is_active: boolean
    description?: string | null
}

interface PaginatedComponents {
    data: PayrollComponent[]
    links: Array<{ url: string | null; label: string; active: boolean }>
    current_page: number
    last_page: number
    total: number
}

const props = defineProps<{
    company: Company
    components: PaginatedComponents
    filters: {
        type?: string
        search?: string
    }
}>()

const showForm = ref(false)
const editingComponent = ref<PayrollComponent | null>(null)

const search = ref(props.filters.search ?? '')
const selectedType = ref(props.filters.type ?? '')

const form = useForm({
    name: '',
    code: '',
    type: 'allowance' as 'allowance' | 'deduction',
    calculation_type: 'fixed' as 'fixed' | 'percentage',
    default_amount: 0,
    is_taxable: false,
    is_recurring: true,
    is_active: true,
    description: '',
})

const pageTitle = computed(() => 'Payroll Components')

const applyFilters = () => {
    router.get(
        route('payroll.components.index'),
        {
            search: search.value || undefined,
            type: selectedType.value || undefined,
        },
        { preserveState: true, replace: true }
    )
}

const openCreateForm = () => {
    editingComponent.value = null

    form.reset()
    form.clearErrors()

    form.name = ''
    form.code = ''
    form.type = 'allowance'
    form.calculation_type = 'fixed'
    form.default_amount = 0
    form.is_taxable = false
    form.is_recurring = true
    form.is_active = true
    form.description = ''

    showForm.value = true
}

const openEditForm = (component: PayrollComponent) => {
    editingComponent.value = component
    form.clearErrors()

    form.name = component.name
    form.code = component.code
    form.type = component.type
    form.calculation_type = component.calculation_type
    form.default_amount = Number(component.default_amount)
    form.is_taxable = component.is_taxable
    form.is_recurring = component.is_recurring
    form.is_active = component.is_active
    form.description = component.description ?? ''

    showForm.value = true
}

const submitForm = () => {
    if (editingComponent.value) {
        form.put(
            route('payroll.components.update', editingComponent.value.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    showForm.value = false
                },
            }
        )
    } else {
        form.post(route('payroll.components.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showForm.value = false
            },
        })
    }
}

const deactivateComponent = (component: PayrollComponent) => {
    if (!confirm(`Deactivate ${component.name}?`)) {
        return
    }

    router.post(
        route('payroll.components.deactivate', component.id),
        {},
        { preserveScroll: true }
    )
}

const formatAmount = (amount: string | number) => {
    return Number(amount).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout>
        <div class="space-y-6 p-6">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        Payroll Components
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Manage allowances and deductions for {{ company.name }}.
                    </p>
                </div>

                <button
                    type="button"
                    @click="openCreateForm"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    Add Component
                </button>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="grid gap-3 sm:grid-cols-3">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search name or code..."
                        class="rounded-lg border-gray-300 text-sm"
                        @keyup.enter="applyFilters"
                    />

                    <select
                        v-model="selectedType"
                        class="rounded-lg border-gray-300 text-sm"
                        @change="applyFilters"
                    >
                        <option value="">All components</option>
                        <option value="allowance">Allowances</option>
                        <option value="deduction">Deductions</option>
                    </select>

                    <button
                        type="button"
                        @click="applyFilters"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Search
                    </button>
                </div>
            </div>

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
                                    Calculation
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Default
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Taxable
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
                                v-for="component in components.data"
                                :key="component.id"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">
                                        {{ component.name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ component.code }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-sm capitalize text-gray-700">
                                    {{ component.type }}
                                </td>

                                <td class="px-6 py-4 text-sm capitalize text-gray-700">
                                    {{ component.calculation_type }}
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ component.calculation_type === 'percentage'
                                        ? `${formatAmount(component.default_amount)}%`
                                        : formatAmount(component.default_amount)
                                    }}
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ component.is_taxable ? 'Yes' : 'No' }}
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        :class="component.is_active
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-gray-100 text-gray-600'"
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                    >
                                        {{ component.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right text-sm">
                                    <button
                                        type="button"
                                        @click="openEditForm(component)"
                                        class="font-medium text-indigo-600 hover:text-indigo-800"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        v-if="component.is_active"
                                        type="button"
                                        @click="deactivateComponent(component)"
                                        class="ml-3 font-medium text-red-600 hover:text-red-800"
                                    >
                                        Deactivate
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="components.data.length === 0">
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
                                    No payroll components found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4">
                    <p class="text-sm text-gray-500">
                        {{ components.total }} component(s)
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in components.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            :class="[
                                'rounded border px-3 py-1 text-sm',
                                link.active
                                    ? 'border-indigo-600 bg-indigo-600 text-white'
                                    : 'border-gray-300 text-gray-700',
                                !link.url ? 'pointer-events-none opacity-40' : '',
                            ]"
                        />
                    </div>
                </div>
            </div>

            <div
                v-if="showForm"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/40 p-4"
            >
                <div class="my-8 w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl">
                    <div class="mb-5 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ editingComponent ? 'Edit Component' : 'Add Payroll Component' }}
                        </h2>

                        <button
                            type="button"
                            @click="showForm = false"
                            class="text-2xl text-gray-400 hover:text-gray-700"
                        >
                            &times;
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Component Name
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 text-sm"
                                    required
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Component Code
                                </label>
                                <input
                                    v-model="form.code"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 text-sm"
                                    required
                                />
                                <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.code }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Type
                                </label>
                                <select
                                    v-model="form.type"
                                    class="w-full rounded-lg border-gray-300 text-sm"
                                >
                                    <option value="allowance">Allowance</option>
                                    <option value="deduction">Deduction</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Calculation Type
                                </label>
                                <select
                                    v-model="form.calculation_type"
                                    class="w-full rounded-lg border-gray-300 text-sm"
                                >
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Default {{ form.calculation_type === 'percentage' ? 'Percentage (%)' : 'Amount' }}
                                </label>
                                <input
                                    v-model.number="form.default_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full rounded-lg border-gray-300 text-sm"
                                    required
                                />
                                <p v-if="form.errors.default_amount" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.default_amount }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input v-model="form.is_taxable" type="checkbox" class="rounded border-gray-300" />
                                Taxable
                            </label>

                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input v-model="form.is_recurring" type="checkbox" class="rounded border-gray-300" />
                                Recurring
                            </label>

                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300" />
                                Active
                            </label>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Description
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-lg border-gray-300 text-sm"
                            />
                        </div>

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
                                {{ form.processing ? 'Saving...' : 'Save Component' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>