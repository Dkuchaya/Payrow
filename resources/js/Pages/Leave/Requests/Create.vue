<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Company {
    id: number
    name: string
}

interface Employee {
    id: number
    employee_number: string | null
    first_name: string
    middle_name: string | null
    last_name: string
}

interface LeaveType {
    id: number
    name: string
    code: string
    description: string | null
    is_paid: boolean
    requires_balance: boolean
    requires_attachment: boolean
}

interface Policy {
    id: number
    entitlement_days: number
    accrual_method: string
    carry_forward_allowed: boolean
    max_carry_forward_days: number | null
    min_notice_days: number
    max_consecutive_days: number | null
    allow_negative_balance: boolean
    requires_attachment: boolean
}

interface Balance {
    entitled_days: number
    accrued_days: number
    carried_forward_days: number
    used_days: number
    adjusted_days: number
    pending_days: number
    available_days: number
}

const props = defineProps<{
    company: Company
    employees: Employee[]
    leaveTypes: LeaveType[]
}>()

const form = useForm({
    employee_id: '',
    leave_type_id: '',
    start_date: '',
    end_date: '',
    reason: '',
    notes: '',
    attachment: null as File | null,
})

const loadingOptions = ref(false)
const policy = ref<Policy | null>(null)
const balance = ref<Balance | null>(null)
const optionsMessage = ref('')
const attachmentInput = ref<HTMLInputElement | null>(null)

const employee = computed(() => {
    return props.employees.find(
        item => String(item.id) === String(form.employee_id)
    )
})

const leaveType = computed(() => {
    return props.leaveTypes.find(
        item => String(item.id) === String(form.leave_type_id)
    )
})

const employeeName = (item: Employee) => {
    return [
        item.first_name,
        item.middle_name,
        item.last_name,
    ]
        .filter(Boolean)
        .join(' ')
}

const calculatedDays = computed(() => {
    if (!form.start_date || !form.end_date) {
        return 0
    }

    const start = new Date(`${form.start_date}T00:00:00`)
    const end = new Date(`${form.end_date}T00:00:00`)

    const difference = end.getTime() - start.getTime()

    if (difference < 0) {
        return 0
    }

    return Math.floor(
        difference / (1000 * 60 * 60 * 24)
    ) + 1
})

const remainingAfterRequest = computed(() => {
    if (!balance.value) {
        return null
    }

    return balance.value.available_days - calculatedDays.value
})

const attachmentRequired = computed(() => {
    return !!(
        leaveType.value?.requires_attachment ||
        policy.value?.requires_attachment
    )
})

const loadOptions = async () => {
    if (
        !form.employee_id ||
        !form.leave_type_id ||
        !form.start_date
    ) {
        policy.value = null
        balance.value = null
        optionsMessage.value = ''
        return
    }

    loadingOptions.value = true
    optionsMessage.value = ''

    try {
        const params = new URLSearchParams({
            employee_id: String(form.employee_id),
            leave_type_id: String(form.leave_type_id),
            start_date: form.start_date,
        })

        const response = await fetch(
            `${route('leave.requests.lookup-options')}?${params.toString()}`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            }
        )

        const data = await response.json()

        if (!response.ok) {
            throw new Error(
                data.message || 'Unable to load leave information.'
            )
        }

        policy.value = data.policy
        balance.value = data.balance

        if (!data.policy) {
            optionsMessage.value =
                data.message ||
                'No active leave policy is available.'
        } else if (
            leaveType.value?.requires_balance &&
            !data.balance
        ) {
            optionsMessage.value =
                'This employee does not have a leave balance for the selected year.'
        }
    } catch (error) {
        policy.value = null
        balance.value = null

        optionsMessage.value =
            error instanceof Error
                ? error.message
                : 'Unable to load leave information.'
    } finally {
        loadingOptions.value = false
    }
}

watch(
    [
        () => form.employee_id,
        () => form.leave_type_id,
        () => form.start_date,
    ],
    () => {
        loadOptions()
    }
)

watch(
    () => form.start_date,
    value => {
        if (value && !form.end_date) {
            form.end_date = value
        }
    }
)

const onAttachmentChange = (
    event: Event
) => {
    const target = event.target as HTMLInputElement

    form.attachment =
        target.files?.[0] ?? null
}

const removeAttachment = () => {
    form.attachment = null

    if (attachmentInput.value) {
        attachmentInput.value.value = ''
    }
}

const submit = () => {
    if (!policy.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Leave policy required',
            text: 'There is no active leave policy for the selected leave type and date.',
        })

        return
    }

    if (
        leaveType.value?.requires_balance &&
        !balance.value
    ) {
        Swal.fire({
            icon: 'warning',
            title: 'Leave balance required',
            text: 'This employee does not have a leave balance for the selected year.',
        })

        return
    }

    if (
        balance.value &&
        !policy.value.allow_negative_balance &&
        calculatedDays.value > balance.value.available_days
    ) {
        Swal.fire({
            icon: 'warning',
            title: 'Insufficient leave balance',
            text: `Available balance is ${balance.value.available_days} days.`,
        })

        return
    }

    form.post(
        route('leave.requests.store'),
        {
            forceFormData: true,

            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Leave request submitted',
                    text: 'The leave request has been submitted for approval.',
                    timer: 1800,
                    showConfirmButton: false,
                })
            },

            onError: errors => {
                const firstError =
                    Object.values(errors)[0]

                if (firstError) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to submit',
                        text: String(firstError),
                    })
                }
            },
        }
    )
}

const cancel = () => {
    router.visit(
        route('leave.requests.index')
    )
}

const formatNumber = (
    value: number | null | undefined
) => {
    if (value === null || value === undefined) {
        return '0'
    }

    return Number(value).toFixed(2)
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <div class="p-6">

            <!-- Header -->
            <div class="mb-6">
                <button
                    type="button"
                    @click="cancel"
                    class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-800"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>

                    Back to Leave Requests
                </button>

                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        Create Leave Request
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Submit an employee leave request for {{ company.name }}.
                    </p>
                </div>
            </div>

            <form
                @submit.prevent="submit"
                class="mx-auto max-w-5xl"
            >
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                    <!-- Main form -->
                    <div class="space-y-6 lg:col-span-2">

                        <!-- Employee & Leave -->
                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 px-6 py-4">
                                <h2 class="font-semibold text-gray-900">
                                    Leave Details
                                </h2>

                                <p class="mt-1 text-sm text-gray-500">
                                    Select the employee and leave type.
                                </p>
                            </div>

                            <div class="space-y-5 p-6">

                                <!-- Employee -->
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Employee
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <select
                                        v-model="form.employee_id"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                        :class="{
                                            'border-red-500': form.errors.employee_id
                                        }"
                                    >
                                        <option value="">
                                            Select employee
                                        </option>

                                        <option
                                            v-for="item in employees"
                                            :key="item.id"
                                            :value="String(item.id)"
                                        >
                                            {{ employeeName(item) }}
                                            <template v-if="item.employee_number">
                                                — {{ item.employee_number }}
                                            </template>
                                        </option>
                                    </select>

                                    <p
                                        v-if="form.errors.employee_id"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ form.errors.employee_id }}
                                    </p>
                                </div>

                                <!-- Leave Type -->
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Leave Type
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <select
                                        v-model="form.leave_type_id"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                        :class="{
                                            'border-red-500': form.errors.leave_type_id
                                        }"
                                    >
                                        <option value="">
                                            Select leave type
                                        </option>

                                        <option
                                            v-for="type in leaveTypes"
                                            :key="type.id"
                                            :value="String(type.id)"
                                        >
                                            {{ type.name }} ({{ type.code }})
                                        </option>
                                    </select>

                                    <p
                                        v-if="leaveType?.description"
                                        class="mt-1.5 text-xs text-gray-500"
                                    >
                                        {{ leaveType.description }}
                                    </p>

                                    <p
                                        v-if="form.errors.leave_type_id"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ form.errors.leave_type_id }}
                                    </p>
                                </div>

                                <!-- Dates -->
                                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                            Start Date
                                            <span class="text-red-500">*</span>
                                        </label>

                                        <input
                                            v-model="form.start_date"
                                            type="date"
                                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                            :class="{
                                                'border-red-500': form.errors.start_date
                                            }"
                                        />

                                        <p
                                            v-if="form.errors.start_date"
                                            class="mt-1 text-xs text-red-600"
                                        >
                                            {{ form.errors.start_date }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                            End Date
                                            <span class="text-red-500">*</span>
                                        </label>

                                        <input
                                            v-model="form.end_date"
                                            type="date"
                                            :min="form.start_date"
                                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                            :class="{
                                                'border-red-500': form.errors.end_date
                                            }"
                                        />

                                        <p
                                            v-if="form.errors.end_date"
                                            class="mt-1 text-xs text-red-600"
                                        >
                                            {{ form.errors.end_date }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Days -->
                                <div
                                    v-if="calculatedDays > 0"
                                    class="rounded-lg bg-blue-50 p-4"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-blue-800">
                                            Requested Leave
                                        </span>

                                        <span class="text-lg font-bold text-blue-700">
                                            {{ calculatedDays }}
                                            {{ calculatedDays === 1 ? 'day' : 'days' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Reason -->
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Reason
                                    </label>

                                    <textarea
                                        v-model="form.reason"
                                        rows="4"
                                        placeholder="Enter the reason for this leave request..."
                                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />

                                    <p
                                        v-if="form.errors.reason"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ form.errors.reason }}
                                    </p>
                                </div>

                                <!-- Notes -->
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Notes
                                    </label>

                                    <textarea
                                        v-model="form.notes"
                                        rows="3"
                                        placeholder="Additional notes..."
                                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                </div>

                                <!-- Attachment -->
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Supporting Document

                                        <span
                                            v-if="attachmentRequired"
                                            class="text-red-500"
                                        >
                                            *
                                        </span>
                                    </label>

                                    <input
                                        ref="attachmentInput"
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                        @change="onAttachmentChange"
                                        class="block w-full rounded-lg border border-gray-300 text-sm text-gray-600 file:mr-4 file:border-0 file:bg-gray-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-100"
                                    />

                                    <div
                                        v-if="form.attachment"
                                        class="mt-2 flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2"
                                    >
                                        <span class="truncate text-sm text-gray-700">
                                            {{ form.attachment.name }}
                                        </span>

                                        <button
                                            type="button"
                                            @click="removeAttachment"
                                            class="ml-3 text-sm font-medium text-red-600 hover:text-red-700"
                                        >
                                            Remove
                                        </button>
                                    </div>

                                    <p class="mt-1 text-xs text-gray-500">
                                        PDF, JPG, PNG, DOC or DOCX. Maximum 10MB.
                                    </p>

                                    <p
                                        v-if="form.errors.attachment"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ form.errors.attachment }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                @click="cancel"
                                class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing || loadingOptions"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg
                                    v-if="form.processing"
                                    class="h-4 w-4 animate-spin"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />

                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                    />
                                </svg>

                                {{ form.processing ? 'Submitting...' : 'Submit Leave Request' }}
                            </button>
                        </div>
                    </div>

                    <!-- Right sidebar -->
                    <div class="space-y-6">

                        <!-- Policy -->
                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 px-5 py-4">
                                <h2 class="font-semibold text-gray-900">
                                    Leave Policy
                                </h2>
                            </div>

                            <div class="p-5">

                                <div
                                    v-if="loadingOptions"
                                    class="flex items-center gap-2 text-sm text-gray-500"
                                >
                                    <svg
                                        class="h-4 w-4 animate-spin"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        />

                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                        />
                                    </svg>

                                    Loading policy...
                                </div>

                                <div
                                    v-else-if="policy"
                                    class="space-y-4"
                                >
                                    <div>
                                        <p class="text-xs text-gray-500">
                                            Annual Entitlement
                                        </p>

                                        <p class="mt-1 text-lg font-semibold text-gray-900">
                                            {{ formatNumber(policy.entitlement_days) }} days
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs text-gray-500">
                                                Accrual
                                            </p>

                                            <p class="mt-1 text-sm font-medium capitalize text-gray-800">
                                                {{ policy.accrual_method.replaceAll('_', ' ') }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-gray-500">
                                                Notice
                                            </p>

                                            <p class="mt-1 text-sm font-medium text-gray-800">
                                                {{ policy.min_notice_days }} days
                                            </p>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-500">
                                            Carry Forward
                                        </p>

                                        <p class="mt-1 text-sm font-medium text-gray-800">
                                            {{ policy.carry_forward_allowed ? 'Allowed' : 'Not allowed' }}
                                        </p>
                                    </div>

                                    <div
                                        v-if="policy.max_consecutive_days"
                                    >
                                        <p class="text-xs text-gray-500">
                                            Maximum Consecutive Days
                                        </p>

                                        <p class="mt-1 text-sm font-medium text-gray-800">
                                            {{ policy.max_consecutive_days }} days
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-else-if="optionsMessage"
                                    class="rounded-lg bg-red-50 p-4 text-sm text-red-700"
                                >
                                    {{ optionsMessage }}
                                </div>

                                <p
                                    v-else
                                    class="text-sm text-gray-500"
                                >
                                    Select an employee, leave type and start date to view the applicable policy.
                                </p>
                            </div>
                        </div>

                        <!-- Balance -->
                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 px-5 py-4">
                                <h2 class="font-semibold text-gray-900">
                                    Leave Balance
                                </h2>
                            </div>

                            <div class="p-5">

                                <div
                                    v-if="balance"
                                    class="space-y-4"
                                >
                                    <div class="rounded-lg bg-green-50 p-4">
                                        <p class="text-xs font-medium text-green-700">
                                            Available Balance
                                        </p>

                                        <p class="mt-1 text-3xl font-bold text-green-700">
                                            {{ formatNumber(balance.available_days) }}
                                        </p>

                                        <p class="mt-1 text-xs text-green-600">
                                            days available
                                        </p>
                                    </div>

                                    <div class="space-y-3">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">
                                                Entitled
                                            </span>

                                            <span class="font-medium text-gray-800">
                                                {{ formatNumber(balance.entitled_days) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">
                                                Accrued
                                            </span>

                                            <span class="font-medium text-gray-800">
                                                {{ formatNumber(balance.accrued_days) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">
                                                Carried Forward
                                            </span>

                                            <span class="font-medium text-gray-800">
                                                {{ formatNumber(balance.carried_forward_days) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">
                                                Used
                                            </span>

                                            <span class="font-medium text-gray-800">
                                                {{ formatNumber(balance.used_days) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">
                                                Pending
                                            </span>

                                            <span class="font-medium text-yellow-600">
                                                {{ formatNumber(balance.pending_days) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        v-if="calculatedDays > 0"
                                        class="border-t border-gray-200 pt-4"
                                    >
                                        <div class="flex justify-between text-sm">
                                            <span class="font-medium text-gray-700">
                                                After Request
                                            </span>

                                            <span
                                                class="font-bold"
                                                :class="
                                                    remainingAfterRequest !== null &&
                                                    remainingAfterRequest < 0
                                                        ? 'text-red-600'
                                                        : 'text-blue-600'
                                                "
                                            >
                                                {{ formatNumber(remainingAfterRequest) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else-if="leaveType?.requires_balance"
                                    class="text-sm text-gray-500"
                                >
                                    Select the employee, leave type and start date to load the employee's balance.
                                </div>

                                <div
                                    v-else-if="leaveType"
                                    class="rounded-lg bg-blue-50 p-4 text-sm text-blue-700"
                                >
                                    This leave type does not require a leave balance.
                                </div>

                                <p
                                    v-else
                                    class="text-sm text-gray-500"
                                >
                                    Leave balance information will appear here.
                                </p>
                            </div>
                        </div>

                        <!-- Validation message -->
                        <div
                            v-if="optionsMessage && policy"
                            class="rounded-xl border border-yellow-200 bg-yellow-50 p-4"
                        >
                            <p class="text-sm font-medium text-yellow-800">
                                Attention
                            </p>

                            <p class="mt-1 text-sm text-yellow-700">
                                {{ optionsMessage }}
                            </p>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
</template>