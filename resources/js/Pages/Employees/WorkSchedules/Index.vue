<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
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
    employee_number: string
    first_name: string
    middle_name?: string | null
    last_name: string
}

interface WorkSchedule {
    id: number
    name: string
    code: string
}

interface Assignment {
    id: number
    work_schedule_id: number
    work_schedule: WorkSchedule | null
    effective_from: string | null
    effective_to: string | null
    status: 'Active' | 'Scheduled' | 'Ended'
    is_primary: boolean
    notes: string | null
}

const props = defineProps<{
    company: Company
    employee: Employee
    assignments: Assignment[]
    workSchedules: WorkSchedule[]
}>()

const form = ref({
    work_schedule_id: '',
    effective_from: '',
    effective_to: '',
    is_primary: true,
    notes: '',
})

const processing = ref(false)

const employeeName = computed(() => {
    return [
        props.employee.first_name,
        props.employee.middle_name,
        props.employee.last_name,
    ]
        .filter(Boolean)
        .join(' ')
})


const dateOverlap = computed(() => {
    if (!form.value.effective_from) {
        return null
    }

    const newStart = new Date(form.value.effective_from)
    const newEnd = form.value.effective_to
        ? new Date(form.value.effective_to)
        : null

    const isNewPrimary = Boolean(form.value.is_primary)

    const overlappingAssignment = props.assignments.find((assignment) => {
        if (!assignment.effective_from) {
            return false
        }

        const existingStart = new Date(assignment.effective_from)

        const existingEnd = assignment.effective_to
            ? new Date(assignment.effective_to)
            : null

        /*
         * If this is a new primary schedule and the existing
         * schedule is open-ended but started before the new
         * schedule, the existing schedule will automatically
         * end the day before the new schedule starts.
         *
         * Therefore, it is not considered an overlap.
         */
        if (
            isNewPrimary &&
            !existingEnd &&
            existingStart < newStart
        ) {
            return false
        }

        const startsBeforeExistingEnds =
            !existingEnd || newStart <= existingEnd

        const endsAfterExistingStarts =
            !newEnd || newEnd >= existingStart

        return (
            startsBeforeExistingEnds &&
            endsAfterExistingStarts
        )
    })

    return overlappingAssignment ?? null
})

const showCancelModal = ref(false)
const cancellingAssignment = ref<Assignment | null>(null)
const cancellingProcessing = ref(false)

const openCancelModal = (assignment: Assignment) => {
    cancellingAssignment.value = assignment
    showCancelModal.value = true
}

const closeCancelModal = () => {
    if (cancellingProcessing.value) {
        return
    }

    showCancelModal.value = false
    cancellingAssignment.value = null
}

const cancelAssignment = () => {
    if (!cancellingAssignment.value) {
        return
    }

    cancellingProcessing.value = true

    router.delete(
        route(
            'employees.work-schedules.cancel',
            {
                employee: props.employee.id,
                assignment: cancellingAssignment.value.id,
            }
        ),
        {
            preserveScroll: true,

            onFinish: () => {
                cancellingProcessing.value = false
            },

            onSuccess: () => {
                closeCancelModal()
            },
        }
    )
}

const submit = () => {
    processing.value = true

    router.post(
        route(
            'employees.work-schedules.store',
            props.employee.id
        ),
        form.value,
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false
            },
            onSuccess: () => {
                form.value = {
                    work_schedule_id: '',
                    effective_from: '',
                    effective_to: '',
                    is_primary: true,
                    notes: '',
                }
            },
        }
    )
}

    const showEndModal = ref(false)
const endingAssignment = ref<Assignment | null>(null)
const endingDate = ref('')
const endingProcessing = ref(false)

const openEndModal = (assignment: Assignment) => {
    endingAssignment.value = assignment

    const today = new Date().toISOString().slice(0, 10)

    endingDate.value = today

    showEndModal.value = true
}

const closeEndModal = () => {
    if (endingProcessing.value) {
        return
    }

    showEndModal.value = false
    endingAssignment.value = null
    endingDate.value = ''
}

    const endAssignment = () => {
        if (!endingAssignment.value || !endingDate.value) {
            return
        }

        const assignment = endingAssignment.value

        endingProcessing.value = true

        router.patch(
            route(
                'employees.work-schedules.end',
                {
                    employee: props.employee.id,
                    assignment: assignment.id,
                }
            ),
            {
                effective_to: endingDate.value,
            },
            {
                preserveScroll: true,

                onFinish: () => {
                    endingProcessing.value = false
                },

                onSuccess: () => {
                    closeEndModal()
                },
            }
        )
    }
</script>

<template>
    <Head :title="`Work Schedules - ${employeeName}`" />

    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <Link
                        :href="route('employees.index')"
                        class="hover:text-gray-700"
                    >
                        Employees
                    </Link>

                    <span>/</span>

                    <span>Work Schedules</span>
                </div>

                <h1 class="mt-2 text-2xl font-semibold text-gray-900">
                    Work Schedules
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Manage work schedule assignments for
                    <span class="font-medium text-gray-700">
                        {{ employeeName }}
                    </span>
                    <span class="ml-1">
                        ({{ employee.employee_number }})
                    </span>
                </p>
            </div>

            <Link
                :href="route('employees.index')"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Back to Employees
            </Link>
        </div>

        <!-- Assign Schedule -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Assign Work Schedule
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Assign a work schedule and define when it becomes effective.
                </p>
            </div>

            <form
                @submit.prevent="submit"
                class="space-y-6 p-6"
            >

                <!-- Schedule -->
                <div>
                    <label
                        for="work_schedule_id"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Work Schedule
                    </label>

                    <select
                        id="work_schedule_id"
                        v-model="form.work_schedule_id"
                        required
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">
                            Select work schedule
                        </option>

                        <option
                            v-for="schedule in workSchedules"
                            :key="schedule.id"
                            :value="schedule.id"
                        >
                            {{ schedule.name }} ({{ schedule.code }})
                        </option>
                    </select>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                    <div>
                        <label
                            for="effective_from"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Effective From
                        </label>

                        <input
                            id="effective_from"
                            v-model="form.effective_from"
                            type="date"
                            required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label
                            for="effective_to"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Effective To
                        </label>

                        <input
                            id="effective_to"
                            v-model="form.effective_to"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />

                        <p class="mt-1 text-xs text-gray-500">
                            Leave blank if the assignment has no end date.
                        </p>
                    </div>

                    <div
                        v-if="dateOverlap"
                        class="rounded-lg border border-red-200 bg-red-50 p-4"
                    >
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 text-red-600">
                                ⚠
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-red-800">
                                    Schedule period overlaps an existing assignment
                                </p>

                                <p class="mt-1 text-sm text-red-700">
                                    This employee already has
                                    <span class="font-semibold">
                                        {{ dateOverlap.work_schedule?.name ?? 'another work schedule' }}
                                    </span>
                                    covering part or all of the selected period.
                                </p>

                                <p class="mt-1 text-xs text-red-600">
                                    Existing period:
                                    {{ dateOverlap.effective_from || '-' }}
                                    →
                                    {{ dateOverlap.effective_to || 'No end date' }}
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Primary -->
                <div class="flex items-start gap-3">

                    <input
                        id="is_primary"
                        v-model="form.is_primary"
                        type="checkbox"
                        class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />

                    <div>
                        <label
                            for="is_primary"
                            class="text-sm font-medium text-gray-700"
                        >
                            Make this the primary schedule
                        </label>

                        <p class="text-xs text-gray-500">
                            The primary schedule will be used as the employee's
                            main work schedule.
                        </p>
                    </div>

                </div>

                <!-- Notes -->
                <div>
                    <label
                        for="notes"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Notes
                    </label>

                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        maxlength="2000"
                        placeholder="Optional notes about this assignment..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    ></textarea>
                </div>

                <!-- Submit -->
                <div class="flex justify-end border-t border-gray-100 pt-4">

                    <button
                        type="submit"
                        :disabled="processing || !!dateOverlap"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span v-if="processing">
                            Assigning...
                        </span>

                        <span v-else>
                            Assign Schedule
                        </span>
                    </button>

                </div>

            </form>
        </div>

        <!-- Existing Assignments -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Schedule History
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Work schedules previously assigned to this employee.
                </p>
            </div>

            <div
                v-if="assignments.length === 0"
                class="px-6 py-10 text-center"
            >
                <p class="text-sm text-gray-500">
                    No work schedules have been assigned yet.
                </p>
            </div>

            <div
                v-else
                class="overflow-x-auto"
            >
                <table class="min-w-full divide-y divide-gray-200">

                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Schedule
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Effective From
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Effective To
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Status
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white">

                        <tr
                            v-for="assignment in assignments"
                            :key="assignment.id"
                            class="hover:bg-gray-50"
                        >

                            <td class="whitespace-nowrap px-6 py-4">
                                <div
                                    v-if="assignment.work_schedule"
                                >
                                    <div class="font-medium text-gray-900">
                                        {{ assignment.work_schedule.name }}
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        {{ assignment.work_schedule.code }}
                                    </div>
                                </div>

                                <span
                                    v-else
                                    class="text-sm text-gray-400"
                                >
                                    Schedule unavailable
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                {{ assignment.effective_from || '-' }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                {{ assignment.effective_to || 'No end date' }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4">

                               <div class="flex flex-wrap items-center gap-2">
                                <span
                                    :class="{
                                        'bg-green-100 text-green-700':
                                            assignment.status === 'Active',

                                        'bg-blue-100 text-blue-700':
                                            assignment.status === 'Scheduled',

                                        'bg-gray-100 text-gray-600':
                                            assignment.status === 'Ended',
                                    }"
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-medium"
                                >
                                    {{ assignment.status }}
                                </span>

                                <span
                                    :class="
                                        assignment.is_primary
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-gray-100 text-gray-600'
                                    "
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-medium"
                                >
                                    {{ assignment.is_primary ? 'Primary' : 'Secondary' }}
                                </span>
                            </div>

                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right">

                               <div class="flex items-center gap-4">
                                    <Link
                                        :href="route(
                                            'employees.work-schedules.edit',
                                            {
                                                employee: employee.id,
                                                assignment: assignment.id,
                                            }
                                        )"
                                        class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                    >
                                        Edit
                                    </Link>

                                <button
                                    v-if="assignment.status === 'Active'"
                                    type="button"
                                    @click="openEndModal(assignment)"
                                    class="text-sm font-medium text-red-600 hover:text-red-800"
                                >
                                    End
                                </button>
                                 <button
                                    v-if="assignment.status === 'Scheduled'"
                                    type="button"
                                    @click="openCancelModal(assignment)"
                                    class="text-sm font-medium text-orange-600 hover:text-orange-800"
                                >
                                    Cancel
                                </button>
                                </div>
                            </td>

                        </tr>

                    </tbody>

                </table>
            </div>

        </div>

    </div>
    <!-- End Schedule Modal -->

<!-- Cancel Scheduled Assignment Modal -->
<div
    v-if="showCancelModal"
    class="fixed inset-0 z-50 flex items-center justify-center px-4"
>
    <!-- Backdrop -->
    <div
        class="absolute inset-0 bg-black/50"
        @click="closeCancelModal"
    ></div>

    <!-- Modal -->
    <div
        class="relative z-10 w-full max-w-md rounded-xl bg-white shadow-xl"
    >
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-lg font-semibold text-gray-900">
                Cancel Scheduled Work Schedule
            </h3>

            <p class="mt-1 text-sm text-gray-500">
                This scheduled assignment has not started yet.
            </p>
        </div>

        <!-- Body -->
        <div class="space-y-4 px-6 py-5">
            <div
                class="rounded-lg border border-orange-200 bg-orange-50 p-4"
            >
                <p class="text-sm text-orange-800">
                    You are cancelling:

                    <span class="font-semibold">
                        {{
                            cancellingAssignment?.work_schedule?.name
                            ?? 'this work schedule'
                        }}
                    </span>
                </p>

                <p class="mt-2 text-sm text-orange-700">
                    Effective from:

                    <span class="font-semibold">
                        {{
                            cancellingAssignment?.effective_from
                            ?? '-'
                        }}
                    </span>
                </p>
            </div>

            <p class="text-sm text-gray-600">
                This scheduled assignment will be removed because it has
                not started yet.
            </p>
        </div>

        <!-- Footer -->
        <div
            class="flex flex-col-reverse gap-3 border-t border-gray-200 px-6 py-4 sm:flex-row sm:justify-end"
        >
            <button
                type="button"
                @click="closeCancelModal"
                :disabled="cancellingProcessing"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Keep Schedule
            </button>

            <button
                type="button"
                @click="cancelAssignment"
                :disabled="cancellingProcessing"
                class="rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span v-if="cancellingProcessing">
                    Cancelling...
                </span>

                <span v-else>
                    Cancel Schedule
                </span>
            </button>
        </div>
    </div>
</div>


<div
    v-if="showEndModal"
    class="fixed inset-0 z-50 flex items-center justify-center px-4"
>
    <!-- Backdrop -->
    <div
        class="absolute inset-0 bg-black/50"
        @click="closeEndModal"
    ></div>

    <!-- Modal -->
    <div
        class="relative z-10 w-full max-w-md rounded-xl bg-white shadow-xl"
    >
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-lg font-semibold text-gray-900">
                End Work Schedule
            </h3>

            <p class="mt-1 text-sm text-gray-500">
                End the current schedule assignment for this employee.
            </p>
        </div>

        <!-- Body -->
        <div class="space-y-5 px-6 py-5">
            <div
                class="rounded-lg border border-yellow-200 bg-yellow-50 p-4"
            >
                <p class="text-sm text-yellow-800">
                    You are ending:

                    <span class="font-semibold">
                        {{
                            endingAssignment?.work_schedule?.name
                            ?? 'this work schedule'
                        }}
                    </span>
                </p>

                <p class="mt-1 text-xs text-yellow-700">
                    The schedule will remain in the employee's history.
                </p>
            </div>

            <!-- End Date -->
            <div>
                <label
                    for="ending_date"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    End Date
                </label>

                <input
                    id="ending_date"
                    v-model="endingDate"
                    type="date"
                    :min="endingAssignment?.effective_from ?? undefined"
                    :max="endingAssignment?.effective_to ?? undefined"
                    required
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />

                <p class="mt-1 text-xs text-gray-500">
                    The schedule will apply through this date.
                </p>
            </div>
        </div>

                <!-- Footer -->
                <div
                    class="flex flex-col-reverse gap-3 border-t border-gray-200 px-6 py-4 sm:flex-row sm:justify-end"
                >
                    <button
                        type="button"
                        @click="closeEndModal"
                        :disabled="endingProcessing"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="endAssignment"
                        :disabled="endingProcessing || !endingDate"
                        class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span v-if="endingProcessing">
                            Ending...
                        </span>

                        <span v-else>
                            End Schedule
                        </span>
                    </button>
                </div>
            </div>
        </div>
</template>