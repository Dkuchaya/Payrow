<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'
defineOptions({ layout: AppLayout })



const props = defineProps({
    ruleSet: {
        type: Object,
        required: true,
    },
    employeeRule: {
        type: Object,
        default: null,
    },
    employerRule: {
        type: Object,
        default: null,
    },
});

const isDraft = computed(() => props.ruleSet.status === 'draft');

const form = useForm({
    employee_rate: Number(props.employeeRule?.rate ?? 1),
    employer_rate: Number(props.employerRule?.rate ?? 1),
    period: props.employeeRule?.settings?.period ?? 'monthly',
    employee_is_active: props.employeeRule?.is_active ?? true,
    employer_is_active: props.employerRule?.is_active ?? true,
    notes: props.employeeRule?.notes ?? '',
});

const combinedRate = computed(() => {
    return Number(form.employee_rate) + Number(form.employer_rate);
});

function saveConfiguration() {
    form.put(
        route('statutory-rule-sets.nhima.save', props.ruleSet.id),
        {
            preserveScroll: true,
        }
    );
}
</script>

<template>
    <Head title="NHIMA Configuration" />

    <div class="space-y-6 p-6">

        <!-- Header -->
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <Link
                    :href="route('statutory-rule-sets.show', ruleSet.id)"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    ← Back to Rule Set
                </Link>

                <h1 class="mt-3 text-2xl font-bold text-gray-900">
                    NHIMA Configuration
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Manage employee and employer health insurance contributions.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <span
                    class="rounded-full px-3 py-1 text-xs font-semibold"
                    :class="isDraft
                        ? 'bg-amber-100 text-amber-800'
                        : 'bg-green-100 text-green-800'"
                >
                    {{ ruleSet.status.toUpperCase() }}
                </span>

                <span class="text-sm text-gray-500">
                    {{ ruleSet.country_name }} · {{ ruleSet.tax_year }}
                </span>
            </div>
        </div>

        <!-- Read-only Notice -->
        <div
            v-if="!isDraft"
            class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800"
        >
            This rule set is {{ ruleSet.status }}. NHIMA configuration is read-only.
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">
                    Employee Contribution
                </p>
                <p class="mt-2 text-2xl font-bold text-gray-900">
                    {{ Number(form.employee_rate).toFixed(2) }}%
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    {{ form.employee_is_active ? 'Enabled' : 'Disabled' }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">
                    Employer Contribution
                </p>
                <p class="mt-2 text-2xl font-bold text-gray-900">
                    {{ Number(form.employer_rate).toFixed(2) }}%
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    {{ form.employer_is_active ? 'Enabled' : 'Disabled' }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">
                    Combined Contribution
                </p>
                <p class="mt-2 text-2xl font-bold text-indigo-600">
                    {{ combinedRate.toFixed(2) }}%
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    Employee + Employer
                </p>
            </div>

        </div>

        <!-- Configuration Form -->
        <form @submit.prevent="saveConfiguration" class="space-y-6">

            <!-- Employee -->
            <section class="rounded-xl border border-gray-200 bg-white">

                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                Employee Contribution
                            </h2>
                            <p class="mt-1 text-sm text-gray-500">
                                NHIMA contribution deducted from employee earnings.
                            </p>
                        </div>

                        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                            Employee
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Employee Contribution Rate (%)
                        </label>

                        <input
                            v-model.number="form.employee_rate"
                            type="number"
                            min="0"
                            max="100"
                            step="0.000001"
                            :disabled="!isDraft"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:bg-gray-100"
                        />

                        <p v-if="form.errors.employee_rate" class="mt-1 text-sm text-red-600">
                            {{ form.errors.employee_rate }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Earnings Basis
                        </label>

                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                            <p class="font-medium text-gray-900">
                                NHIMA Employee Income
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                Basic salary + NHIMA-liable allowances + overtime
                            </p>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="inline-flex items-center gap-2">
                            <input
                                v-model="form.employee_is_active"
                                type="checkbox"
                                :disabled="!isDraft"
                                class="rounded border-gray-300 text-indigo-600"
                            />
                            <span class="text-sm font-medium text-gray-700">
                                Enable employee NHIMA contribution
                            </span>
                        </label>
                    </div>

                </div>
            </section>

            <!-- Employer -->
            <section class="rounded-xl border border-gray-200 bg-white">

                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                Employer Contribution
                            </h2>
                            <p class="mt-1 text-sm text-gray-500">
                                NHIMA contribution paid by the employer.
                            </p>
                        </div>

                        <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">
                            Employer
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Employer Contribution Rate (%)
                        </label>

                        <input
                            v-model.number="form.employer_rate"
                            type="number"
                            min="0"
                            max="100"
                            step="0.000001"
                            :disabled="!isDraft"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:bg-gray-100"
                        />

                        <p v-if="form.errors.employer_rate" class="mt-1 text-sm text-red-600">
                            {{ form.errors.employer_rate }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Earnings Basis
                        </label>

                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                            <p class="font-medium text-gray-900">
                                Basic Salary
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                Employer contribution calculated on basic salary
                            </p>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="inline-flex items-center gap-2">
                            <input
                                v-model="form.employer_is_active"
                                type="checkbox"
                                :disabled="!isDraft"
                                class="rounded border-gray-300 text-indigo-600"
                            />
                            <span class="text-sm font-medium text-gray-700">
                                Enable employer NHIMA contribution
                            </span>
                        </label>
                    </div>

                </div>
            </section>

            <!-- General Settings -->
            <section class="rounded-xl border border-gray-200 bg-white p-6">

                <h2 class="text-lg font-semibold text-gray-900">
                    General Settings
                </h2>

                <div class="mt-5 max-w-md">
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Contribution Period
                    </label>

                    <select
                        v-model="form.period"
                        :disabled="!isDraft"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:bg-gray-100"
                    >
                        <option value="monthly">Monthly</option>
                    </select>

                    <p v-if="form.errors.period" class="mt-1 text-sm text-red-600">
                        {{ form.errors.period }}
                    </p>
                </div>

            </section>

            <!-- Notes -->
            <section class="rounded-xl border border-gray-200 bg-white p-6">

                <h2 class="text-lg font-semibold text-gray-900">
                    Internal Notes
                </h2>

                <textarea
                    v-model="form.notes"
                    rows="3"
                    :disabled="!isDraft"
                    placeholder="Add notes about this NHIMA configuration..."
                    class="mt-3 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:bg-gray-100"
                ></textarea>

                <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                    {{ form.errors.notes }}
                </p>

            </section>

            <!-- Save -->
            <div v-if="isDraft" class="flex justify-end">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                >
                    {{ form.processing ? 'Saving...' : 'Save NHIMA Configuration' }}
                </button>
            </div>

        </form>
    </div>
</template>
