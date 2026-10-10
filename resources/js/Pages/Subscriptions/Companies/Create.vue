<script setup lang="ts">
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Company {
    id: number
    name: string
}

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

const props = defineProps<{
    companies: Company[]
    plans: Plan[]
}>()

const form = useForm({
    company_id: '',
    subscription_plan_id: '',
    billing_cycle: 'monthly',
    status: 'active',
    starts_at: new Date().toISOString().slice(0, 10),
    ends_at: '',
    trial_ends_at: '',
    payment_reference: '',
    paid_at: '',
})

const selectedPlan = computed(() => {
    return props.plans.find(
        plan => String(plan.id) === String(form.subscription_plan_id)
    )
})

const selectedPrice = computed(() => {
    if (!selectedPlan.value) {
        return null
    }

    return selectedPlan.value.prices.find(
        price =>
            price.billing_cycle === form.billing_cycle &&
            price.is_active
    )
})

const selectedAmount = computed(() => {
    return selectedPrice.value
        ? Number(selectedPrice.value.amount)
        : 0
})

function formatAmount(amount: number): string {
    return `K${amount.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`
}

function employeeLimit(plan: Plan): string {
    return plan.employee_limit === null
        ? 'Unlimited employees'
        : `${plan.employee_limit} employees`
}

function submit() {
    form.post(route('company-subscriptions.store'))
}
</script>

<template>
    <div class="mx-auto max-w-5xl space-y-6">

        <!-- Header -->
        <div>
            <Link
                :href="route('company-subscriptions.index')"
                class="text-sm font-medium text-blue-600 hover:text-blue-800"
            >
                ← Back to Company Subscriptions
            </Link>

            <h1 class="mt-3 text-2xl font-bold text-gray-900">
                Assign Subscription
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Assign a PAYROW subscription plan to a company.
            </p>
        </div>

        <form
            @submit.prevent="submit"
            class="space-y-6"
        >

            <!-- Company & Plan -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

                <h2 class="text-lg font-semibold text-gray-900">
                    Subscription
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Select the company and subscription plan.
                </p>

                <div class="mt-5 grid gap-6 md:grid-cols-2">

                    <!-- Company -->
                    <div>
                        <label class="label">
                            Company
                        </label>

                        <select
                            v-model="form.company_id"
                            class="input"
                        >
                            <option value="">
                                Select company
                            </option>

                            <option
                                v-for="company in props.companies"
                                :key="company.id"
                                :value="company.id"
                            >
                                {{ company.name }}
                            </option>
                        </select>

                        <p
                            v-if="form.errors.company_id"
                            class="error"
                        >
                            {{ form.errors.company_id }}
                        </p>
                    </div>

                    <!-- Plan -->
                    <div>
                        <label class="label">
                            Subscription Plan
                        </label>

                        <select
                            v-model="form.subscription_plan_id"
                            class="input"
                        >
                            <option value="">
                                Select plan
                            </option>

                            <option
                                v-for="plan in props.plans"
                                :key="plan.id"
                                :value="plan.id"
                            >
                                {{ plan.name }} —
                                {{ employeeLimit(plan) }}
                            </option>
                        </select>

                        <p
                            v-if="form.errors.subscription_plan_id"
                            class="error"
                        >
                            {{ form.errors.subscription_plan_id }}
                        </p>
                    </div>

                </div>

                <!-- Selected Plan -->
                <div
                    v-if="selectedPlan"
                    class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-5"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <h3 class="font-semibold text-blue-900">
                                {{ selectedPlan.name }}
                            </h3>

                            <p class="mt-1 text-sm text-blue-700">
                                {{ employeeLimit(selectedPlan) }}
                            </p>
                        </div>

                        <div class="text-left sm:text-right">
                            <p class="text-xs font-medium uppercase tracking-wide text-blue-600">
                                Current Price
                            </p>

                            <p class="mt-1 text-xl font-bold text-blue-900">
                                {{ formatAmount(selectedAmount) }}
                            </p>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Billing -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

                <h2 class="text-lg font-semibold text-gray-900">
                    Billing
                </h2>

                <div class="mt-5 grid gap-6 md:grid-cols-2">

                    <!-- Billing Cycle -->
                    <div>
                        <label class="label">
                            Billing Cycle
                        </label>

                        <select
                            v-model="form.billing_cycle"
                            class="input"
                        >
                            <option value="monthly">
                                Monthly
                            </option>

                            <option value="quarterly">
                                Quarterly
                            </option>

                            <option value="semi_annual">
                                Semi-Annual
                            </option>

                            <option value="annual">
                                Annual
                            </option>
                        </select>

                        <p class="mt-1 text-xs text-gray-500">
                            The amount is automatically taken from the selected plan.
                        </p>

                        <p
                            v-if="form.errors.billing_cycle"
                            class="error"
                        >
                            {{ form.errors.billing_cycle }}
                        </p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="label">
                            Status
                        </label>

                        <select
                            v-model="form.status"
                            class="input"
                        >
                            <option value="active">
                                Active
                            </option>

                            <option value="trial">
                                Trial
                            </option>

                            <option value="past_due">
                                Past Due
                            </option>

                            <option value="suspended">
                                Suspended
                            </option>

                            <option value="cancelled">
                                Cancelled
                            </option>

                            <option value="expired">
                                Expired
                            </option>
                        </select>

                        <p
                            v-if="form.errors.status"
                            class="error"
                        >
                            {{ form.errors.status }}
                        </p>
                    </div>

                </div>

            </div>

            <!-- Dates -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

                <h2 class="text-lg font-semibold text-gray-900">
                    Subscription Period
                </h2>

                <div class="mt-5 grid gap-6 md:grid-cols-3">

                    <!-- Start -->
                    <div>
                        <label class="label">
                            Start Date
                        </label>

                        <input
                            v-model="form.starts_at"
                            type="date"
                            class="input"
                        />

                        <p
                            v-if="form.errors.starts_at"
                            class="error"
                        >
                            {{ form.errors.starts_at }}
                        </p>
                    </div>

                    <!-- End -->
                    <div>
                        <label class="label">
                            End Date
                        </label>

                        <input
                            v-model="form.ends_at"
                            type="date"
                            class="input"
                        />

                        <p class="mt-1 text-xs text-gray-500">
                            Leave empty if not yet determined.
                        </p>

                        <p
                            v-if="form.errors.ends_at"
                            class="error"
                        >
                            {{ form.errors.ends_at }}
                        </p>
                    </div>

                    <!-- Trial -->
                    <div>
                        <label class="label">
                            Trial Ends
                        </label>

                        <input
                            v-model="form.trial_ends_at"
                            type="date"
                            class="input"
                        />

                        <p
                            v-if="form.errors.trial_ends_at"
                            class="error"
                        >
                            {{ form.errors.trial_ends_at }}
                        </p>
                    </div>

                </div>

            </div>

            <!-- Payment -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

                <h2 class="text-lg font-semibold text-gray-900">
                    Payment
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Record payment information if payment has already been received.
                </p>

                <div class="mt-5 grid gap-6 md:grid-cols-2">

                    <!-- Reference -->
                    <div>
                        <label class="label">
                            Payment Reference
                        </label>

                        <input
                            v-model="form.payment_reference"
                            type="text"
                            class="input"
                            placeholder="e.g. PAY-2026-00125"
                        />

                        <p
                            v-if="form.errors.payment_reference"
                            class="error"
                        >
                            {{ form.errors.payment_reference }}
                        </p>
                    </div>

                    <!-- Paid Date -->
                    <div>
                        <label class="label">
                            Paid Date
                        </label>

                        <input
                            v-model="form.paid_at"
                            type="date"
                            class="input"
                        />

                        <p
                            v-if="form.errors.paid_at"
                            class="error"
                        >
                            {{ form.errors.paid_at }}
                        </p>
                    </div>

                </div>

            </div>

            <!-- Summary -->
            <div
                v-if="selectedPlan"
                class="rounded-xl border border-gray-200 bg-gray-50 p-6"
            >
                <h2 class="text-lg font-semibold text-gray-900">
                    Subscription Summary
                </h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-3">

                    <div>
                        <p class="text-xs font-medium uppercase text-gray-500">
                            Plan
                        </p>

                        <p class="mt-1 font-semibold text-gray-900">
                            {{ selectedPlan.name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase text-gray-500">
                            Billing
                        </p>

                        <p class="mt-1 font-semibold text-gray-900">
                            {{ form.billing_cycle }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase text-gray-500">
                            Amount
                        </p>

                        <p class="mt-1 text-lg font-bold text-blue-600">
                            {{ formatAmount(selectedAmount) }}
                        </p>
                    </div>

                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 border-t pt-5">

                <Link
                    :href="route('company-subscriptions.index')"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ form.processing ? 'Assigning...' : 'Assign Subscription' }}
                </button>

            </div>

        </form>
    </div>
</template>

<style scoped>
.label {
    @apply block text-sm font-medium text-gray-700;
}

.input {
    @apply mt-1 block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500;
}

.error {
    @apply mt-1 text-sm text-red-600;
}
</style>