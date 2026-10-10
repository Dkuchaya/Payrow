<script setup lang="ts">
import { computed, reactive } from 'vue'
import { router } from '@inertiajs/vue3'

import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Company {
    id: number
    name: string
}

interface RecordedBy {
    id: number
    name: string
    email: string
}

interface Transaction {
    id: number
    company_id: number
    transaction_date: string
    transaction_type: string
    reference: string | null
    description: string
    amount: string | number
    currency: string
    company: Company
    recorded_by: RecordedBy | null
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface PaginatedTransactions {
    data: Transaction[]
    current_page: number
    last_page: number
    total: number
    links: PaginationLink[]
}

const props = defineProps<{
    transactions: PaginatedTransactions
    filters: {
        company_id?: string | number | null
        transaction_type?: string | null
        date_from?: string | null
        date_to?: string | null
        search?: string | null
    }
}>()

const filters = reactive({
    search: props.filters.search ?? '',
    transaction_type: props.filters.transaction_type ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
})

const applyFilters = () => {
    router.get(
        route('accounting.history'),
        {
            search: filters.search || undefined,
            transaction_type: filters.transaction_type || undefined,
            date_from: filters.date_from || undefined,
            date_to: filters.date_to || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}

const clearFilters = () => {
    filters.search = ''
    filters.transaction_type = ''
    filters.date_from = ''
    filters.date_to = ''

    router.get(
        route('accounting.history'),
        {},
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}

const totalAmount = computed(() => {
    return props.transactions.data.reduce(
        (total, transaction) => total + Number(transaction.amount),
        0
    )
})

const formatAmount = (transaction: Transaction) => {
    return new Intl.NumberFormat('en-ZM', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(transaction.amount))
}

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

const transactionTypeLabel = (type: string) => {
    return type
        .replace(/_/g, ' ')
        .replace(/\b\w/g, char => char.toUpperCase())
}

const transactionTypeClass = (type: string) => {
    switch (type) {
        case 'payment':
            return 'bg-green-100 text-green-700'
        case 'refund':
            return 'bg-red-100 text-red-700'
        case 'invoice':
            return 'bg-blue-100 text-blue-700'
        case 'credit':
            return 'bg-purple-100 text-purple-700'
        case 'debit':
            return 'bg-orange-100 text-orange-700'
        default:
            return 'bg-gray-100 text-gray-700'
    }
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Accounting History
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    View financial transactions recorded in PAYROW.
                </p>
            </div>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">
                    Transactions
                </p>

                <p class="mt-2 text-2xl font-bold text-gray-900">
                    {{ transactions.total }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">
                    Current Page Amount
                </p>

                <p class="mt-2 text-2xl font-bold text-green-600">
                    K {{ totalAmount.toLocaleString('en-ZM', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">
                    Showing
                </p>

                <p class="mt-2 text-2xl font-bold text-gray-900">
                    {{ transactions.data.length }}
                </p>

                <p class="mt-1 text-xs text-gray-500">
                    transactions on this page
                </p>
            </div>

        </div>

        <!-- Filters -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">

                <!-- Search -->
                <div class="lg:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Search
                    </label>

                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Reference, description or company..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <!-- Type -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Transaction Type
                    </label>

                    <select
                        v-model="filters.transaction_type"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">All Types</option>
                        <option value="payment">Payment</option>
                        <option value="invoice">Invoice</option>
                        <option value="refund">Refund</option>
                        <option value="credit">Credit</option>
                        <option value="debit">Debit</option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Date From
                    </label>

                    <input
                        v-model="filters.date_from"
                        type="date"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                </div>

                <!-- Date To -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Date To
                    </label>

                    <input
                        v-model="filters.date_to"
                        type="date"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                </div>

            </div>

            <div class="mt-4 flex flex-wrap gap-3">

                <button
                    type="button"
                    @click="applyFilters"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                >
                    Apply Filters
                </button>

                <button
                    type="button"
                    @click="clearFilters"
                    class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                >
                    Clear
                </button>

            </div>
        </div>

        <!-- Transactions -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-200">

                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Date
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Company
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Type
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Description
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Reference
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Amount
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Recorded By
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white">

                        <tr
                            v-for="transaction in transactions.data"
                            :key="transaction.id"
                            class="transition hover:bg-gray-50"
                        >

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                {{ formatDate(transaction.transaction_date) }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                {{ transaction.company.name }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="transactionTypeClass(transaction.transaction_type)"
                                >
                                    {{ transactionTypeLabel(transaction.transaction_type) }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ transaction.description }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                {{ transaction.reference || '—' }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-gray-900">
                                {{ transaction.currency }}
                                {{ formatAmount(transaction) }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                {{ transaction.recorded_by?.name || 'System' }}
                            </td>

                        </tr>

                        <!-- Empty -->
                        <tr v-if="transactions.data.length === 0">
                            <td
                                colspan="7"
                                class="px-6 py-12 text-center"
                            >
                                <div class="text-sm font-medium text-gray-900">
                                    No accounting transactions found.
                                </div>

                                <div class="mt-1 text-sm text-gray-500">
                                    Try changing your filters or record a payment first.
                                </div>
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

            <!-- Pagination -->
            <div
                v-if="transactions.last_page > 1"
                class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-6 py-4"
            >

                <p class="text-sm text-gray-500">
                    Page {{ transactions.current_page }}
                    of {{ transactions.last_page }}
                </p>

                <div class="flex flex-wrap gap-1">

                    <template
                        v-for="(link, index) in transactions.links"
                        :key="index"
                    >
                        <button
                            v-if="link.url"
                            type="button"
                            @click="router.get(link.url, {}, {
                                preserveState: true,
                                preserveScroll: true
                            })"
                            class="rounded-lg px-3 py-2 text-sm transition"
                            :class="link.active
                                ? 'bg-blue-600 text-white'
                                : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                            v-html="link.label"
                        />
                    </template>

                </div>
            </div>

        </div>

    </div>
</template>