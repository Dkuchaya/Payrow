<script setup lang="ts">
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface LeaveBalance {
    id: number
    year: number
    entitled_days: number
    accrued_days: number
    carried_forward_days: number
    used_days: number
    adjusted_days: number
    pending_days: number
    available_days: number
}

interface BalanceTransaction {
    id: number
    transaction_type: string
    days: number
    balance_before: number
    balance_after: number
    description: string | null
    reason: string | null
    created_by: string | null
    created_at: string | null
}

const props = defineProps<{
    company: {
        id: number
        name: string
    }
    leaveRequest: {
        id: number
        employee: {
            id: number
            employee_number: string | null
            name: string
        }
        leave_type: {
            id: number
            name: string
            code: string | null
        }
        policy: {
            id: number
            entitlement_days: number
            accrual_method: string
            carry_forward_allowed: boolean
        } | null
        start_date: string
        end_date: string
        days: number
        reason: string | null
        notes: string | null
        attachment_path: string | null
        attachment_url: string | null
        status: string
        submitted_by: string | null
        approved_by: string | null
        approved_at: string | null
        rejection_reason: string | null
        cancelled_by: string | null
        cancelled_at: string | null
        return_date: string | null
        created_at: string | null
    }
    balance: LeaveBalance | null

    balance_transactions: BalanceTransaction[]
}>()

const statusClass = computed(() => {
    switch (props.leaveRequest.status) {
        case 'approved':
            return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'

        case 'rejected':
            return 'bg-red-50 text-red-700 ring-red-600/20'

        case 'cancelled':
            return 'bg-slate-100 text-slate-600 ring-slate-500/20'

        default:
            return 'bg-amber-50 text-amber-700 ring-amber-600/20'
    }
})

const statusLabel = computed(() => {
    return props.leaveRequest.status
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase())
})

const formatNumber = (value: number) => {
    return Number(value ?? 0).toFixed(2)
}

const formatTransactionType = (value: string) => {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase())
}

const transactionDelta = (transaction: BalanceTransaction) => {
    return Number(
        (
            transaction.balance_after -
            transaction.balance_before
        ).toFixed(2)
    )
}

const transactionDeltaClass = (
    transaction: BalanceTransaction
) => {
    const delta = transactionDelta(transaction)

    if (delta > 0) {
        return 'text-emerald-600'
    }

    if (delta < 0) {
        return 'text-red-600'
    }

    return 'text-slate-600'
}

const transactionDeltaLabel = (
    transaction: BalanceTransaction
) => {
    const delta = transactionDelta(transaction)

    if (delta > 0) {
        return `+${formatNumber(delta)}`
    }

    return formatNumber(delta)
}

function approveRequest() {
    Swal.fire({
        title: 'Approve Leave Request?',
        text: `Approve ${props.leaveRequest.days} day(s) of leave for ${props.leaveRequest.employee.name}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (!result.isConfirmed) {
            return
        }

        router.post(
            route(
                'leave.requests.approve',
                props.leaveRequest.id
            )
        )
    })
}

async function rejectRequest() {
    const result = await Swal.fire({
        title: 'Reject Leave Request',
        input: 'textarea',
        inputLabel: 'Reason for rejection',
        inputPlaceholder: 'Enter the reason...',
        inputAttributes: {
            'aria-label': 'Reason for rejection',
        },
        showCancelButton: true,
        confirmButtonText: 'Reject Request',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'Please provide a rejection reason.'
            }

            return undefined
        },
    })

    if (!result.isConfirmed) {
        return
    }

    router.post(
        route(
            'leave.requests.reject',
            props.leaveRequest.id
        ),
        {
            rejection_reason: result.value,
        }
    )
}

function cancelRequest() {
    Swal.fire({
        title: 'Cancel Leave Request?',
        text: 'This action will release the reserved leave balance.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Cancel',
        cancelButtonText: 'Keep Request',
    }).then((result) => {
        if (!result.isConfirmed) {
            return
        }

        router.post(
            route(
                'leave.requests.cancel',
                props.leaveRequest.id
            )
        )
    })
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <Link
                        :href="route('leave.requests.index')"
                        class="hover:text-blue-600"
                    >
                        Leave Requests
                    </Link>

                    <span>/</span>

                    <span>#{{ leaveRequest.id }}</span>
                </div>

                <h1 class="mt-2 text-2xl font-bold text-slate-900">
                    Leave Request
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Review and manage this employee leave request.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">

                <Link
                    :href="route('leave.requests.index')"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Back to Requests
                </Link>

                <button
                    v-if="leaveRequest.status === 'pending'"
                    type="button"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                    @click="approveRequest"
                >
                    Approve
                </button>

                <button
                    v-if="leaveRequest.status === 'pending'"
                    type="button"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                    @click="rejectRequest"
                >
                    Reject
                </button>

                <button
                    v-if="
                        leaveRequest.status === 'pending' ||
                        leaveRequest.status === 'approved'
                    "
                    type="button"
                    class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                    @click="cancelRequest"
                >
                    Cancel
                </button>

            </div>
        </div>

        <!-- Status -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <p class="text-sm text-slate-500">
                        Request Status
                    </p>

                    <span
                        class="mt-2 inline-flex rounded-full px-3 py-1 text-sm font-semibold capitalize ring-1 ring-inset"
                        :class="statusClass"
                    >
                        {{ statusLabel }}
                    </span>
                </div>

                <div class="text-left sm:text-right">
                    <p class="text-sm text-slate-500">
                        Request Submitted
                    </p>

                    <p class="mt-1 font-medium text-slate-900">
                        {{ leaveRequest.created_at || '—' }}
                    </p>
                </div>

            </div>
        </div>

        <!-- Main grid -->
        <div class="grid gap-6 lg:grid-cols-3">

            <!-- Employee -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="font-semibold text-slate-900">
                    Employee
                </h2>

                <div class="mt-5 space-y-4">

                    <div>
                        <p class="text-xs font-medium uppercase text-slate-400">
                            Employee
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            {{ leaveRequest.employee.name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase text-slate-400">
                            Employee Number
                        </p>

                        <p class="mt-1 text-sm text-slate-700">
                            {{ leaveRequest.employee.employee_number || '—' }}
                        </p>
                    </div>

                </div>
            </div>

            <!-- Leave -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="font-semibold text-slate-900">
                    Leave Details
                </h2>

                <div class="mt-5 space-y-4">

                    <div>
                        <p class="text-xs font-medium uppercase text-slate-400">
                            Leave Type
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            {{ leaveRequest.leave_type.name }}
                        </p>

                        <p
                            v-if="leaveRequest.leave_type.code"
                            class="text-xs text-slate-500"
                        >
                            {{ leaveRequest.leave_type.code }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">

                        <div>
                            <p class="text-xs font-medium uppercase text-slate-400">
                                Start Date
                            </p>

                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ leaveRequest.start_date }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase text-slate-400">
                                End Date
                            </p>

                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ leaveRequest.end_date }}
                            </p>
                        </div>

                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase text-slate-400">
                            Duration
                        </p>

                        <p class="mt-1 text-2xl font-bold text-blue-600">
                            {{ leaveRequest.days }}
                            <span class="text-sm font-medium text-slate-500">
                                day(s)
                            </span>
                        </p>
                    </div>

                    <div v-if="leaveRequest.return_date">
                        <p class="text-xs font-medium uppercase text-slate-400">
                            Return Date
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-900">
                            {{ leaveRequest.return_date }}
                        </p>
                    </div>

                </div>
            </div>

            <!-- Policy -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="font-semibold text-slate-900">
                    Leave Policy
                </h2>

                <div
                    v-if="leaveRequest.policy"
                    class="mt-5 space-y-4"
                >

                    <div>
                        <p class="text-xs font-medium uppercase text-slate-400">
                            Entitlement
                        </p>

                        <p class="mt-1 text-lg font-semibold text-slate-900">
                            {{ leaveRequest.policy.entitlement_days }} days
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase text-slate-400">
                            Accrual Method
                        </p>

                        <p class="mt-1 text-sm capitalize text-slate-700">
                            {{ leaveRequest.policy.accrual_method }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase text-slate-400">
                            Carry Forward
                        </p>

                        <p class="mt-1 text-sm text-slate-700">
                            {{
                                leaveRequest.policy.carry_forward_allowed
                                    ? 'Allowed'
                                    : 'Not allowed'
                            }}
                        </p>
                    </div>

                </div>

                <p
                    v-else
                    class="mt-5 text-sm text-slate-500"
                >
                    No policy information available.
                </p>

            </div>

        </div>

        <!-- Leave Balance -->
<div
    v-if="balance"
    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
>
    <div
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h2 class="font-semibold text-slate-900">
                Leave Balance
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                {{ leaveRequest.leave_type.name }}
                balance for {{ balance.year }}
            </p>
        </div>

        <div
            class="rounded-xl bg-blue-50 px-5 py-3 text-right"
        >
            <p class="text-xs font-medium uppercase tracking-wide text-blue-600">
                Available
            </p>

            <p class="mt-1 text-2xl font-bold text-blue-700">
                {{ formatNumber(balance.available_days) }}
                <span class="text-sm font-medium">
                    days
                </span>
            </p>
        </div>
    </div>

    <div
        class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-7"
    >
        <div>
            <p class="text-xs font-medium uppercase text-slate-400">
                Entitled
            </p>

            <p class="mt-1 text-lg font-semibold text-slate-900">
                {{ formatNumber(balance.entitled_days) }}
            </p>
        </div>

        <div>
            <p class="text-xs font-medium uppercase text-slate-400">
                Accrued
            </p>

            <p class="mt-1 text-lg font-semibold text-slate-900">
                {{ formatNumber(balance.accrued_days) }}
            </p>
        </div>

        <div>
            <p class="text-xs font-medium uppercase text-slate-400">
                Carried Forward
            </p>

            <p class="mt-1 text-lg font-semibold text-slate-900">
                {{ formatNumber(balance.carried_forward_days) }}
            </p>
        </div>

        <div>
            <p class="text-xs font-medium uppercase text-slate-400">
                Used
            </p>

            <p class="mt-1 text-lg font-semibold text-red-600">
                {{ formatNumber(balance.used_days) }}
            </p>
        </div>

        <div>
            <p class="text-xs font-medium uppercase text-slate-400">
                Pending
            </p>

            <p class="mt-1 text-lg font-semibold text-amber-600">
                {{ formatNumber(balance.pending_days) }}
            </p>
        </div>

        <div>
            <p class="text-xs font-medium uppercase text-slate-400">
                Adjusted
            </p>

            <p class="mt-1 text-lg font-semibold text-slate-900">
                {{ formatNumber(balance.adjusted_days) }}
            </p>
        </div>

        <div>
            <p class="text-xs font-medium uppercase text-slate-400">
                Available
            </p>

            <p class="mt-1 text-lg font-bold text-emerald-600">
                {{ formatNumber(balance.available_days) }}
            </p>
        </div>
    </div>
</div>

<!-- Balance Audit History -->
<div
    v-if="balance"
    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
>
    <div>
        <h2 class="font-semibold text-slate-900">
            Balance History
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Complete audit trail for this employee's
            {{ leaveRequest.leave_type.name }} balance.
        </p>
    </div>

    <div
        v-if="balance_transactions.length"
        class="mt-6 divide-y divide-slate-100"
    >
        <div
            v-for="transaction in balance_transactions"
            :key="transaction.id"
            class="py-5 first:pt-0 last:pb-0"
        >
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"
                        >
                            {{
                                formatTransactionType(
                                    transaction.transaction_type
                                )
                            }}
                        </span>

                        <span
                            v-if="transaction.created_at"
                            class="text-xs text-slate-400"
                        >
                            {{ transaction.created_at }}
                        </span>
                    </div>

                    <p
                        v-if="transaction.description"
                        class="mt-2 text-sm font-medium text-slate-900"
                    >
                        {{ transaction.description }}
                    </p>

                    <p
                        v-if="transaction.reason"
                        class="mt-1 whitespace-pre-line text-sm text-slate-500"
                    >
                        {{ transaction.reason }}
                    </p>

                    <p
                        v-if="transaction.created_by"
                        class="mt-2 text-xs text-slate-400"
                    >
                        By {{ transaction.created_by }}
                    </p>
                </div>

                <div
                    class="flex shrink-0 flex-col items-start lg:items-end"
                >
                    <p
                        class="text-lg font-bold"
                        :class="transactionDeltaClass(transaction)"
                    >
                        {{ transactionDeltaLabel(transaction) }}
                        <span class="text-xs font-medium text-slate-400">
                            days
                        </span>
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        {{ formatNumber(transaction.balance_before) }}
                        →
                        {{ formatNumber(transaction.balance_after) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div
        v-else
        class="mt-6 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center"
    >
        <p class="text-sm font-medium text-slate-600">
            No balance transactions recorded.
        </p>

        <p class="mt-1 text-xs text-slate-400">
            Balance changes will appear here as they occur.
        </p>
    </div>
</div>

        <!-- Reason / Notes -->
        <div class="grid gap-6 lg:grid-cols-2">

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="font-semibold text-slate-900">
                    Reason
                </h2>

                <p
                    v-if="leaveRequest.reason"
                    class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600"
                >
                    {{ leaveRequest.reason }}
                </p>

                <p
                    v-else
                    class="mt-4 text-sm text-slate-400"
                >
                    No reason provided.
                </p>

            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="font-semibold text-slate-900">
                    Notes
                </h2>

                <p
                    v-if="leaveRequest.notes"
                    class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600"
                >
                    {{ leaveRequest.notes }}
                </p>

                <p
                    v-else
                    class="mt-4 text-sm text-slate-400"
                >
                    No notes provided.
                </p>

            </div>

        </div>

        <!-- Attachment -->
        <div
            v-if="leaveRequest.attachment_url"
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            <div class="flex items-center justify-between gap-4">

                <div>
                    <h2 class="font-semibold text-slate-900">
                        Attachment
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Supporting document attached to this request.
                    </p>
                </div>

                <a
                    :href="leaveRequest.attachment_url"
                    target="_blank"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    View Attachment
                </a>

            </div>
        </div>

        <!-- Approval information -->
        <div
            v-if="
                leaveRequest.approved_by ||
                leaveRequest.rejection_reason ||
                leaveRequest.cancelled_by
            "
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >

            <h2 class="font-semibold text-slate-900">
                Workflow History
            </h2>

            <div class="mt-5 space-y-4">

                <div v-if="leaveRequest.approved_by">
                    <p class="text-xs font-medium uppercase text-slate-400">
                        Approved By
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ leaveRequest.approved_by }}
                        <span v-if="leaveRequest.approved_at">
                            · {{ leaveRequest.approved_at }}
                        </span>
                    </p>
                </div>

                <div v-if="leaveRequest.rejection_reason">
                    <p class="text-xs font-medium uppercase text-slate-400">
                        Rejection Reason
                    </p>

                    <p class="mt-1 whitespace-pre-line text-sm text-red-600">
                        {{ leaveRequest.rejection_reason }}
                    </p>
                </div>

                <div v-if="leaveRequest.cancelled_by">
                    <p class="text-xs font-medium uppercase text-slate-400">
                        Cancelled By
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ leaveRequest.cancelled_by }}

                        <span v-if="leaveRequest.cancelled_at">
                            · {{ leaveRequest.cancelled_at }}
                        </span>
                    </p>
                </div>

            </div>
        </div>

    </div>
</template>