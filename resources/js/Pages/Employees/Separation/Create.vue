<script setup lang="ts">
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'

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
        id: number
        name: string
    } | null
    position?: {
        id: number
        name: string
    } | null
}

const props = defineProps<{
    employee: Employee
}>()

const fullName = computed(() => {
    return [
        props.employee.first_name,
        props.employee.middle_name,
        props.employee.last_name,
    ]
        .filter(Boolean)
        .join(' ')
})

const form = useForm({
    separation_type: '',
    notice_date: '',
    effective_date: '',
    last_working_date: '',
    reason: '',
    notes: '',
})

const separationTypes = [
    { value: 'resignation', label: 'Resignation' },
    { value: 'termination', label: 'Termination' },
    { value: 'redundancy', label: 'Redundancy / Retrenchment' },
    { value: 'retirement', label: 'Retirement' },
    { value: 'end_of_contract', label: 'End of Contract' },
    { value: 'dismissal', label: 'Dismissal' },
    { value: 'death', label: 'Death' },
    { value: 'abscondment', label: 'Abscondment' },
    { value: 'mutual_separation', label: 'Mutual Separation' },
    { value: 'other', label: 'Other' },
]

function submit() {
    form.post(`/employees/${props.employee.id}/separation`)
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <Link
                        :href="`/employees/${employee.id}`"
                        class="hover:text-blue-600"
                    >
                        Employee
                    </Link>

                    <span>/</span>

                    <span>Separation</span>
                </div>

                <h1 class="mt-2 text-2xl font-bold text-gray-900">
                    Employee Separation
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Submit a separation request for {{ fullName }}.
                </p>
            </div>

            <Link
                :href="`/employees/${employee.id}`"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Cancel
            </Link>
        </div>

        <!-- Employee summary -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div
                    class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-lg font-bold text-blue-700"
                >
                    {{ employee.first_name?.charAt(0) }}
                    {{ employee.last_name?.charAt(0) }}
                </div>

                <div>
                    <h2 class="font-semibold text-gray-900">
                        {{ fullName }}
                    </h2>

                    <p class="text-sm text-gray-500">
                        {{ employee.employee_number }}
                    </p>

                    <p class="text-sm text-gray-500">
                        {{ employee.position?.name || 'No position' }}
                        <span v-if="employee.department">
                            · {{ employee.department.name }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Warning -->
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <div class="flex gap-3">
                <div class="mt-0.5 text-amber-600">
                    ⚠
                </div>

                <div>
                    <h3 class="font-semibold text-amber-900">
                        Separation affects the employee lifecycle
                    </h3>

                    <p class="mt-1 text-sm text-amber-800">
                        Submitting this request will not immediately mark the
                        employee as separated. The request will first enter
                        the approval workflow.
                    </p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form
            @submit.prevent="submit"
            class="rounded-xl border border-gray-200 bg-white shadow-sm"
        >
            <div class="border-b border-gray-200 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-900">
                    Separation Details
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Provide the details of why and when the employee is leaving.
                </p>
            </div>

            <div class="grid gap-6 p-6 md:grid-cols-2">
                <!-- Separation type -->
                <div>
                    <label class="form-label">
                        Separation Type <span class="text-red-500">*</span>
                    </label>

                    <select
                        v-model="form.separation_type"
                        class="form-input"
                    >
                        <option value="">
                            Select separation type
                        </option>

                        <option
                            v-for="type in separationTypes"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>

                    <p
                        v-if="form.errors.separation_type"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.separation_type }}
                    </p>
                </div>

                <!-- Notice date -->
                <div>
                    <label class="form-label">
                        Notice Date
                    </label>

                    <input
                        v-model="form.notice_date"
                        type="date"
                        class="form-input"
                    />

                    <p
                        v-if="form.errors.notice_date"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.notice_date }}
                    </p>
                </div>

                <!-- Effective date -->
                <div>
                    <label class="form-label">
                        Effective Date <span class="text-red-500">*</span>
                    </label>

                    <input
                        v-model="form.effective_date"
                        type="date"
                        class="form-input"
                    />

                    <p
                        v-if="form.errors.effective_date"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.effective_date }}
                    </p>
                </div>

                <!-- Last working date -->
                <div>
                    <label class="form-label">
                        Last Working Date
                    </label>

                    <input
                        v-model="form.last_working_date"
                        type="date"
                        class="form-input"
                    />

                    <p
                        v-if="form.errors.last_working_date"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.last_working_date }}
                    </p>
                </div>

                <!-- Reason -->
                <div class="md:col-span-2">
                    <label class="form-label">
                        Reason
                    </label>

                    <textarea
                        v-model="form.reason"
                        rows="5"
                        class="form-input"
                        placeholder="Explain the reason for separation..."
                    ></textarea>

                    <p
                        v-if="form.errors.reason"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.reason }}
                    </p>
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <label class="form-label">
                        Additional Notes
                    </label>

                    <textarea
                        v-model="form.notes"
                        rows="4"
                        class="form-input"
                        placeholder="Add any additional information..."
                    ></textarea>

                    <p
                        v-if="form.errors.notes"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.notes }}
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
                <Link
                    :href="`/employees/${employee.id}`"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{
                        form.processing
                            ? 'Submitting...'
                            : 'Submit Separation Request'
                    }}
                </button>
            </div>
        </form>
    </div>
</template>

<style scoped>
.form-label {
    @apply mb-1.5 block text-sm font-medium text-gray-700;
}

.form-input {
    @apply w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100;
}
</style>