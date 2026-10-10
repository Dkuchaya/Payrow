<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
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
    status: string
    employee: Employee
}

interface Clearance {
    id: number
    category: string
    title: string
    description?: string | null
    is_required: boolean
    status: 'pending' | 'cleared' | 'rejected'
    comments?: string | null
    cleared_at?: string | null
    department?: {
        id: number
        name: string
    } | null
    responsible_user?: {
        id: number
        name: string
    } | null
    cleared_by?: {
        id: number
        name: string
    } | null
}

interface Stats {
    total: number
    pending: number
    cleared: number
    rejected: number
    required: number
    required_cleared: number
    complete: boolean
}

interface Department {
    id: number
    name: string
}

const props = defineProps<{
    separation: Separation
    clearances: Clearance[]
    stats: Stats
    departments: Department[]
}>()

const selectedClearance = ref<Clearance | null>(null)
const action = ref<'clear' | 'reject' | null>(null)
const comments = ref('')
const processing = ref(false)

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

const progressPercentage = computed(() => {
    if (props.stats.required === 0) {
        return 100
    }

    return Math.round(
        (props.stats.required_cleared / props.stats.required) * 100
    )
})

const statusLabel = (status: string) => {
    return status
        .replaceAll('_', ' ')
        .replace(/\b\w/g, letter => letter.toUpperCase())
}

const typeLabel = (type: string) => {
    return type
        .replaceAll('_', ' ')
        .replace(/\b\w/g, letter => letter.toUpperCase())
}

const statusClass = (status: string) => {
    switch (status) {
        case 'cleared':
            return 'bg-green-100 text-green-700'

        case 'rejected':
            return 'bg-red-100 text-red-700'

        default:
            return 'bg-yellow-100 text-yellow-700'
    }
}

const openAction = (
    clearance: Clearance,
    selectedAction: 'clear' | 'reject'
) => {
    selectedClearance.value = clearance
    action.value = selectedAction
    comments.value = ''
}

const closeAction = () => {
    selectedClearance.value = null
    action.value = null
    comments.value = ''
}

const submitAction = () => {
    if (!selectedClearance.value || !action.value) {
        return
    }

    if (action.value === 'reject' && !comments.value.trim()) {
        return
    }

    processing.value = true

    const url =
        action.value === 'clear'
            ? `/employee-separation-clearances/${selectedClearance.value.id}/clear`
            : `/employee-separation-clearances/${selectedClearance.value.id}/reject`

    router.post(
        url,
        {
            comments: comments.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false
                closeAction()
            },
        }
    )
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="mb-2 flex items-center gap-2 text-sm text-slate-500">
                        <a
                            href="/employee-separations"
                            class="hover:text-blue-600"
                        >
                            Employee Separations
                        </a>

                        <span>/</span>

                        <span>Clearance</span>
                    </div>

                    <h1 class="text-2xl font-bold text-slate-900">
                        Employee Clearance
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Complete all required clearance items before final payroll.
                    </p>
                </div>

                <a
                    :href="`/employee-separations/${props.separation.id}`"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                >
                    ← Back to Separation
                </a>
            </div>

            <!-- Employee Summary -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">

                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-lg font-bold text-blue-700"
                        >
                            {{ employeeName.charAt(0).toUpperCase() }}
                        </div>

                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">
                                {{ employeeName }}
                            </h2>

                            <p class="text-sm text-slate-500">
                                {{ props.separation.employee.employee_number }}
                            </p>

                            <p class="mt-1 text-sm text-slate-500">
                                {{ props.separation.employee.position?.name || 'No position' }}
                                <span class="mx-1">•</span>
                                {{ props.separation.employee.department?.name || 'No department' }}
                            </p>
                        </div>
                    </div>

                    <div class="text-left md:text-right">
                        <span
                            class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                            :class="statusClass(props.separation.status)"
                        >
                            {{ statusLabel(props.separation.status) }}
                        </span>

                        <p class="mt-2 text-sm text-slate-500">
                            {{ typeLabel(props.separation.separation_type) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Progress -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-900">
                            Clearance Progress
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ stats.required_cleared }} of {{ stats.required }}
                            required items completed
                        </p>
                    </div>

                    <div class="text-2xl font-bold text-blue-600">
                        {{ progressPercentage }}%
                    </div>
                </div>

                <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-100">
                    <div
                        class="h-full rounded-full bg-blue-600 transition-all duration-500"
                        :style="{ width: `${progressPercentage}%` }"
                    ></div>
                </div>

                <div
                    v-if="stats.complete"
                    class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-700"
                >
                    ✓ All required clearance items have been completed.
                    This separation can proceed to final payroll.
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Total</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">
                        {{ stats.total }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Pending</p>
                    <p class="mt-2 text-2xl font-bold text-yellow-600">
                        {{ stats.pending }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Cleared</p>
                    <p class="mt-2 text-2xl font-bold text-green-600">
                        {{ stats.cleared }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Rejected</p>
                    <p class="mt-2 text-2xl font-bold text-red-600">
                        {{ stats.rejected }}
                    </p>
                </div>
            </div>

            <!-- Checklist -->
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="font-semibold text-slate-900">
                        Clearance Checklist
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Review and complete each clearance requirement.
                    </p>
                </div>

                <div class="divide-y divide-slate-100">

                    <div
                        v-for="clearance in clearances"
                        :key="clearance.id"
                        class="p-6"
                    >
<div
    class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between"
>
                            <div class="flex gap-4">

                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                                    :class="
                                        clearance.status === 'cleared'
                                            ? 'bg-green-100 text-green-700'
                                            : clearance.status === 'rejected'
                                              ? 'bg-red-100 text-red-700'
                                              : 'bg-yellow-100 text-yellow-700'
                                    "
                                >
                                    <span v-if="clearance.status === 'cleared'">
                                        ✓
                                    </span>

                                    <span v-else-if="clearance.status === 'rejected'">
                                        !
                                    </span>

                                    <span v-else>
                                        •
                                    </span>
                                </div>

                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-semibold text-slate-900">
                                            {{ clearance.title }}
                                        </h3>

                                        <span
                                            v-if="clearance.is_required"
                                            class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700"
                                        >
                                            Required
                                        </span>

                                        <span
                                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="statusClass(clearance.status)"
                                        >
                                            {{ statusLabel(clearance.status) }}
                                        </span>
                                    </div>

                                    <p
                                        v-if="clearance.description"
                                        class="mt-1 max-w-3xl text-sm text-slate-500"
                                    >
                                        {{ clearance.description }}
                                    </p>

                                    <div class="mt-2 flex flex-wrap gap-4 text-xs text-slate-500">
                                        <span>
                                            Category:
                                            <strong class="text-slate-700">
                                                {{ clearance.category }}
                                            </strong>
                                        </span>

                                        <span v-if="clearance.department">
                                            Department:
                                            <strong class="text-slate-700">
                                                {{ clearance.department.name }}
                                            </strong>
                                        </span>

                                        <span v-if="clearance.responsible_user">
                                            Responsible:
                                            <strong class="text-slate-700">
                                                {{ clearance.responsible_user.name }}
                                            </strong>
                                        </span>
                                    </div>

                                    <p
                                        v-if="clearance.comments"
                                        class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-600"
                                    >
                                        {{ clearance.comments }}
                                    </p>
                                </div>
                            </div>

                            <!-- Actions -->
                           <div
                                v-if="clearance.status !== 'cleared'"
                                class="flex items-center gap-2 whitespace-nowrap"
                            >
                                <button
                                    type="button"
                                    class="inline-flex h-9 min-w-[80px] items-center justify-center rounded-lg border border-green-300 bg-white px-4 text-sm font-semibold text-green-600 shadow-sm transition hover:bg-green-50"
                                    @click="openAction(clearance, 'clear')"
                                >
                                    ✓ Clear
                                </button>

                                <button
                                    type="button"
                                    class="inline-flex h-9 min-w-[80px] items-center justify-center rounded-lg border border-red-300 bg-white px-4 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-50"
                                    @click="openAction(clearance, 'reject')"
                                >
                                    ✕ Reject
                                </button>
                            </div>

                            <div
                                v-else
                                class="text-sm font-medium text-green-600"
                            >
                                ✓ Completed
                            </div>
                        </div>
                    </div>

                    <!-- Empty -->
                    <div
                        v-if="clearances.length === 0"
                        class="px-6 py-12 text-center"
                    >
                        <p class="font-medium text-slate-700">
                            No clearance items found.
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Start the clearance workflow from the separation page.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Modal -->
        <div
            v-if="selectedClearance && action"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-lg rounded-xl bg-white shadow-xl">

                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ action === 'clear' ? 'Complete Clearance' : 'Reject Clearance' }}
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ selectedClearance.title }}
                    </p>
                </div>

                <div class="p-6">
                    <label class="block text-sm font-medium text-slate-700">
                        Comments
                        <span v-if="action === 'reject'" class="text-red-500">
                            *
                        </span>
                    </label>

                    <textarea
                        v-model="comments"
                        rows="4"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        :placeholder="
                            action === 'clear'
                                ? 'Optional clearance comments...'
                                : 'Please provide the reason for rejection...'
                        "
                    ></textarea>

                    <p
                        v-if="action === 'reject' && !comments.trim()"
                        class="mt-2 text-xs text-red-500"
                    >
                        A reason is required when rejecting a clearance.
                    </p>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        @click="closeAction"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        :disabled="
                            processing ||
                            (action === 'reject' && !comments.trim())
                        "
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        :class="
                            action === 'clear'
                                ? 'bg-green-600 hover:bg-green-700'
                                : 'bg-red-600 hover:bg-red-700'
                        "
                        @click="submitAction"
                    >
                        {{
                            processing
                                ? 'Processing...'
                                : action === 'clear'
                                  ? 'Complete Clearance'
                                  : 'Reject Clearance'
                        }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>