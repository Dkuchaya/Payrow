<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface LeaveType {
    id: number
    name: string
    code: string
    is_paid: boolean
    requires_balance: boolean
    requires_attachment: boolean
    is_active: boolean
}

interface LeavePolicy {
    id: number
    leave_type_id: number
    leave_type: LeaveType | null
    effective_from: string
    effective_to: string | null
    entitlement_days: number
    accrual_method: string
    accrual_days_per_period: number | null
    carry_forward_allowed: boolean
    max_carry_forward_days: number | null
    min_notice_days: number
    max_consecutive_days: number | null
    allow_negative_balance: boolean
    requires_attachment: boolean
    is_active: boolean
}

interface Company {
    id: number
    name: string
}

const props = defineProps<{
    policies: LeavePolicy[]
    leaveTypes: LeaveType[]
    company: Company
}>()

const page = usePage<any>()

const search = ref('')
const showModal = ref(false)
const editing = ref<LeavePolicy | null>(null)

const form = useForm({
    leave_type_id: '',
    effective_from: '',
    effective_to: '',
    entitlement_days: 0,
    accrual_method: 'annual',
    accrual_days_per_period: null as number | null,
    carry_forward_allowed: false,
    max_carry_forward_days: null as number | null,
    min_notice_days: 0,
    max_consecutive_days: null as number | null,
    allow_negative_balance: false,
    requires_attachment: false,
    is_active: true,
})

const permissions = computed<string[]>(() => {
    return page.props.auth?.permissions ?? []
})

const isSuperAdmin = computed(() => {
    return page.props.auth?.user?.is_super_admin === true
})

const canManage = computed(() => {
    return (
        isSuperAdmin.value ||
        permissions.value.includes('leave.manage')
    )
})

const filteredPolicies = computed(() => {
    const term = search.value.trim().toLowerCase()

    if (!term) {
        return props.policies
    }

    return props.policies.filter((policy) => {
        return (
            policy.leave_type?.name
                ?.toLowerCase()
                .includes(term) ||
            policy.leave_type?.code
                ?.toLowerCase()
                .includes(term) ||
            policy.accrual_method
                .toLowerCase()
                .includes(term)
        )
    })
})

const totalPolicies = computed(() => props.policies.length)

const activePolicies = computed(() => {
    return props.policies.filter(
        (policy) => policy.is_active
    ).length
})

const inactivePolicies = computed(() => {
    return props.policies.filter(
        (policy) => !policy.is_active
    ).length
})

const carryForwardPolicies = computed(() => {
    return props.policies.filter(
        (policy) => policy.carry_forward_allowed
    ).length
})

const accrualMethods = [
    {
        value: 'annual',
        label: 'Annual',
    },
    {
        value: 'monthly',
        label: 'Monthly',
    },
    {
        value: 'quarterly',
        label: 'Quarterly',
    },
    {
        value: 'per_payroll',
        label: 'Per Payroll',
    },
    {
        value: 'none',
        label: 'None',
    },
]

const accrualMethodLabel = (method: string) => {
    return (
        accrualMethods.find(
            (item) => item.value === method
        )?.label ?? method
    )
}

function openCreateModal() {
    editing.value = null

    form.reset()

    form.leave_type_id = ''
    form.effective_from = new Date()
        .toISOString()
        .substring(0, 10)
    form.effective_to = ''
    form.entitlement_days = 0
    form.accrual_method = 'annual'
    form.accrual_days_per_period = null
    form.carry_forward_allowed = false
    form.max_carry_forward_days = null
    form.min_notice_days = 0
    form.max_consecutive_days = null
    form.allow_negative_balance = false
    form.requires_attachment = false
    form.is_active = true

    form.clearErrors()

    showModal.value = true
}

function openEditModal(policy: LeavePolicy) {
    editing.value = policy

    form.leave_type_id = String(policy.leave_type_id)
    form.effective_from = policy.effective_from
    form.effective_to = policy.effective_to ?? ''
    form.entitlement_days = policy.entitlement_days
    form.accrual_method = policy.accrual_method
    form.accrual_days_per_period =
        policy.accrual_days_per_period
    form.carry_forward_allowed =
        policy.carry_forward_allowed
    form.max_carry_forward_days =
        policy.max_carry_forward_days
    form.min_notice_days = policy.min_notice_days
    form.max_consecutive_days =
        policy.max_consecutive_days
    form.allow_negative_balance =
        policy.allow_negative_balance
    form.requires_attachment =
        policy.requires_attachment
    form.is_active = policy.is_active

    form.clearErrors()

    showModal.value = true
}

function closeModal() {
    if (form.processing) {
        return
    }

    showModal.value = false
    editing.value = null
    form.clearErrors()
}

function submit() {
    if (editing.value) {
        form.put(
            route(
                'leave.policies.update',
                editing.value.id
            ),
            {
                preserveScroll: true,

                onSuccess: () => {
                    closeModal()

                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: 'Leave policy updated successfully.',
                        timer: 1800,
                        showConfirmButton: false,
                    })
                },
            }
        )

        return
    }

    form.post(route('leave.policies.store'), {
        preserveScroll: true,

        onSuccess: () => {
            closeModal()

            Swal.fire({
                icon: 'success',
                title: 'Created',
                text: 'Leave policy created successfully.',
                timer: 1800,
                showConfirmButton: false,
            })
        },
    })
}

function toggleStatus(policy: LeavePolicy) {
    const action = policy.is_active
        ? 'deactivate'
        : 'activate'

    Swal.fire({
        title: `${action.charAt(0).toUpperCase() + action.slice(1)} policy?`,
        text: `This will ${action} the policy for ${policy.leave_type?.name ?? 'this leave type'}.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: `Yes, ${action}`,
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#2563eb',
    }).then((result) => {
        if (!result.isConfirmed) {
            return
        }

        router.patch(
            route(
                'leave.policies.toggle-status',
                policy.id
            ),
            {},
            {
                preserveScroll: true,

                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: `Leave policy ${action}d successfully.`,
                        timer: 1500,
                        showConfirmButton: false,
                    })
                },
            }
        )
    })
}

function deletePolicy(policy: LeavePolicy) {
    Swal.fire({
        title: 'Delete leave policy?',
        text: 'This policy can only be deleted if it has not been used by balances or leave requests.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
    }).then((result) => {
        if (!result.isConfirmed) {
            return
        }

        router.delete(
            route(
                'leave.policies.destroy',
                policy.id
            ),
            {
                preserveScroll: true,

                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted',
                        text: 'Leave policy deleted successfully.',
                        timer: 1500,
                        showConfirmButton: false,
                    })
                },
            }
        )
    })
}
</script>

<template>
    <Head title="Leave Policies" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">
            Leave Policies
        </h1>

        <p class="mt-1 text-sm text-gray-500">
            Configure leave entitlement, accrual, carry-forward and request rules for {{ company.name }}.
        </p>
    </div>

    <button
        type="button"
        @click="openCreateModal"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 4v16m8-8H4"
            />
        </svg>

        Create Leave Policy
    </button>
</div>

        <!-- Statistics -->
        <div
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Total Policies
                        </p>

                        <p
                            class="mt-2 text-3xl font-bold text-gray-900"
                        >
                            {{ totalPolicies }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-blue-600"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 3h8a1 1 0 110 2H6a1 1 0 110-2zm0 4h8a1 1 0 110 2H6a1 1 0 110-2zm0 4h5a1 1 0 110 2H6a1 1 0 110-2z"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Active
                        </p>

                        <p
                            class="mt-2 text-3xl font-bold text-green-600"
                        >
                            {{ activePolicies }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg bg-green-50 text-green-600"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.172 7.707 8.879a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l3-3z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Inactive
                        </p>

                        <p
                            class="mt-2 text-3xl font-bold text-gray-600"
                        >
                            {{ inactivePolicies }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg bg-gray-100 text-gray-500"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Carry Forward
                        </p>

                        <p
                            class="mt-2 text-3xl font-bold text-purple-600"
                        >
                            {{ carryForwardPolicies }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg bg-purple-50 text-purple-600"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M4 4a2 2 0 012-2h6a2 2 0 012 2v1h1a2 2 0 012 2v7a2 2 0 01-2 2h-3v-2h3V7H6v7h3v2H6a2 2 0 01-2-2V7a2 2 0 012-2h1V4H6v1H4V4zm4 0v1h4V4H8z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
        >
            <!-- Toolbar -->
            <div
                class="flex flex-col gap-3 border-b border-gray-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="relative w-full sm:max-w-md">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.817-4.817A6 6 0 012 8z"
                            clip-rule="evenodd"
                        />
                    </svg>

                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search policies..."
                        class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                    />
                </div>

                <div
                    v-if="search"
                    class="text-sm text-gray-500"
                >
                    {{ filteredPolicies.length }}
                    result{{ filteredPolicies.length === 1 ? '' : 's' }}
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="filteredPolicies.length === 0"
                class="px-6 py-16 text-center"
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-8 w-8 text-gray-400"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            d="M5 3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5zm3 3h4a1 1 0 110 2H8a1 1 0 110-2zm0 4h4a1 1 0 110 2H8a1 1 0 110-2zm0 4h2a1 1 0 110 2H8a1 1 0 110-2z"
                        />
                    </svg>
                </div>

                <h3
                    class="mt-4 text-base font-semibold text-gray-900"
                >
                    No leave policies found
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    {{
                        search
                            ? 'Try changing your search.'
                            : 'Create your first leave policy to get started.'
                    }}
                </p>

                <button
                    v-if="canManage && !search"
                    type="button"
                    class="mt-5 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                    @click="openCreateModal"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                            clip-rule="evenodd"
                        />
                    </svg>

                    Add Policy
                </button>
            </div>

            <!-- Table -->
            <div
                v-else
                class="overflow-x-auto"
            >
                <table
                    class="min-w-full divide-y divide-gray-200"
                >
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Leave Type
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Entitlement
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Accrual
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Carry Forward
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Effective
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Status
                            </th>

                            <th
                                v-if="canManage"
                                class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        class="divide-y divide-gray-200 bg-white"
                    >
                        <tr
                            v-for="policy in filteredPolicies"
                            :key="policy.id"
                            class="transition hover:bg-gray-50"
                        >
                            <!-- Leave Type -->
                            <td class="px-6 py-4">
                                <div
                                    class="font-semibold text-gray-900"
                                >
                                    {{
                                        policy.leave_type?.name ??
                                        'Unknown'
                                    }}
                                </div>

                                <span
                                    v-if="policy.leave_type?.code"
                                    class="mt-1 inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600"
                                >
                                    {{
                                        policy.leave_type.code
                                    }}
                                </span>
                            </td>

                            <!-- Entitlement -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    class="text-sm font-semibold text-gray-900"
                                >
                                    {{
                                        policy.entitlement_days
                                    }}
                                    days
                                </span>
                            </td>

                            <!-- Accrual -->
                            <td class="px-6 py-4">
                                <div
                                    class="text-sm font-medium text-gray-800"
                                >
                                    {{
                                        accrualMethodLabel(
                                            policy.accrual_method
                                        )
                                    }}
                                </div>

                                <div
                                    v-if="
                                        policy.accrual_days_per_period !==
                                        null
                                    "
                                    class="mt-1 text-xs text-gray-500"
                                >
                                    {{
                                        policy.accrual_days_per_period
                                    }}
                                    days / period
                                </div>
                            </td>

                            <!-- Carry Forward -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    v-if="policy.carry_forward_allowed"
                                    class="inline-flex rounded-full bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700"
                                >
                                    {{
                                        policy.max_carry_forward_days !==
                                        null
                                            ? `Up to ${policy.max_carry_forward_days} days`
                                            : 'Allowed'
                                    }}
                                </span>

                                <span
                                    v-else
                                    class="text-sm text-gray-400"
                                >
                                    Not allowed
                                </span>
                            </td>

                            <!-- Effective -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <div
                                    class="text-sm text-gray-800"
                                >
                                    {{ policy.effective_from }}
                                </div>

                                <div
                                    v-if="policy.effective_to"
                                    class="mt-1 text-xs text-gray-500"
                                >
                                    Until
                                    {{
                                        policy.effective_to
                                    }}
                                </div>

                                <div
                                    v-else
                                    class="mt-1 text-xs text-gray-400"
                                >
                                    No end date
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    v-if="policy.is_active"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-green-500"
                                    ></span>

                                    Active
                                </span>

                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-gray-400"
                                    ></span>

                                    Inactive
                                </span>
                            </td>

                            <!-- Actions -->
                            <td
                                v-if="canManage"
                                class="whitespace-nowrap px-6 py-4 text-right"
                            >
                                <div
                                    class="flex justify-end gap-1"
                                >
                                    <button
                                        type="button"
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-blue-50 hover:text-blue-600"
                                        title="Edit"
                                        @click="openEditModal(policy)"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 6.207l2.828 2.828-7.5 7.5H4v-2.707l7.379-7.621z"
                                            />
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-yellow-50 hover:text-yellow-600"
                                        :title="
                                            policy.is_active
                                                ? 'Deactivate'
                                                : 'Activate'
                                        "
                                        @click="toggleStatus(policy)"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                v-if="policy.is_active"
                                                fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v4a1 1 0 00.293.707l2 2a1 1 0 001.414-1.414L11 10.586V7z"
                                                clip-rule="evenodd"
                                            />

                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-4a1 1 0 102 0V9a1 1 0 10-2 0v5zm0-7a1 1 0 102 0V6a1 1 0 10-2 0v1z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-red-50 hover:text-red-600"
                                        title="Delete"
                                        @click="deletePolicy(policy)"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 100 2h.293l.707 10.243A2 2 0 006.994 18h6.012a2 2 0 001.994-1.757L15.707 6H16a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM8 8a1 1 0 012 0v7a1 1 0 11-2 0V8zm4 0a1 1 0 012 0v7a1 1 0 11-2 0V8z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div
        v-if="showModal"
        class="fixed inset-0 z-50 overflow-y-auto"
    >
        <div
            class="flex min-h-screen items-center justify-center px-4 py-8"
        >
            <div
                class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm"
                @click="closeModal"
            ></div>

            <div
                class="relative z-10 w-full max-w-3xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
            >
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between border-b border-gray-200 px-6 py-5"
                >
                    <div>
                        <h2
                            class="text-lg font-bold text-gray-900"
                        >
                            {{
                                editing
                                    ? 'Edit Leave Policy'
                                    : 'Add Leave Policy'
                            }}
                        </h2>

                        <p
                            class="mt-1 text-sm text-gray-500"
                        >
                            Configure entitlement and leave request rules.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                        @click="closeModal"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submit">
                    <div class="max-h-[70vh] space-y-6 overflow-y-auto px-6 py-6">
                        <!-- Leave Type & Dates -->
                        <div>
                            <div class="mb-4">
                                <h3
                                    class="text-sm font-semibold text-gray-900"
                                >
                                    Policy Information
                                </h3>

                                <p
                                    class="mt-1 text-xs text-gray-500"
                                >
                                    Select the leave type and policy period.
                                </p>
                            </div>

                            <div
                                class="grid grid-cols-1 gap-5 sm:grid-cols-2"
                            >
                                <!-- Leave Type -->
                                <div class="sm:col-span-2">
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-gray-700"
                                    >
                                        Leave Type
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <select
                                        v-model="form.leave_type_id"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                                    >
                                        <option value="">
                                            Select leave type
                                        </option>

                                        <option
                                            v-for="leaveType in leaveTypes"
                                            :key="leaveType.id"
                                            :value="leaveType.id"
                                        >
                                            {{ leaveType.name }}
                                            ({{ leaveType.code }})
                                        </option>
                                    </select>

                                    <p
                                        v-if="form.errors.leave_type_id"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{
                                            form.errors.leave_type_id
                                        }}
                                    </p>
                                </div>

                                <!-- Effective From -->
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-gray-700"
                                    >
                                        Effective From
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        v-model="form.effective_from"
                                        type="date"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                                    />

                                    <p
                                        v-if="form.errors.effective_from"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{
                                            form.errors.effective_from
                                        }}
                                    </p>
                                </div>

                                <!-- Effective To -->
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-gray-700"
                                    >
                                        Effective To
                                    </label>

                                    <input
                                        v-model="form.effective_to"
                                        type="date"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                                    />

                                    <p
                                        v-if="form.errors.effective_to"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{
                                            form.errors.effective_to
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Entitlement -->
                        <div
                            class="rounded-xl border border-gray-200 bg-gray-50 p-5"
                        >
                            <div class="mb-4">
                                <h3
                                    class="text-sm font-semibold text-gray-900"
                                >
                                    Entitlement & Accrual
                                </h3>

                                <p
                                    class="mt-1 text-xs text-gray-500"
                                >
                                    Define how employees earn and receive leave
                                    days.
                                </p>
                            </div>

                            <div
                                class="grid grid-cols-1 gap-5 sm:grid-cols-2"
                            >
                                <!-- Entitlement -->
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-gray-700"
                                    >
                                        Entitlement Days
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        v-model.number="form.entitlement_days"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="24"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                                    />

                                    <p
                                        v-if="form.errors.entitlement_days"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{
                                            form.errors.entitlement_days
                                        }}
                                    </p>
                                </div>

                                <!-- Accrual Method -->
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-gray-700"
                                    >
                                        Accrual Method
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <select
                                        v-model="form.accrual_method"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                                    >
                                        <option
                                            v-for="method in accrualMethods"
                                            :key="method.value"
                                            :value="method.value"
                                        >
                                            {{ method.label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Accrual Period -->
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-gray-700"
                                    >
                                        Days Per Period
                                    </label>

                                    <input
                                        v-model.number="
                                            form.accrual_days_per_period
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="2"
                                        :disabled="
                                            form.accrual_method === 'none'
                                        "
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
                                    />

                                    <p
                                        class="mt-1 text-xs text-gray-500"
                                    >
                                        Example: 2 days per month.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Carry Forward -->
                        <div
                            class="rounded-xl border border-gray-200 bg-white"
                        >
                            <label
                                class="flex cursor-pointer items-start gap-3 p-5"
                            >
                                <input
                                    v-model="form.carry_forward_allowed"
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />

                                <span>
                                    <span
                                        class="block text-sm font-semibold text-gray-800"
                                    >
                                        Allow Carry Forward
                                    </span>

                                    <span
                                        class="mt-1 block text-xs text-gray-500"
                                    >
                                        Allow unused leave days to move into
                                        the next leave year.
                                    </span>
                                </span>
                            </label>

                            <div
                                v-if="form.carry_forward_allowed"
                                class="border-t border-gray-200 bg-gray-50 p-5"
                            >
                                <label
                                    class="mb-1.5 block text-sm font-medium text-gray-700"
                                >
                                    Maximum Carry Forward Days
                                </label>

                                <input
                                    v-model.number="
                                        form.max_carry_forward_days
                                    "
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="5"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 sm:max-w-xs"
                                />

                                <p
                                    class="mt-1 text-xs text-gray-500"
                                >
                                    Leave blank if there is no maximum.
                                </p>
                            </div>
                        </div>

                        <!-- Request Rules -->
                        <div
                            class="rounded-xl border border-gray-200 bg-gray-50 p-5"
                        >
                            <div class="mb-4">
                                <h3
                                    class="text-sm font-semibold text-gray-900"
                                >
                                    Request Rules
                                </h3>

                                <p
                                    class="mt-1 text-xs text-gray-500"
                                >
                                    Control how employees can request this
                                    leave.
                                </p>
                            </div>

                            <div
                                class="grid grid-cols-1 gap-5 sm:grid-cols-2"
                            >
                                <!-- Notice -->
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-gray-700"
                                    >
                                        Minimum Notice Days
                                    </label>

                                    <input
                                        v-model.number="form.min_notice_days"
                                        type="number"
                                        min="0"
                                        max="365"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                                    />
                                </div>

                                <!-- Consecutive -->
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-gray-700"
                                    >
                                        Maximum Consecutive Days
                                    </label>

                                    <input
                                        v-model.number="
                                            form.max_consecutive_days
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="15"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                                    />

                                    <p
                                        class="mt-1 text-xs text-gray-500"
                                    >
                                        Leave blank for no limit.
                                    </p>
                                </div>
                            </div>

                            <div
                                class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2"
                            >
                                <!-- Negative -->
                                <label
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-white p-4"
                                >
                                    <input
                                        v-model="form.allow_negative_balance"
                                        type="checkbox"
                                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />

                                    <span>
                                        <span
                                            class="block text-sm font-medium text-gray-800"
                                        >
                                            Allow Negative Balance
                                        </span>

                                        <span
                                            class="mt-1 block text-xs text-gray-500"
                                        >
                                            Allow employees to request more
                                            days than their available balance.
                                        </span>
                                    </span>
                                </label>

                                <!-- Attachment -->
                                <label
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-white p-4"
                                >
                                    <input
                                        v-model="form.requires_attachment"
                                        type="checkbox"
                                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />

                                    <span>
                                        <span
                                            class="block text-sm font-medium text-gray-800"
                                        >
                                            Require Attachment
                                        </span>

                                        <span
                                            class="mt-1 block text-xs text-gray-500"
                                        >
                                            Supporting documentation must be
                                            attached to the request.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Active -->
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-white p-5"
                        >
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />

                            <span>
                                <span
                                    class="block text-sm font-semibold text-gray-800"
                                >
                                    Active Policy
                                </span>

                                <span
                                    class="mt-1 block text-xs text-gray-500"
                                >
                                    Active policies can be used when creating
                                    employee leave balances and requests.
                                </span>
                            </span>
                        </label>
                    </div>

                    <!-- Footer -->
                    <div
                        class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end"
                    >
                        <button
                            type="button"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="form.processing"
                            @click="closeModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            <span
                                v-if="form.processing"
                                class="inline-flex items-center gap-2"
                            >
                                <svg
                                    class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
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
                                    ></circle>

                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                    ></path>
                                </svg>

                                Saving...
                            </span>

                            <span v-else>
                                {{
                                    editing
                                        ? 'Update Policy'
                                        : 'Create Policy'
                                }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>