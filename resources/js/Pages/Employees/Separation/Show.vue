<script setup lang="ts">
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Employee {
    id: number
    employee_number: string
    first_name: string
    middle_name?: string | null
    last_name: string
    status: string
    department?: {
        name: string
    } | null
    position?: {
        name: string
    } | null
}

interface Separation {
    id: number
    separation_type: string
    notice_date?: string | null
    effective_date: string
    last_working_date?: string | null
    reason?: string | null
    notes?: string | null
    status: string
    approved_at?: string | null
    employee: Employee
    creator?: {
        id: number
        name: string
    } | null
    approver?: {
        id: number
        name: string
    } | null
}

const props = defineProps<{
    separation: Separation
    clearanceComplete: boolean
    finalSettlementStatus: string | null
}>()


const employee = computed(() => props.separation.employee)

const fullName = computed(() => {
    return [
        employee.value.first_name,
        employee.value.middle_name,
        employee.value.last_name,
    ]
        .filter(Boolean)
        .join(' ')
})

const workflow = [
    {
        value: 'pending',
        label: 'Pending',
        description: 'Awaiting management approval.',
    },
    {
        value: 'approved',
        label: 'Approved',
        description: 'Separation has been approved.',
    },
    {
        value: 'clearance',
        label: 'Clearance',
        description: 'Employee clearance is in progress.',
    },
    {
        value: 'final_payroll',
        label: 'Final Payroll',
        description: 'Final settlement is being processed.',
    },
    {
        value: 'completed',
        label: 'Completed',
        description: 'Employee separation is complete.',
    },
]

function typeLabel(value: string) {
    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, letter => letter.toUpperCase())
}

function statusLabel(value: string) {
    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, letter => letter.toUpperCase())
}

function statusClass(value: string) {
    const classes: Record<string, string> = {
        pending: 'bg-amber-100 text-amber-800',
        approved: 'bg-blue-100 text-blue-800',
        clearance: 'bg-purple-100 text-purple-800',
        final_payroll: 'bg-indigo-100 text-indigo-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    }

    return classes[value] || 'bg-gray-100 text-gray-700'
}

function formatDate(value?: string | null) {
    if (!value) return '—'

    return new Date(value).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

function formatDateTime(value?: string | null) {
    if (!value) return '—'

    return new Date(value).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

function currentStep() {
    return workflow.findIndex(
        item => item.value === props.separation.status
    )
}

function isCompleted(index: number) {
    return index <= currentStep()
}

function approve() {
    if (!confirm('Approve this separation request?')) return

    router.post(
        `/employee-separations/${props.separation.id}/approve`
    )
}

function startClearance() {
    if (!confirm('Move this employee separation into clearance?')) return

    router.post(
        `/employee-separations/${props.separation.id}/clearance`
    )
}

function sendToFinalPayroll() {
    if (!confirm('Send this separation to final payroll?')) return

    router.post(
        `/employee-separations/${props.separation.id}/final-payroll`
    )
}

function complete() {
    if (
        !confirm(
            'Complete this separation? This will mark the employee as separated.'
        )
    ) {
        return
    }

    router.post(
        `/employee-separations/${props.separation.id}/complete`
    )
}

function cancelSeparation() {
    if (!confirm('Cancel this separation request?')) return

    router.post(
        `/employee-separations/${props.separation.id}/cancel`
    )
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <Link
                        href="/employee-separations"
                        class="hover:text-blue-600"
                    >
                        Separations
                    </Link>

                    <span>/</span>

                    <span>{{ employee.employee_number }}</span>
                </div>

                <h1 class="mt-2 text-2xl font-bold text-gray-900">
                    Employee Separation
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    {{ fullName }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    :href="`/employees/${employee.id}`"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Employee Profile
                </Link>

                <Link
                    href="/employee-separations"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    All Separations
                </Link>
            </div>
        </div>

        <!-- Employee card -->
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">

                <div
                    class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xl font-bold text-blue-700"
                >
                    {{ employee.first_name?.charAt(0) }}
                    {{ employee.last_name?.charAt(0) }}
                </div>

                <div class="flex-1">
                    <h2 class="text-lg font-semibold text-gray-900">
                        {{ fullName }}
                    </h2>

                    <p class="text-sm text-gray-500">
                        {{ employee.employee_number }}
                    </p>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ employee.position?.name || 'No position' }}
                        <span v-if="employee.department">
                            · {{ employee.department.name }}
                        </span>
                    </p>
                </div>

                <div>
                    <span
                        class="inline-flex rounded-full px-3 py-1.5 text-sm font-semibold"
                        :class="statusClass(separation.status)"
                    >
                        {{ statusLabel(separation.status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Workflow -->
        <div class="rounded-xl border bg-white p-6 shadow-sm">

            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900">
                    Separation Workflow
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Track the employee through the separation process.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-5">

                <div
                    v-for="(step, index) in workflow"
                    :key="step.value"
                    class="relative"
                >
                    <div
                        class="rounded-xl border p-4"
                        :class="isCompleted(index)
                            ? 'border-blue-200 bg-blue-50'
                            : 'border-gray-200 bg-gray-50'"
                    >
                        <div class="flex items-center gap-3">

                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                                :class="isCompleted(index)
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-200 text-gray-500'"
                            >
                                <span v-if="index < currentStep()">
                                    ✓
                                </span>

                                <span v-else>
                                    {{ index + 1 }}
                                </span>
                            </div>

                            <div>
                                <p
                                    class="text-sm font-semibold"
                                    :class="isCompleted(index)
                                        ? 'text-blue-900'
                                        : 'text-gray-700'"
                                >
                                    {{ step.label }}
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    {{ step.description }}
                                </p>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Separation details -->
        <div class="grid gap-6 lg:grid-cols-2">

            <div class="rounded-xl border bg-white shadow-sm">

                <div class="border-b px-6 py-4">
                    <h2 class="font-semibold text-gray-900">
                        Separation Details
                    </h2>
                </div>

                <div class="divide-y">

                    <div class="flex justify-between gap-4 px-6 py-4">
                        <span class="text-sm text-gray-500">
                            Separation Type
                        </span>

                        <span class="text-right text-sm font-medium text-gray-900">
                            {{ typeLabel(separation.separation_type) }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4 px-6 py-4">
                        <span class="text-sm text-gray-500">
                            Notice Date
                        </span>

                        <span class="text-right text-sm font-medium text-gray-900">
                            {{ formatDate(separation.notice_date) }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4 px-6 py-4">
                        <span class="text-sm text-gray-500">
                            Effective Date
                        </span>

                        <span class="text-right text-sm font-medium text-gray-900">
                            {{ formatDate(separation.effective_date) }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4 px-6 py-4">
                        <span class="text-sm text-gray-500">
                            Last Working Date
                        </span>

                        <span class="text-right text-sm font-medium text-gray-900">
                            {{ formatDate(separation.last_working_date) }}
                        </span>
                    </div>

                </div>
            </div>

            <!-- Approval -->
            <div class="rounded-xl border bg-white shadow-sm">

                <div class="border-b px-6 py-4">
                    <h2 class="font-semibold text-gray-900">
                        Approval Information
                    </h2>
                </div>

                <div class="divide-y">

                    <div class="flex justify-between gap-4 px-6 py-4">
                        <span class="text-sm text-gray-500">
                            Requested By
                        </span>

                        <span class="text-right text-sm font-medium text-gray-900">
                            {{ separation.creator?.name || '—' }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4 px-6 py-4">
                        <span class="text-sm text-gray-500">
                            Approved By
                        </span>

                        <span class="text-right text-sm font-medium text-gray-900">
                            {{ separation.approver?.name || '—' }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4 px-6 py-4">
                        <span class="text-sm text-gray-500">
                            Approved At
                        </span>

                        <span class="text-right text-sm font-medium text-gray-900">
                            {{ formatDateTime(separation.approved_at) }}
                        </span>
                    </div>

                </div>
            </div>

        </div>

        <!-- Reason / notes -->
        <div class="grid gap-6 lg:grid-cols-2">

            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <h2 class="font-semibold text-gray-900">
                    Reason
                </h2>

                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600">
                    {{ separation.reason || 'No reason provided.' }}
                </p>
            </div>

            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <h2 class="font-semibold text-gray-900">
                    Additional Notes
                </h2>

                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600">
                    {{ separation.notes || 'No additional notes.' }}
                </p>
            </div>

        </div>

        <!-- Actions -->
        <div
            v-if="separation.status !== 'completed' && separation.status !== 'cancelled'"
            class="rounded-xl border bg-white p-6 shadow-sm"
        >
            <h2 class="font-semibold text-gray-900">
                Workflow Actions
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Move this separation to the next stage.
            </p>

   <div class="flex flex-wrap items-center gap-3">

        <!-- APPROVE -->
        <button
            v-if="separation.status === 'pending'"
            type="button"
            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            @click="approve"
        >
            ✓ Approve Separation
        </button>

        <!-- START CLEARANCE -->
        <button
            v-if="separation.status === 'approved'"
            type="button"
            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            @click="startClearance"
        >
            → Start Clearance
        </button>

        <!-- SEND TO FINAL PAYROLL -->
        <button
            v-if="separation.status === 'clearance' && clearanceComplete"
            type="button"
            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            @click="sendToFinalPayroll"
        >
            → Send to Final Payroll
        </button>

        <!-- CLEARANCE INCOMPLETE -->
        <Link
            v-if="separation.status === 'clearance' && !clearanceComplete"
            :href="`/employee-separations/${separation.id}/clearance`"
            class="inline-flex items-center justify-center rounded-lg border border-red-300 bg-white px-5 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50"
        >
            ⚠ Complete Clearance
        </Link>

        <!-- MANAGE CLEARANCE -->
        <Link
            v-if="separation.status === 'clearance'"
            :href="`/employee-separations/${separation.id}/clearance`"
            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
        >
            ✓ Manage Clearance
        </Link>

        <!-- MANAGE FINAL SETTLEMENT -->
        <Link
            v-if="separation.status === 'final_payroll'"
            :href="`/employee-separations/${separation.id}/final-settlement`"
            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
        >
            ✓ Manage Final Settlement
        </Link>

        <!-- COMPLETE SEPARATION -->
       <button
            v-if="
                separation.status === 'final_payroll' &&
                finalSettlementStatus === 'paid'
            "
            type="button"
            class="inline-flex items-center justify-center rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700"
            @click="complete"
        >
            ✓ Complete Separation
        </button>

        <!-- CANCEL -->
        <button
            v-if="
                separation.status !== 'completed' &&
                separation.status !== 'cancelled'
            "
            type="button"
            class="inline-flex items-center justify-center rounded-lg border border-red-300 bg-white px-5 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50"
            @click="cancelSeparation"
        >
            Cancel Separation
        </button>

    </div>
 </div>

        <!-- Completed notice -->
        <div
            v-if="separation.status === 'completed'"
            class="rounded-xl border border-green-200 bg-green-50 p-5"
        >
            <div class="flex gap-3">
                <div class="text-green-600">
                    ✓
                </div>

                <div>
                    <h3 class="font-semibold text-green-900">
                        Separation Completed
                    </h3>

                    <p class="mt-1 text-sm text-green-800">
                        This employee has been officially separated from the company.
                        The employee status is now marked as separated.
                    </p>
                </div>
            </div>
        </div>

        <!-- Cancelled notice -->
        <div
            v-if="separation.status === 'cancelled'"
            class="rounded-xl border border-red-200 bg-red-50 p-5"
        >
            <div class="flex gap-3">
                <div class="text-red-600">
                    !
                </div>

                <div>
                    <h3 class="font-semibold text-red-900">
                        Separation Cancelled
                    </h3>

                    <p class="mt-1 text-sm text-red-800">
                        This separation request has been cancelled and no further
                        workflow actions are available.
                    </p>
                </div>
            </div>
        </div>

        <div
        v-if="separation.status === 'final_payroll'"
        class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3"
    >
        <div class="text-sm font-semibold text-blue-900">
            Final Settlement Status
        </div>

        <div class="mt-1 text-sm text-blue-700">
            {{
                finalSettlementStatus
                    ? finalSettlementStatus
                        .replace('_', ' ')
                        .replace(/\b\w/g, char => char.toUpperCase())
                    : 'Not created'
            }}
        </div>
</div>

    </div>
</template>