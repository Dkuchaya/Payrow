<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, ref, computed } from 'vue'
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
    employee_limit: number | null
    prices: Price[]
}

interface Company {
    id: number
    name: string
    slug: string
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

const props = defineProps<{
    subscription: Subscription
    plans: Plan[]
}>()

const form = reactive({
    subscription_plan_id: props.subscription.plan.id,
    billing_cycle: props.subscription.billing_cycle,
    status: props.subscription.status,
    starts_at: props.subscription.starts_at
        ? props.subscription.starts_at.substring(0, 10)
        : '',
    ends_at: props.subscription.ends_at
        ? props.subscription.ends_at.substring(0, 10)
        : '',
    trial_ends_at: props.subscription.trial_ends_at
        ? props.subscription.trial_ends_at.substring(0, 10)
        : '',
    payment_reference: props.subscription.payment_reference ?? '',
    paid_at: props.subscription.paid_at
        ? props.subscription.paid_at.substring(0, 10)
        : '',
})

const processing = ref(false)

const selectedPlan = computed(() => {
    return props.plans.find(
        plan => plan.id === Number(form.subscription_plan_id)
    )
})

const availablePrices = computed(() => {
    return selectedPlan.value?.prices?.filter(price => price.is_active) ?? []
})

const updateSubscription = () => {
    processing.value = true

    router.put(
        route(
            'company-subscriptions.update',
            props.subscription.id
        ),
        form,
        {
            preserveScroll: true,

            onSuccess: () => {
                // Inertia will redirect back to the subscription details page
            },

            onError: (errors) => {
                console.error('Subscription update failed:', errors)
            },

            onFinish: () => {
                processing.value = false
            },
        }
    )
}
</script>

<template>
    <Head title="Edit Subscription" />

    <div class="mx-auto max-w-5xl space-y-6 p-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Edit Subscription
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    {{ subscription.company.name }}
                </p>
            </div>

            <Link
                :href="route(
                    'company-subscriptions.show',
                    subscription.id
                )"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Cancel
            </Link>
        </div>

        <!-- Form -->
        <form
            @submit.prevent="updateSubscription"
            class="rounded-xl border border-gray-200 bg-white shadow-sm"
        >
            <div class="space-y-6 p-6">

                <!-- Company -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Company
                    </label>

                    <input
                        type="text"
                        :value="subscription.company.name"
                        disabled
                        class="w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2.5 text-sm text-gray-600"
                    />
                </div>

                <!-- Plan -->
                <div>
                    <label
                        for="subscription_plan_id"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Subscription Plan
                    </label>

                    <select
                        id="subscription_plan_id"
                        v-model="form.subscription_plan_id"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option
                            v-for="plan in plans"
                            :key="plan.id"
                            :value="plan.id"
                        >
                            {{ plan.name }}
                            <span v-if="plan.employee_limit">
                                — {{ plan.employee_limit }} employees
                            </span>
                            <span v-else>
                                — Unlimited employees
                            </span>
                        </option>
                    </select>
                </div>

                <!-- Billing Cycle -->
                <div>
                    <label
                        for="billing_cycle"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Billing Cycle
                    </label>

                    <select
                        id="billing_cycle"
                        v-model="form.billing_cycle"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option
                            v-for="price in availablePrices"
                            :key="price.id"
                            :value="price.billing_cycle"
                        >
                            {{
                                price.billing_cycle
                                    .replace('_', ' ')
                                    .replace(/\b\w/g, l => l.toUpperCase())
                            }}
                            — ZMW {{ Number(price.amount).toLocaleString() }}
                        </option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label
                        for="status"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Status
                    </label>

                    <select
                        id="status"
                        v-model="form.status"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="trial">Trial</option>
                        <option value="active">Active</option>
                        <option value="past_due">Past Due</option>
                        <option value="suspended">Suspended</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

                    <div>
                        <label
                            for="starts_at"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Start Date
                        </label>

                        <input
                            id="starts_at"
                            v-model="form.starts_at"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label
                            for="ends_at"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            End Date
                        </label>

                        <input
                            id="ends_at"
                            v-model="form.ends_at"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label
                            for="trial_ends_at"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Trial End Date
                        </label>

                        <input
                            id="trial_ends_at"
                            v-model="form.trial_ends_at"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                </div>

                <!-- Payment -->
                <div class="border-t border-gray-200 pt-6">

                    <h2 class="mb-4 text-lg font-semibold text-gray-900">
                        Payment Information
                    </h2>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                        <div>
                            <label
                                for="payment_reference"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Payment Reference
                            </label>

                            <input
                                id="payment_reference"
                                v-model="form.payment_reference"
                                type="text"
                                placeholder="e.g. INV-001234"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>

                        <div>
                            <label
                                for="paid_at"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Payment Date
                            </label>

                            <input
                                id="paid_at"
                                v-model="form.paid_at"
                                type="date"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>

                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">

                <Link
                    :href="route(
                        'company-subscriptions.show',
                        subscription.id
                    )"
                    class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="processing"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{ processing ? 'Saving...' : 'Save Changes' }}
                </button>

            </div>
        </form>
    </div>
</template>