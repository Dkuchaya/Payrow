<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Price {
    id: number
    billing_cycle: string
    amount: string | number
    is_active: boolean
}

interface Plan {
    id: number
    name: string
    slug: string
    description: string | null
    employee_limit: number | null
    is_active: boolean
    subscriptions_count: number
    prices: Price[]
}

const props = defineProps<{
    plans: Plan[]
}>()

function price(plan: Plan, cycle: string): string {
    const item = plan.prices.find(
        (price) => price.billing_cycle === cycle
    )

    if (!item) {
        return '—'
    }

    return `K${Number(item.amount).toLocaleString()}`
}

function deletePlan(plan: Plan) {
    if (plan.subscriptions_count > 0) {
        alert(
            'This plan cannot be deleted because it has company subscriptions. Deactivate it instead.'
        )
        return
    }

    if (!confirm(`Delete "${plan.name}"?`)) {
        return
    }

    router.delete(
        route('subscription-plans.destroy', plan.id)
    )
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Subscription Plans
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Manage PAYROW subscription plans and pricing.
                </p>
            </div>

            <Link
                :href="route('subscription-plans.create')"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            >
                + Add Plan
            </Link>
        </div>

        <!-- Plans -->
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">

                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Plan
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Employees
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Monthly
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Quarterly
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                6 Months
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Annual
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Companies
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Status
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">

                        <tr
                            v-for="plan in props.plans"
                            :key="plan.id"
                            class="hover:bg-gray-50"
                        >
                            <!-- Plan -->
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">
                                    {{ plan.name }}
                                </div>

                                <div class="text-xs text-gray-400">
                                    {{ plan.slug }}
                                </div>

                                <div
                                    v-if="plan.description"
                                    class="mt-1 max-w-xs truncate text-xs text-gray-500"
                                >
                                    {{ plan.description }}
                                </div>
                            </td>

                            <!-- Employees -->
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <span v-if="plan.employee_limit !== null">
                                    {{ plan.employee_limit }}
                                </span>

                                <span
                                    v-else
                                    class="font-semibold text-green-600"
                                >
                                    Unlimited
                                </span>
                            </td>

                            <!-- Prices -->
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ price(plan, 'monthly') }}
                            </td>

                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ price(plan, 'quarterly') }}
                            </td>

                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ price(plan, 'semi_annual') }}
                            </td>

                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ price(plan, 'annual') }}
                            </td>

                            <!-- Companies -->
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ plan.subscriptions_count }}
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4">
                                <span
                                    :class="plan.is_active
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-gray-100 text-gray-600'"
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                >
                                    {{ plan.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">

                                    <Link
                                        :href="route(
                                            'subscription-plans.edit',
                                            plan.id
                                        )"
                                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Edit
                                    </Link>

                                    <button
                                        type="button"
                                        class="rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40"
                                        :disabled="plan.subscriptions_count > 0"
                                        @click="deletePlan(plan)"
                                    >
                                        Delete
                                    </button>

                                </div>
                            </td>
                        </tr>

                        <!-- Empty -->
                        <tr v-if="props.plans.length === 0">
                            <td
                                colspan="9"
                                class="px-6 py-12 text-center"
                            >
                                <div class="text-sm font-medium text-gray-900">
                                    No subscription plans found.
                                </div>

                                <p class="mt-1 text-sm text-gray-500">
                                    Create your first subscription plan.
                                </p>

                                <Link
                                    :href="route('subscription-plans.create')"
                                    class="mt-4 inline-block text-sm font-semibold text-blue-600 hover:text-blue-800"
                                >
                                    Add Subscription Plan →
                                </Link>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

        </div>
    </div>
</template>