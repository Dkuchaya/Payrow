<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Employee {
    id: number
    employee_number: string | null
    name: string
}

interface LeaveType {
    id: number
    name: string
    code: string
}

interface Balance {
    id: number
    employee: Employee | null
    leave_type: LeaveType | null
    year: number
    entitled_days: number
    accrued_days: number
    carried_forward_days: number
    used_days: number
    pending_days: number
    adjusted_days: number
    available_days: number
}

const props = defineProps<{
    company: {
        id: number
        name: string
    }
    balances: Balance[]
    year: number
}>()

const search = ref('')
const selectedYear = ref(props.year)
const showAdjustmentModal = ref(false)
const selectedBalance = ref<Balance | null>(null)
const adjustmentDays = ref('')
const adjustmentReason = ref('')
const processing = ref(false)

const filteredBalances = computed(() => {
    const term = search.value.trim().toLowerCase()

    if (!term) {
        return props.balances
    }

    return props.balances.filter((balance) => {
        const employeeName =
            balance.employee?.name?.toLowerCase() ?? ''

        const employeeNumber =
            balance.employee?.employee_number?.toLowerCase() ?? ''

        const leaveType =
            balance.leave_type?.name?.toLowerCase() ?? ''

        const leaveCode =
            balance.leave_type?.code?.toLowerCase() ?? ''

        return (
            employeeName.includes(term) ||
            employeeNumber.includes(term) ||
            leaveType.includes(term) ||
            leaveCode.includes(term)
        )
    })
})

const statistics = computed(() => {
    const balances = props.balances

    return {
        total: balances.length,

        employees: new Set(
            balances
                .map((balance) => balance.employee?.id)
                .filter(Boolean)
        ).size,

        used: balances.reduce(
            (sum, balance) => sum + Number(balance.used_days || 0),
            0
        ),

        available: balances.reduce(
            (sum, balance) =>
                sum + Number(balance.available_days || 0),
            0
        ),
    }
})

const formatDays = (days: number) => {
    const value = Number(days || 0)

    return value % 1 === 0
        ? value.toFixed(0)
        : value.toFixed(2)
}

const formatNumber = (value: number) => {
    return new Intl.NumberFormat().format(value)
}

const loadYear = () => {
    router.get(
        route('leave.balances.index'),
        {
            year: selectedYear.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}

const generateBalances = () => {
    Swal.fire({
        title: 'Generate leave balances?',
        text: `This will generate balances for ${selectedYear.value} using the company's active leave policies.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Generate Balances',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#2563eb',
    }).then((result) => {
        if (!result.isConfirmed) {
            return
        }

        processing.value = true

        router.post(
            route('leave.balances.generate'),
            {
                year: selectedYear.value,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    processing.value = false
                },
            }
        )
    })
}


function runAccrual() {
    if (processing.value) {
        return
    }

    Swal.fire({
        title: 'Run Leave Accrual?',
        text: `PAYROW will calculate leave accrual for ${props.year}.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Run Accrual',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#4f46e5',
    }).then((result) => {
        if (!result.isConfirmed) {
            return
        }

        processing.value = true

        router.post(
            route('leave.balances.accrue'),
            {
                year: props.year,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    processing.value = false
                },
            }
        )
    })
}

function runCarryForward() {
    if (processing.value) return

    const nextYear = Number(props.year) + 1

    Swal.fire({
        title: 'Run Leave Carry Forward?',
        text: `PAYROW will carry eligible unused leave from ${props.year} into ${nextYear}.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Carry Forward',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#059669',
    }).then((result) => {
        if (!result.isConfirmed) return

        processing.value = true

        router.post(
            route('leave.balances.carry-forward'),
            {
                from_year: props.year,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    processing.value = false
                },
            }
        )
    })
}

const openAdjustment = (balance: Balance) => {
    selectedBalance.value = balance
    adjustmentDays.value = ''
    adjustmentReason.value = ''
    showAdjustmentModal.value = true
}

const closeAdjustment = () => {
    if (processing.value) {
        return
    }

    showAdjustmentModal.value = false
    selectedBalance.value = null
    adjustmentDays.value = ''
    adjustmentReason.value = ''
}

const submitAdjustment = () => {
    if (!selectedBalance.value) {
        return
    }

    const days = Number(adjustmentDays.value)

    if (!adjustmentDays.value || Number.isNaN(days)) {
        Swal.fire({
            icon: 'warning',
            title: 'Adjustment required',
            text: 'Please enter the number of days to adjust.',
        })

        return
    }

    if (!adjustmentReason.value.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Reason required',
            text: 'Please provide a reason for the adjustment.',
        })

        return
    }

    processing.value = true

    router.patch(
        route(
            'leave.balances.adjust',
            selectedBalance.value.id
        ),
        {
            days,
            reason: adjustmentReason.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeAdjustment()
            },
            onFinish: () => {
                processing.value = false
            },
        }
    )
}

const clearSearch = () => {
    search.value = ''
}
</script>

<template>
    <Head title="Leave Balances" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="border-b border-gray-200 bg-white">
            <div class="px-6 py-6">
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div>
                        <h1
                            class="text-2xl font-bold tracking-tight text-gray-900"
                        >
                            Leave Balances
                        </h1>

                        <p class="mt-1 text-sm text-gray-500">
                            Manage employee leave entitlements,
                            accruals and available balances.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Year -->
                        <select
                            v-model="selectedYear"
                            @change="loadYear"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option
                                v-for="year in 7"
                                :key="year"
                                :value="new Date().getFullYear() - 3 + year - 1"
                            >
                                {{ new Date().getFullYear() - 3 + year - 1 }}
                            </option>
                        </select>

                        <button
                            type="button"
                            @click="runCarryForward"
                            :disabled="processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M17 1l4 4-4 4"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M3 11V9a4 4 0 014-4h14"
                                    stroke-linecap="round"
                                />
                                <path
                                    d="M7 23l-4-4 4-4"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M21 13v2a4 4 0 01-4 4H3"
                                    stroke-linecap="round"
                                />
                            </svg>

                            Carry Forward
                        </button>

                        <button
                                type="button"
                                @click="runAccrual"
                                :disabled="processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M12 3v18M3 12h18"
                                        stroke-linecap="round"
                                    />
                                </svg>

                                Run Accrual
                            </button>

                        <button
                            type="button"
                            @click="generateBalances"
                            :disabled="processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M12 3v18M3 12h18"
                                />
                            </svg>

                            Generate Balances
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6">
            <!-- Statistics -->
            <div
                class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
            >
                <!-- Total -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-gray-500"
                            >
                                Total Balances
                            </p>

                            <p
                                class="mt-2 text-2xl font-bold text-gray-900"
                            >
                                {{ formatNumber(statistics.total) }}
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-blue-600"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <rect
                                    x="3"
                                    y="4"
                                    width="18"
                                    height="16"
                                    rx="2"
                                />
                                <path d="M8 9h8M8 13h5" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Employees -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-gray-500"
                            >
                                Employees
                            </p>

                            <p
                                class="mt-2 text-2xl font-bold text-gray-900"
                            >
                                {{ formatNumber(statistics.employees) }}
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                />
                                <circle
                                    cx="9"
                                    cy="7"
                                    r="4"
                                />
                                <path
                                    d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Used -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-gray-500"
                            >
                                Days Used
                            </p>

                            <p
                                class="mt-2 text-2xl font-bold text-gray-900"
                            >
                                {{ formatDays(statistics.used) }}
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-lg bg-orange-50 text-orange-600"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M5 12h14M12 5v14"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Available -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-gray-500"
                            >
                                Available Days
                            </p>

                            <p
                                class="mt-2 text-2xl font-bold text-gray-900"
                            >
                                {{ formatDays(statistics.available) }}
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-lg bg-green-50 text-green-600"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M5 12l4 4L19 6"
                                />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main card -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <!-- Toolbar -->
                <div
                    class="flex flex-col gap-4 border-b border-gray-200 p-5 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div>
                        <h2
                            class="text-base font-semibold text-gray-900"
                        >
                            {{ selectedYear }} Leave Balances
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ filteredBalances.length }}
                            balance{{ filteredBalances.length === 1 ? '' : 's' }}
                            shown
                        </p>
                    </div>

                    <div class="relative w-full lg:w-96">
                        <svg
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle
                                cx="11"
                                cy="11"
                                r="8"
                            />
                            <path d="m21 21-4.3-4.3" />
                        </svg>

                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search employee or leave type..."
                            class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-10 text-sm outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <button
                            v-if="search"
                            type="button"
                            @click="clearSearch"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        >
                            ×
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Employee
                                </th>

                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Leave Type
                                </th>

                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Entitled
                                </th>

                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Accrued
                                </th>

                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Carried
                                </th>

                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Used
                                </th>

                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Pending
                                </th>

                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Available
                                </th>

                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-gray-100 bg-white"
                        >
                            <tr
                                v-for="balance in filteredBalances"
                                :key="balance.id"
                                class="transition hover:bg-gray-50"
                            >
                                <!-- Employee -->
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div
                                        class="flex items-center gap-3"
                                    >
                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-sm font-semibold text-blue-600"
                                        >
                                            {{
                                                balance.employee?.name
                                                    ?.charAt(0)
                                                    ?.toUpperCase() || '?'
                                            }}
                                        </div>

                                        <div>
                                            <div
                                                class="text-sm font-semibold text-gray-900"
                                            >
                                                {{
                                                    balance.employee?.name ||
                                                    'Unknown Employee'
                                                }}
                                            </div>

                                            <div
                                                class="text-xs text-gray-500"
                                            >
                                                {{
                                                    balance.employee
                                                        ?.employee_number ||
                                                    '—'
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Leave Type -->
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{
                                            balance.leave_type?.name ||
                                            'Unknown'
                                        }}
                                    </div>

                                    <div
                                        class="text-xs text-gray-500"
                                    >
                                        {{
                                            balance.leave_type?.code ||
                                            '—'
                                        }}
                                    </div>
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right text-sm text-gray-700"
                                >
                                    {{ formatDays(balance.entitled_days) }}
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right text-sm text-gray-700"
                                >
                                    {{ formatDays(balance.accrued_days) }}
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right text-sm text-gray-700"
                                >
                                    {{ formatDays(balance.carried_forward_days) }}
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right text-sm text-gray-700"
                                >
                                    {{ formatDays(balance.used_days) }}
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right text-sm text-gray-700"
                                >
                                    {{ formatDays(balance.pending_days) }}
                                </td>

                                <!-- Available -->
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right"
                                >
                                    <span
                                        class="inline-flex min-w-16 items-center justify-center rounded-full px-2.5 py-1 text-xs font-bold"
                                        :class="
                                            balance.available_days > 0
                                                ? 'bg-green-50 text-green-700'
                                                : 'bg-gray-100 text-gray-600'
                                        "
                                    >
                                        {{
                                            formatDays(
                                                balance.available_days
                                            )
                                        }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right"
                                >
                                    <button
                                        type="button"
                                        @click="openAdjustment(balance)"
                                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                    >
                                        Adjust
                                    </button>
                                </td>
                            </tr>

                            <!-- Empty -->
                            <tr
                                v-if="filteredBalances.length === 0"
                            >
                                <td
                                    colspan="9"
                                    class="px-5 py-16 text-center"
                                >
                                    <div
                                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400"
                                    >
                                        <svg
                                            class="h-6 w-6"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <circle
                                                cx="11"
                                                cy="11"
                                                r="8"
                                            />
                                            <path
                                                d="m21 21-4.3-4.3"
                                            />
                                        </svg>
                                    </div>

                                    <h3
                                        class="mt-4 text-sm font-semibold text-gray-900"
                                    >
                                        No leave balances found
                                    </h3>

                                    <p
                                        class="mt-1 text-sm text-gray-500"
                                    >
                                        Generate balances for
                                        {{ selectedYear }} or change your
                                        search.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Adjustment Modal -->
        <div
            v-if="showAdjustmentModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        >
            <div
                class="w-full max-w-lg rounded-2xl bg-white shadow-2xl"
            >
                <div
                    class="flex items-center justify-between border-b border-gray-200 px-6 py-5"
                >
                    <div>
                        <h3
                            class="text-lg font-bold text-gray-900"
                        >
                            Adjust Leave Balance
                        </h3>

                        <p
                            v-if="selectedBalance"
                            class="mt-1 text-sm text-gray-500"
                        >
                            {{
                                selectedBalance.employee?.name
                            }}
                            —
                            {{
                                selectedBalance.leave_type?.name
                            }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="closeAdjustment"
                        class="text-2xl leading-none text-gray-400 hover:text-gray-600"
                    >
                        ×
                    </button>
                </div>

                <form
                    @submit.prevent="submitAdjustment"
                    class="space-y-5 p-6"
                >
                    <div
                        v-if="selectedBalance"
                        class="rounded-lg bg-gray-50 p-4"
                    >
                        <div
                            class="flex items-center justify-between"
                        >
                            <span
                                class="text-sm text-gray-500"
                            >
                                Current available
                            </span>

                            <span
                                class="text-lg font-bold text-gray-900"
                            >
                                {{
                                    formatDays(
                                        selectedBalance.available_days
                                    )
                                }}
                                days
                            </span>
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Adjustment Days
                        </label>

                        <input
                            v-model="adjustmentDays"
                            type="number"
                            step="0.01"
                            placeholder="e.g. 2 or -2"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <p class="mt-1 text-xs text-gray-500">
                            Use a positive number to add days or a
                            negative number to deduct days.
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Reason
                        </label>

                        <textarea
                            v-model="adjustmentReason"
                            rows="4"
                            placeholder="Enter the reason for this adjustment..."
                            class="w-full resize-none rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        ></textarea>
                    </div>

                    <div
                        class="flex justify-end gap-3 border-t border-gray-200 pt-5"
                    >
                        <button
                            type="button"
                            @click="closeAdjustment"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="processing"
                            class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Save Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>