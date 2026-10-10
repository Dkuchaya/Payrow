<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Company {
    id: number
    name: string
    slug: string
    logo: string | null
    currency: string
}

interface Plan {
    id: number
    name: string
    slug: string
    employee_limit: number | null
    prices: {
        id: number
        billing_cycle: string
        amount: string | number
        is_active: boolean
    }[]
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
    created_at: string
}


const props = defineProps<{
    subscription: Subscription
}>()

const showPaymentModal = ref(false)

const paymentForm = reactive({
    amount: Number(props.subscription.amount),
    payment_date: new Date().toISOString().substring(0, 10),
    payment_reference: '',
    payment_method: '',
    notes: '',
})

const recordingPayment = ref(false)
const processingPayment = ref(false)

const recordPayment = () => {
    processingPayment.value = true

    router.post(
        route(
            'company-subscriptions.record-payment',
            props.subscription.id
        ),
        paymentForm,
        {
            preserveScroll: true,

            onSuccess: () => {
                showPaymentModal.value = false

                paymentForm.amount = Number(props.subscription.amount)
                paymentForm.payment_date = new Date()
                    .toISOString()
                    .substring(0, 10)
                paymentForm.payment_reference = ''
                paymentForm.payment_method = ''
                paymentForm.notes = ''
            },

            onError: (errors) => {
                console.error('Payment recording error:', errors)
            },

            onFinish: () => {
                processingPayment.value = false
            },
        }
    )
}


const renewing = ref(false)

const renewSubscription = () => {
    if (
        !confirm(
            'Are you sure you want to renew this subscription?'
        )
    ) {
        return
    }

    renewing.value = true

    router.post(
        route(
            'company-subscriptions.renew',
            props.subscription.id
        ),
        {},
        {
            preserveScroll: true,

            onSuccess: () => {
                console.log('Subscription renewed successfully.')
            },

            onError: (errors) => {
                console.error('Renewal error:', errors)
            },

            onFinish: () => {
                renewing.value = false
            },
        }
    )
}


const suspending = ref(false)

const suspendSubscription = () => {
    if (
        !confirm(
            'Are you sure you want to suspend this subscription?'
        )
    ) {
        return
    }

    suspending.value = true

    router.post(
        route(
            'company-subscriptions.suspend',
            props.subscription.id
        ),
        {},
        {
            preserveScroll: true,

            onSuccess: () => {
                console.log('Subscription suspended successfully.')
            },

            onError: (errors) => {
                console.error('Suspension error:', errors)
            },

            onFinish: () => {
                suspending.value = false
            },
        }
    )
}

const cancelling = ref(false)

const cancelSubscription = () => {
    if (
        !confirm(
            'Are you sure you want to cancel this subscription? This action cannot be undone.'
        )
    ) {
        return
    }

    cancelling.value = true

    router.post(
        route(
            'company-subscriptions.cancel',
            props.subscription.id
        ),
        {},
        {
            preserveScroll: true,

            onSuccess: () => {
                console.log('Subscription cancelled successfully.')
            },

            onError: (errors) => {
                console.error('Cancellation error:', errors)
            },

            onFinish: () => {
                cancelling.value = false
            },
        }
    )
}


function formatDate(date: string | null): string {
    if (!date) {
        return '—'
    }

    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    })
}

function formatAmount(amount: string | number): string {
    return `${props.subscription.company.currency} ${Number(amount).toLocaleString(
        undefined,
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }
    )}`
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
        .replace(/\b\w/g, letter => letter.toUpperCase())
}

function billingLabel(cycle: string): string {
    return {
        monthly: 'Monthly',
        quarterly: 'Quarterly',
        semi_annual: 'Semi-Annual',
        annual: 'Annual',
    }[cycle] ?? cycle
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

            <div>
                <Link
                    :href="route('company-subscriptions.index')"
                    class="text-sm font-medium text-blue-600 hover:text-blue-800"
                >
                    ← Back to Company Subscriptions
                </Link>

                <div class="mt-3 flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-lg font-bold text-blue-700"
                    >
                        {{ props.subscription.company.name.charAt(0).toUpperCase() }}
                    </div>

                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            {{ props.subscription.company.name }}
                        </h1>

                        <p class="text-sm text-gray-500">
                            Subscription #{{ props.subscription.id }}
                        </p>
                    </div>
                </div>
            </div>

            <Link
                :href="route(
                    'company-subscriptions.edit',
                    props.subscription.id
                )"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
            >
                Edit Subscription
            </Link>

        </div>

        <!-- Status Banner -->
        <div
            class="rounded-xl border p-5"
            :class="
                props.subscription.status === 'active'
                    ? 'border-green-200 bg-green-50'
                    : props.subscription.status === 'trial'
                        ? 'border-blue-200 bg-blue-50'
                        : props.subscription.status === 'suspended'
                            ? 'border-orange-200 bg-orange-50'
                            : 'border-gray-200 bg-gray-50'
            "
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <p class="text-sm font-medium text-gray-600">
                        Subscription Status
                    </p>

                    <div class="mt-1 flex items-center gap-3">
                        <span
                            :class="statusClass(props.subscription.status)"
                            class="inline-flex rounded-full px-3 py-1 text-sm font-semibold"
                        >
                            {{ statusLabel(props.subscription.status) }}
                        </span>
                    </div>
                </div>

                <div class="text-left sm:text-right">
                    <p class="text-sm text-gray-500">
                        Subscription Amount
                    </p>

                    <p class="text-2xl font-bold text-gray-900">
                        {{ formatAmount(props.subscription.amount) }}
                    </p>

                    <p class="text-xs text-gray-500">
                        {{ billingLabel(props.subscription.billing_cycle) }}
                    </p>
                </div>

            </div>
        </div>

        <!-- Overview -->
        <div class="grid gap-6 lg:grid-cols-2">

            <!-- Company -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

                <h2 class="text-lg font-semibold text-gray-900">
                    Company
                </h2>

                <dl class="mt-5 space-y-4">

                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500">
                            Company
                        </dt>

                        <dd class="text-right text-sm font-medium text-gray-900">
                            {{ props.subscription.company.name }}
                        </dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500">
                            Slug
                        </dt>

                        <dd class="text-right text-sm font-medium text-gray-900">
                            {{ props.subscription.company.slug }}
                        </dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500">
                            Currency
                        </dt>

                        <dd class="text-right text-sm font-medium text-gray-900">
                            {{ props.subscription.company.currency }}
                        </dd>
                    </div>

                </dl>

            </div>

            <!-- Plan -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

                <h2 class="text-lg font-semibold text-gray-900">
                    Subscription Plan
                </h2>

                <dl class="mt-5 space-y-4">

                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500">
                            Plan
                        </dt>

                        <dd class="text-right text-sm font-semibold text-gray-900">
                            {{ props.subscription.plan.name }}
                        </dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500">
                            Employee Limit
                        </dt>

                        <dd class="text-right text-sm font-medium text-gray-900">
                            <span v-if="props.subscription.plan.employee_limit !== null">
                                {{ props.subscription.plan.employee_limit }}
                            </span>

                            <span
                                v-else
                                class="text-green-600"
                            >
                                Unlimited
                            </span>
                        </dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500">
                            Billing Cycle
                        </dt>

                        <dd class="text-right text-sm font-medium text-gray-900">
                            {{ billingLabel(props.subscription.billing_cycle) }}
                        </dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500">
                            Amount
                        </dt>

                        <dd class="text-right text-sm font-semibold text-gray-900">
                            {{ formatAmount(props.subscription.amount) }}
                        </dd>
                    </div>

                </dl>

            </div>

        </div>

        <!-- Period -->
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

            <h2 class="text-lg font-semibold text-gray-900">
                Subscription Period
            </h2>

            <div class="mt-5 grid gap-6 sm:grid-cols-3">

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Start Date
                    </p>

                    <p class="mt-2 font-semibold text-gray-900">
                        {{ formatDate(props.subscription.starts_at) }}
                    </p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Expiry Date
                    </p>

                    <p class="mt-2 font-semibold text-gray-900">
                        {{ formatDate(props.subscription.ends_at) }}
                    </p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Trial Ends
                    </p>

                    <p class="mt-2 font-semibold text-gray-900">
                        {{ formatDate(props.subscription.trial_ends_at) }}
                    </p>
                </div>

            </div>

        </div>

        <!-- Payment -->
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

            <h2 class="text-lg font-semibold text-gray-900">
                Payment Information
            </h2>

            <div class="mt-5 grid gap-6 sm:grid-cols-3">

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Amount
                    </p>

                    <p class="mt-2 font-semibold text-gray-900">
                        {{ formatAmount(props.subscription.amount) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Payment Reference
                    </p>

                    <p class="mt-2 font-semibold text-gray-900">
                        {{ props.subscription.payment_reference || 'Not recorded' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Payment Date
                    </p>

                    <p class="mt-2 font-semibold text-gray-900">
                        {{ formatDate(props.subscription.paid_at) }}
                    </p>
                </div>

            </div>

        </div>

        <!-- Actions -->
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

            <h2 class="text-lg font-semibold text-gray-900">
                Subscription Actions
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Subscription renewal, payment, suspension and cancellation actions will appear here.
            </p>

            <div class="mt-5 flex flex-wrap gap-3">

                <Link
                    :href="route(
                        'company-subscriptions.edit',
                        props.subscription.id
                    )"
                    class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                >
                    Edit
                </Link>

            <button
                type="button"
                @click="showPaymentModal = true"
                class="rounded-lg border border-green-300 bg-white px-4 py-2 text-sm font-medium text-green-700 transition hover:bg-green-50"
            >
                Record Payment
            </button>

               <button
                    type="button"
                    :disabled="renewing"
                    @click="renewSubscription"
                    class="rounded-lg border border-green-300 bg-white px-4 py-2 text-sm font-medium text-green-700 transition hover:bg-green-50 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{ renewing ? 'Renewing...' : 'Renew' }}
                </button>

                <button
                    type="button"
                    :disabled="suspending"
                    @click="suspendSubscription"
                    class="rounded-lg border border-yellow-300 bg-white px-4 py-2 text-sm font-medium text-yellow-700 transition hover:bg-yellow-50 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{ suspending ? 'Suspending...' : 'Suspend' }}
                </button>

                <button
                    type="button"
                    :disabled="cancelling"
                    @click="cancelSubscription"
                    class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{ cancelling ? 'Cancelling...' : 'Cancel' }}
                </button>


            </div>

        </div>

<!-- Record Payment Modal -->
<div
    v-if="showPaymentModal"
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 p-4"
>
    <div
        class="relative flex w-full max-w-lg flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
        style="max-height: 90vh;"
    >

        <!-- Modal Header -->
        <div class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6 py-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">
                    Record Payment
                </h2>

                <p class="text-sm text-gray-500">
                    {{ props.subscription.company.name }}
                </p>
            </div>

            <button
                type="button"
                @click="showPaymentModal = false"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-xl text-gray-400 hover:bg-gray-100 hover:text-gray-600"
            >
                ×
            </button>
        </div>

        <!-- Scrollable Form Body -->
        <form
            @submit.prevent="recordPayment"
            class="flex min-h-0 flex-1 flex-col"
        >

            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">

                <!-- Amount -->
                <div class="mb-5">
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Amount
                    </label>

                    <input
                        v-model.number="paymentForm.amount"
                        type="number"
                        min="0.01"
                        step="0.01"
                        required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
                    />
                </div>

                <!-- Payment Date -->
                <div class="mb-5">
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Payment Date
                    </label>

                    <input
                        v-model="paymentForm.payment_date"
                        type="date"
                        required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
                    />
                </div>

                <!-- Reference -->
                <div class="mb-5">
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Payment Reference
                    </label>

                    <input
                        v-model="paymentForm.payment_reference"
                        type="text"
                        placeholder="e.g. BANK-TRX-001234"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
                    />
                </div>

                <!-- Payment Method -->
                <div class="mb-5">
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Payment Method
                    </label>

                    <select
                        v-model="paymentForm.payment_method"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
                    >
                        <option value="">
                            Select method
                        </option>

                        <option value="bank_transfer">
                            Bank Transfer
                        </option>

                        <option value="cash">
                            Cash
                        </option>

                        <option value="mobile_money">
                            Mobile Money
                        </option>

                        <option value="card">
                            Card
                        </option>

                        <option value="cheque">
                            Cheque
                        </option>

                        <option value="other">
                            Other
                        </option>
                    </select>
                </div>

                <!-- Notes -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Notes
                    </label>

                    <textarea
                        v-model="paymentForm.notes"
                        rows="3"
                        placeholder="Optional payment notes..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
                    ></textarea>
                </div>

            </div>

            <!-- FIXED FOOTER -->
            <div
                class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-200 bg-white px-6 py-4"
            >

                <button
                    type="button"
                    @click="showPaymentModal = false"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </button>

                

                    <button
                        type="submit"
                        class="rounded-lg border border-green-300 bg-white px-4 py-2 text-sm font-medium text-green-700 transition hover:bg-green-50"                        :disabled="recordingPayment"
                                
                    >
                    <span v-if="recordingPayment"">
                        Recording...
                    </span>

                    <span v-else ">
                        Record Payment
                    </span>
                </button>

            </div>

        </form>

    </div>
</div>

    </div>
</template>