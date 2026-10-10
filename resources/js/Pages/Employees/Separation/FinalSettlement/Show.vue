<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

interface Employee {
    id: number
    employee_number: string
    first_name: string
    middle_name?: string | null
    last_name: string
    department?: {
        id: number
        name: string
    } | null
    position?: {
        id: number
        name: string
    } | null
}

interface Separation {
    id: number
    separation_type: string
    effective_date: string
    last_working_date?: string | null
    reason?: string | null
    status: string
    employee: Employee
}

interface Settlement {
    id: number
    employee_separation_id: number

    status: 'draft' | 'review' | 'approved' | 'paid' | 'cancelled'

    salary_due: number | string | null
    leave_pay: number | string | null
    allowances: number | string | null
    overtime: number | string | null
    bonus: number | string | null
    commission: number | string | null
    notice_pay: number | string | null
    gratuity: number | string | null
    severance_pay: number | string | null
    other_earnings: number | string | null

    loan_deduction: number | string | null
    advance_deduction: number | string | null
    notice_deduction: number | string | null
    statutory_deductions: number | string | null
    other_deductions: number | string | null

    gross_earnings?: number | string | null
    total_deductions?: number | string | null
    net_settlement?: number | string | null

    notes?: string | null

    payment_reference?: string | null
    payment_method?: string | null
    paid_at?: string | null

    prepared_by?: {
        id: number
        name: string
    } | null

    reviewed_by?: {
        id: number
        name: string
    } | null

    approved_by?: {
        id: number
        name: string
    } | null

    paid_by?: {
        id: number
        name: string
    } | null

    created_at?: string
    updated_at?: string
}

const props = defineProps<{
    separation: Separation
    settlement: Settlement
}>()

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const employeeName = computed(() => {
    const employee = props.separation.employee

    return [
        employee.first_name,
        employee.middle_name,
        employee.last_name,
    ]
        .filter(Boolean)
        .join(' ')
})

function amount(value: number | string | null | undefined): number {
    return Number(value ?? 0)
}

function money(value: number | string | null | undefined): string {
    return new Intl.NumberFormat('en-ZM', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount(value))
}

function formatDate(value?: string | null): string {
    if (!value) {
        return '—'
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return value
    }

    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

function formatStatus(status: string): string {
    return status
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
}

function formatSeparationType(type: string): string {
    return type
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
}

/*
|--------------------------------------------------------------------------
| Editable form
|--------------------------------------------------------------------------
*/

const form = ref({
    salary_due: amount(props.settlement.salary_due),
    leave_pay: amount(props.settlement.leave_pay),
    allowances: amount(props.settlement.allowances),
    overtime: amount(props.settlement.overtime),
    bonus: amount(props.settlement.bonus),
    commission: amount(props.settlement.commission),
    notice_pay: amount(props.settlement.notice_pay),
    gratuity: amount(props.settlement.gratuity),
    severance_pay: amount(props.settlement.severance_pay),
    other_earnings: amount(props.settlement.other_earnings),

    loan_deduction: amount(props.settlement.loan_deduction),
    advance_deduction: amount(props.settlement.advance_deduction),
    notice_deduction: amount(props.settlement.notice_deduction),
    statutory_deductions: amount(props.settlement.statutory_deductions),
    other_deductions: amount(props.settlement.other_deductions),

    notes: props.settlement.notes ?? '',
})

const processing = ref(false)
const showPaymentModal = ref(false)
const showCancelModal = ref(false)

const paymentForm = ref({
    payment_reference: props.settlement.payment_reference ?? '',
    payment_method: props.settlement.payment_method ?? '',
})

const cancelReason = ref('')

/*
|--------------------------------------------------------------------------
| Calculations
|--------------------------------------------------------------------------
*/

const grossEarnings = computed(() => {
    return (
        amount(form.value.salary_due) +
        amount(form.value.leave_pay) +
        amount(form.value.allowances) +
        amount(form.value.overtime) +
        amount(form.value.bonus) +
        amount(form.value.commission) +
        amount(form.value.notice_pay) +
        amount(form.value.gratuity) +
        amount(form.value.severance_pay) +
        amount(form.value.other_earnings)
    )
})

const totalDeductions = computed(() => {
    return (
        amount(form.value.loan_deduction) +
        amount(form.value.advance_deduction) +
        amount(form.value.notice_deduction) +
        amount(form.value.statutory_deductions) +
        amount(form.value.other_deductions)
    )
})

const netSettlement = computed(() => {
    return Math.max(0, grossEarnings.value - totalDeductions.value)
})

/*
|--------------------------------------------------------------------------
| Status helpers
|--------------------------------------------------------------------------
*/

const isDraft = computed(() => props.settlement.status === 'draft')
const isReview = computed(() => props.settlement.status === 'review')
const isApproved = computed(() => props.settlement.status === 'approved')
const isPaid = computed(() => props.settlement.status === 'paid')
const isCancelled = computed(() => props.settlement.status === 'cancelled')

/*
|--------------------------------------------------------------------------
| Save settlement
|--------------------------------------------------------------------------
*/

function updateSettlement() {
    if (!isDraft.value || processing.value) {
        return
    }

    processing.value = true

    router.put(
        `/employee-final-settlements/${props.settlement.id}`,
        {
            ...form.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false
            },
        }
    )
}

/*
|--------------------------------------------------------------------------
| Submit for review
|--------------------------------------------------------------------------
*/

function submitForReview() {
    if (!isDraft.value || processing.value) {
        return
    }

    processing.value = true

    router.post(
        `/employee-final-settlements/${props.settlement.id}/submit-review`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false
            },
        }
    )
}

/*
|--------------------------------------------------------------------------
| Approve
|--------------------------------------------------------------------------
*/

function approveSettlement() {
    if (!isReview.value || processing.value) {
        return
    }

    if (
        !confirm(
            'Are you sure you want to approve this final settlement?'
        )
    ) {
        return
    }

    processing.value = true

    router.post(
        `/employee-final-settlements/${props.settlement.id}/approve`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false
            },
        }
    )
}

/*
|--------------------------------------------------------------------------
| Mark paid
|--------------------------------------------------------------------------
*/

function openPaymentModal() {
    paymentForm.value.payment_reference =
        props.settlement.payment_reference ?? ''

    paymentForm.value.payment_method =
        props.settlement.payment_method ?? ''

    showPaymentModal.value = true
}

function markPaid() {
    if (!isApproved.value || processing.value) {
        return
    }

    if (!paymentForm.value.payment_reference.trim()) {
        alert('Please enter the payment reference.')
        return
    }

    if (!paymentForm.value.payment_method.trim()) {
        alert('Please select a payment method.')
        return
    }

    processing.value = true

    router.post(
        `/employee-final-settlements/${props.settlement.id}/paid`,
        {
            payment_reference:
                paymentForm.value.payment_reference,

            payment_method:
                paymentForm.value.payment_method,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showPaymentModal.value = false
            },
            onFinish: () => {
                processing.value = false
            },
        }
    )
}

/*
|--------------------------------------------------------------------------
| Cancel
|--------------------------------------------------------------------------
*/

function openCancelModal() {
    cancelReason.value = ''
    showCancelModal.value = true
}

function cancelSettlement() {
    if (processing.value) {
        return
    }

    processing.value = true

    router.post(
        `/employee-final-settlements/${props.settlement.id}/cancel`,
        {
            reason: cancelReason.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showCancelModal.value = false
            },
            onFinish: () => {
                processing.value = false
            },
        }
    )
}
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <Link
                            :href="`/employee-separations/${separation.id}`"
                            class="text-sm font-medium text-gray-500 hover:text-gray-900"
                        >
                            ← Separation
                        </Link>
                    </div>

                    <h1 class="mt-3 text-2xl font-bold text-gray-900">
                        Final Settlement
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Review and process the employee's final settlement.
                    </p>
                </div>

                <!-- Status -->
                <span
                    class="inline-flex w-fit items-center rounded-full px-4 py-2 text-sm font-semibold"
                    :class="{
                        'bg-gray-100 text-gray-700': settlement.status === 'draft',
                        'bg-blue-100 text-blue-700': settlement.status === 'review',
                        'bg-purple-100 text-purple-700': settlement.status === 'approved',
                        'bg-green-100 text-green-700': settlement.status === 'paid',
                        'bg-red-100 text-red-700': settlement.status === 'cancelled',
                    }"
                >
                    {{ formatStatus(settlement.status) }}
                </span>
            </div>

            <!-- Employee Information -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Employee Information
                    </h2>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Employee
                        </p>

                        <p class="mt-1 font-semibold text-gray-900">
                            {{ employeeName }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Employee Number
                        </p>

                        <p class="mt-1 font-semibold text-gray-900">
                            {{ separation.employee.employee_number }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Department
                        </p>

                        <p class="mt-1 text-gray-700">
                            {{ separation.employee.department?.name ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Position
                        </p>

                        <p class="mt-1 text-gray-700">
                            {{ separation.employee.position?.name ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Separation Type
                        </p>

                        <p class="mt-1 text-gray-700">
                            {{ formatSeparationType(separation.separation_type) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Effective Date
                        </p>

                        <p class="mt-1 text-gray-700">
                            {{ formatDate(separation.effective_date) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Last Working Day
                        </p>

                        <p class="mt-1 text-gray-700">
                            {{ formatDate(separation.last_working_date) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Settlement Status
                        </p>

                        <p class="mt-1 font-semibold text-blue-700">
                            {{ formatStatus(settlement.status) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Settlement -->
            <div class="grid gap-6 lg:grid-cols-3">

                <!-- Main calculation -->
                <div class="space-y-6 lg:col-span-2">

                    <!-- Earnings -->
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">
                                    Earnings
                                </h2>

                                <p class="mt-1 text-sm text-gray-500">
                                    Amounts payable to the employee.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">

                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Salary Due
                                </label>

                                <div class="relative mt-1">
                                    <span class="absolute left-3 top-2.5 text-sm text-gray-500">
                                        K
                                    </span>

                                    <input
                                        v-model.number="form.salary_due"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :disabled="!isDraft"
                                        class="w-full rounded-lg border border-gray-300 py-2.5 pl-8 pr-3 text-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Leave Pay
                                </label>

                                <input
                                    v-model.number="form.leave_pay"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!isDraft"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Allowances
                                </label>

                                <input
                                    v-model.number="form.allowances"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!isDraft"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Overtime
                                </label>

                                <input
                                    v-model.number="form.overtime"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!isDraft"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Bonus
                                </label>

                                <input
                                    v-model.number="form.bonus"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!isDraft"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Commission
                                </label>

                                <input
                                    v-model.number="form.commission"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!isDraft"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Notice Pay
                                </label>

                                <input
                                    v-model.number="form.notice_pay"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!isDraft"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Gratuity
                                </label>

                                <input
                                    v-model.number="form.gratuity"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!isDraft"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Severance Pay
                                </label>

                                <input
                                    v-model.number="form.severance_pay"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!isDraft"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Other Earnings
                                </label>

                                <input
                                    v-model.number="form.other_earnings"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!isDraft"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100"
                                />
                            </div>

                        </div>

                        <div class="mt-6 flex items-center justify-between rounded-lg bg-gray-50 px-4 py-4">
                            <span class="font-semibold text-gray-700">
                                Gross Earnings
                            </span>

                            <span class="text-lg font-bold text-gray-900">
                                K {{ money(grossEarnings) }}
                            </span>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="mb-5">
                            <h2 class="text-lg font-semibold text-gray-900">
                                Deductions
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Amounts deducted from the final settlement.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">

                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Loan Deduction
                                </label>

                                <input
                                    v-model.number="form.loan_deduction"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!isDraft"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Advance Deduction
                                </label>

                                <input
                                    v-model.number="form.advance_deduction"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!isDraft"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Notice Deduction
                                </label>

                                <input
                                    v-model.number="form.notice_deduction"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!isDraft"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Statutory Deductions
                                </label>

                                <input
                                    v-model.number="form.statutory_deductions"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!isDraft"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">
                                    Other Deductions
                                </label>

                                <input
                                    v-model.number="form.other_deductions"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!isDraft"
                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100"
                                />
                            </div>

                        </div>

                        <div class="mt-6 flex items-center justify-between rounded-lg bg-gray-50 px-4 py-4">
                            <span class="font-semibold text-gray-700">
                                Total Deductions
                            </span>

                            <span class="text-lg font-bold text-gray-900">
                                K {{ money(totalDeductions) }}
                            </span>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Notes
                        </h2>

                        <textarea
                            v-model="form.notes"
                            rows="5"
                            :disabled="!isDraft"
                            placeholder="Add notes about the final settlement..."
                            class="mt-4 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"
                        />
                    </div>

                </div>

                <!-- Summary -->
                <div class="lg:col-span-1">
                    <div class="sticky top-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                        <h2 class="text-lg font-semibold text-gray-900">
                            Settlement Summary
                        </h2>

                        <div class="mt-6 space-y-4">

                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">
                                    Gross Earnings
                                </span>

                                <span class="font-medium text-gray-900">
                                    K {{ money(grossEarnings) }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">
                                    Total Deductions
                                </span>

                                <span class="font-medium text-red-600">
                                    - K {{ money(totalDeductions) }}
                                </span>
                            </div>

                            <div class="border-t border-gray-200 pt-5">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-900">
                                        Net Settlement
                                    </span>

                                    <span class="text-2xl font-bold text-blue-600">
                                        K {{ money(netSettlement) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Draft actions -->
                        <div
                            v-if="isDraft"
                            class="mt-6 space-y-3"
                        >
                            <button
                                type="button"
                                :disabled="processing"
                                class="flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="updateSettlement"
                            >
                                {{ processing ? 'Saving...' : 'Save Settlement' }}
                            </button>

                            <button
                                type="button"
                                :disabled="processing"
                                class="flex w-full items-center justify-center rounded-lg bg-purple-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="submitForReview"
                            >
                                Submit for Review
                            </button>
                        </div>

                        <!-- Review action -->
                        <div
                            v-if="isReview"
                            class="mt-6"
                        >
                            <button
                                type="button"
                                :disabled="processing"
                                class="flex w-full items-center justify-center rounded-lg bg-green-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="approveSettlement"
                            >
                                ✓ Approve Final Settlement
                            </button>
                        </div>

                        <!-- Approved action -->
                        <div
                            v-if="isApproved"
                            class="mt-6"
                        >
                            <button
                                type="button"
                                :disabled="processing"
                                class="flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="openPaymentModal"
                            >
                                ✓ Mark Settlement as Paid
                            </button>
                        </div>

                        <!-- Paid -->
                        <div
                            v-if="isPaid"
                            class="mt-6 rounded-lg border border-green-200 bg-green-50 p-4"
                        >
                            <p class="text-sm font-semibold text-green-800">
                                Settlement Paid
                            </p>

                            <p class="mt-1 text-sm text-green-700">
                                Payment Reference:
                                {{ settlement.payment_reference || '—' }}
                            </p>

                            <p class="mt-1 text-sm text-green-700">
                                Payment Method:
                                {{ settlement.payment_method || '—' }}
                            </p>

                            <p
                                v-if="settlement.paid_at"
                                class="mt-1 text-sm text-green-700"
                            >
                                Paid:
                                {{ formatDate(settlement.paid_at) }}
                            </p>
                        </div>

                        <!-- Cancel -->
                        <button
                            v-if="
                                !isPaid &&
                                !isCancelled
                            "
                            type="button"
                            :disabled="processing"
                            class="mt-3 flex w-full items-center justify-center rounded-lg border border-red-300 bg-white px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:opacity-50"
                            @click="openCancelModal"
                        >
                            Cancel Settlement
                        </button>

                    </div>
                </div>
            </div>

            <!-- Processing history -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Settlement History
                </h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                    <div class="rounded-lg bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Prepared By
                        </p>

                        <p class="mt-1 font-medium text-gray-900">
                            {{ settlement.prepared_by?.name ?? '—' }}
                        </p>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Reviewed By
                        </p>

                        <p class="mt-1 font-medium text-gray-900">
                            {{ settlement.reviewed_by?.name ?? '—' }}
                        </p>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Approved By
                        </p>

                        <p class="mt-1 font-medium text-gray-900">
                            {{ settlement.approved_by?.name ?? '—' }}
                        </p>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Paid By
                        </p>

                        <p class="mt-1 font-medium text-gray-900">
                            {{ settlement.paid_by?.name ?? '—' }}
                        </p>
                    </div>

                </div>
            </div>

        </div>

        <!-- Payment Modal -->
        <div
            v-if="showPaymentModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">

                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Mark Settlement as Paid
                    </h2>

                    <button
                        type="button"
                        class="text-2xl text-gray-400 hover:text-gray-700"
                        @click="showPaymentModal = false"
                    >
                        ×
                    </button>
                </div>

                <p class="mt-2 text-sm text-gray-500">
                    Record the payment details for this final settlement.
                </p>

                <div class="mt-6 space-y-4">

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Payment Reference
                        </label>

                        <input
                            v-model="paymentForm.payment_reference"
                            type="text"
                            placeholder="e.g. BANK-REF-12345"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Payment Method
                        </label>

                        <select
                            v-model="paymentForm.payment_method"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">
                                Select payment method
                            </option>

                            <option value="bank_transfer">
                                Bank Transfer
                            </option>

                            <option value="mobile_money">
                                Mobile Money
                            </option>

                            <option value="cash">
                                Cash
                            </option>

                            <option value="cheque">
                                Cheque
                            </option>
                        </select>
                    </div>

                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        @click="showPaymentModal = false"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        :disabled="processing"
                        class="rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50"
                        @click="markPaid"
                    >
                        {{ processing ? 'Processing...' : 'Confirm Payment' }}
                    </button>
                </div>

            </div>
        </div>

        <!-- Cancel Modal -->
        <div
            v-if="showCancelModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">

                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Cancel Settlement
                    </h2>

                    <button
                        type="button"
                        class="text-2xl text-gray-400 hover:text-gray-700"
                        @click="showCancelModal = false"
                    >
                        ×
                    </button>
                </div>

                <p class="mt-2 text-sm text-gray-500">
                    Are you sure you want to cancel this final settlement?
                </p>

                <textarea
                    v-model="cancelReason"
                    rows="4"
                    placeholder="Optional cancellation reason..."
                    class="mt-5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                />

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        @click="showCancelModal = false"
                    >
                        Keep Settlement
                    </button>

                    <button
                        type="button"
                        :disabled="processing"
                        class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
                        @click="cancelSettlement"
                    >
                        {{ processing ? 'Cancelling...' : 'Cancel Settlement' }}
                    </button>
                </div>

            </div>
        </div>
    </AppLayout>
</template>