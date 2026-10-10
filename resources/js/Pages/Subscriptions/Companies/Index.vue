<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Company {
    id: number
    name: string
    slug: string
}

interface Plan {
    id: number
    name: string
    slug: string
    employee_limit: number | null
}

interface Subscription {
    id: number
    company: Company
    plan: Plan
    status: string
    billing_cycle: string
    amount: string | number
    starts_at: string | null
    ends_at: string | null
    trial_ends_at: string | null
    payment_reference: string | null
    paid_at: string | null
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface PaginatedSubscriptions {
    data: Subscription[]
    current_page: number
    last_page: number
    from: number | null
    to: number | null
    total: number
    links: PaginationLink[]
}

const props = defineProps<{
    subscriptions: PaginatedSubscriptions
}>()

function formatDate(date: string | null): string {
    if (!date) {
        return '—'
    }

    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

function formatAmount(amount: string | number): string {
    return `K${Number(amount).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`
}

function billingLabel(cycle: string): string {
    return {
        monthly: 'Monthly',
        quarterly: 'Quarterly',
        semi_annual: '6 Months',
        annual: 'Annual',
    }[cycle] ?? cycle
}

function statusClass(status: string): string {
    return {
        trial: 'bg-blue-100 text-blue-700',
        active: 'bg-green-100 text-green-700',
        past_due: 'bg-yellow-100 text-yellow-700',
        suspended: 'bg-orange-100 text-orange-700',
        cancelled: 'bg-red-100 text-red-700',
        expired: 'bg-gray-100 text-gray-600',
    }[status] ?? 'bg-gray-100 text-gray-600'
}

function statusLabel(status: string): string {
    return status
        .replace('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase())
}

function deleteSubscription(subscription: Subscription) {
    if (
        !confirm(
            `Delete the subscription record for ${subscription.company.name}?`
        )
    ) {
        return
    }

    router.delete(
        route(
            'company-subscriptions.destroy',
            subscription.id
        )
    )
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Company Subscriptions
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Manage subscription plans, billing and access for companies.
                </p>
            </div>

            <Link
                :href="route('company-subscriptions.create')"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            >
                + Assign Subscription
            </Link>

        </div>

        <!-- Summary -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">
                    Total Subscriptions
                </p>

                <p class="mt-2 text-2xl font-bold text-gray-900">
                    {{ props.subscriptions.total }}
                </p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">
                    Active / Trial
                </p>

                <p class="mt-2 text-2xl font-bold text-green-600">
                    {{
                        props.subscriptions.data.filter(
                            item =>
                                item.status === 'active' ||
                                item.status === 'trial'
                        ).length
                    }}
                </p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">
                    Expired
                </p>

                <p class="mt-2 text-2xl font-bold text-gray-600">
                    {{
                        props.subscriptions.data.filter(
                            item => item.status === 'expired'
                        ).length
                    }}
                </p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm font-medium text-gray-500">
                    Suspended
                </p>

                <p class="mt-2 text-2xl font-bold text-orange-600">
                    {{
                        props.subscriptions.data.filter(
                            item => item.status === 'suspended'
                        ).length
                    }}
                </p>
            </div>

        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-200">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Company
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Plan
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Billing
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Amount
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Period
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Status
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Payment
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">

                        <tr
                            v-for="subscription in props.subscriptions.data"
                            :key="subscription.id"
                            class="hover:bg-gray-50"
                        >

                            <!-- Company -->
                            <td class="px-6 py-4">

                                <div class="font-semibold text-gray-900">
                                    {{ subscription.company.name }}
                                </div>

                                <div class="text-xs text-gray-400">
                                    {{ subscription.company.slug }}
                                </div>

                            </td>

                            <!-- Plan -->
                            <td class="px-6 py-4">

                                <div class="font-medium text-gray-900">
                                    {{ subscription.plan.name }}
                                </div>

                                <div class="text-xs text-gray-500">
                                    <span v-if="subscription.plan.employee_limit !== null">
                                        {{ subscription.plan.employee_limit }} employees
                                    </span>

                                    <span v-else>
                                        Unlimited employees
                                    </span>
                                </div>

                            </td>

                            <!-- Billing -->
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ billingLabel(subscription.billing_cycle) }}
                            </td>

                            <!-- Amount -->
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                {{ formatAmount(subscription.amount) }}
                            </td>

                            <!-- Period -->
                            <td class="px-6 py-4 text-sm text-gray-600">

                                <div>
                                    {{ formatDate(subscription.starts_at) }}
                                </div>

                                <div class="text-xs text-gray-400">
                                    to {{ formatDate(subscription.ends_at) }}
                                </div>

                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4">

                                <span
                                    :class="statusClass(subscription.status)"
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                >
                                    {{ statusLabel(subscription.status) }}
                                </span>

                            </td>

                            <!-- Payment -->
                            <td class="px-6 py-4">

                                <span
                                    v-if="subscription.paid_at"
                                    class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700"
                                >
                                    Paid
                                </span>

                                <span
                                    v-else
                                    class="inline-flex rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-700"
                                >
                                    Unpaid
                                </span>

                                <div
                                    v-if="subscription.payment_reference"
                                    class="mt-1 max-w-[120px] truncate text-xs text-gray-400"
                                >
                                    {{ subscription.payment_reference }}
                                </div>

                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4">

                                <div class="flex justify-end gap-2">

                                    <Link
                                        :href="route(
                                            'company-subscriptions.show',
                                            subscription.id
                                        )"
                                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        View
                                    </Link>

                                    <Link
                                        :href="route(
                                            'company-subscriptions.edit',
                                            subscription.id
                                        )"
                                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Edit
                                    </Link>

                                    <button
                                        type="button"
                                        class="rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50"
                                        @click="deleteSubscription(subscription)"
                                    >
                                        Delete
                                    </button>

                                </div>

                            </td>

                        </tr>

                        <!-- Empty -->
                        <tr
                            v-if="props.subscriptions.data.length === 0"
                        >
                            <td
                                colspan="8"
                                class="px-6 py-12 text-center"
                            >
                                <div class="text-sm font-medium text-gray-900">
                                    No company subscriptions found.
                                </div>

                                <p class="mt-1 text-sm text-gray-500">
                                    Assign a subscription to a company to get started.
                                </p>

                                <Link
                                    :href="route('company-subscriptions.create')"
                                    class="mt-4 inline-block text-sm font-semibold text-blue-600 hover:text-blue-800"
                                >
                                    Assign Subscription →
                                </Link>
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

            <!-- Pagination -->
            <div
                v-if="props.subscriptions.last_page > 1"
                class="flex flex-col gap-3 border-t border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
            >

                <p class="text-sm text-gray-500">
                    Showing
                    <span class="font-medium">
                        {{ props.subscriptions.from }}
                    </span>
                    to
                    <span class="font-medium">
                        {{ props.subscriptions.to }}
                    </span>
                    of
                    <span class="font-medium">
                        {{ props.subscriptions.total }}
                    </span>
                </p>

                <div class="flex flex-wrap gap-1">

                    <template
                        v-for="(link, index) in props.subscriptions.links"
                        :key="index"
                    >

                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-lg border px-3 py-1.5 text-sm"
                            :class="link.active
                                ? 'border-blue-600 bg-blue-600 text-white'
                                : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                            v-html="link.label"
                        />

                        <span
                            v-else
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-400"
                            v-html="link.label"
                        />

                    </template>

                </div>

            </div>

        </div>

    </div>
</template>