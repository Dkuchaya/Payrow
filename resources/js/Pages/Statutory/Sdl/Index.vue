<script setup>
import { computed, reactive } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout })


const props = defineProps({
    ruleSet: {
        type: Object,
        required: true,
    },
    rule: {
        type: Object,
        default: null,
    },
    exemptCategories: {
        type: Array,
        default: () => [],
    },
});

const categoryLabels = {
    public_service: 'Public service',
    local_authority: 'Local authority',
    approved_public_benefit_organisation: 'Approved public-benefit organisation',
    income_tax_exempt_organisation: 'Income-tax-exempt organisation',
    approved_donor: 'Approved donor',
};

const existingSettings = props.rule?.settings ?? {};

const form = useForm({
    rate: props.rule?.rate ?? 0.5,
    annual_turnover_exemption_threshold:
        existingSettings.annual_turnover_exemption_threshold ?? 800000,
    exempt_employer_categories:
        existingSettings.exempt_employer_categories ?? [...props.exemptCategories],
    is_active: props.rule?.is_active ?? true,
    notes: props.rule?.notes ?? '',
});

const maximumTurnover = computed(() =>
    Number(form.annual_turnover_exemption_threshold || 0).toLocaleString()
);

function save() {
    form.put(
        route('statutory-rule-sets.sdl.save', props.ruleSet.id),
        {
            preserveScroll: true,
        }
    );
}
</script>

<template>
    <Head title="SDL Configuration" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Skills Development Levy (SDL)
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Configure employer SDL settings for
                    {{ ruleSet.name }}.
                </p>
            </div>

            <Link
                :href="route('statutory-rule-sets.show', ruleSet.id)"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Back to Rule Set
            </Link>
        </div>

        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
            <p class="font-semibold text-blue-900">
                Draft configuration
            </p>
            <p class="mt-1 text-sm text-blue-800">
                Changes apply only to this draft rule set. They will not
                affect payroll calculations using the active rule set.
                Verify the applicable statutory requirements before activation.
            </p>
        </div>

        <form @submit.prevent="save" class="space-y-6">
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Levy rate
                </h2>

                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            SDL Rate (%)
                        </label>

                        <input
                            v-model="form.rate"
                            type="number"
                            min="0"
                            max="100"
                            step="0.000001"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        />

                        <p v-if="form.errors.rate" class="mt-1 text-sm text-red-600">
                            {{ form.errors.rate }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Calculation period
                        </label>

                        <input
                            value="Monthly"
                            disabled
                            class="w-full rounded-lg border-gray-300 bg-gray-100 text-gray-600"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Earnings basis
                        </label>

                        <input
                            value="SDL chargeable emoluments"
                            disabled
                            class="w-full rounded-lg border-gray-300 bg-gray-100 text-gray-600"
                        />

                        <p class="mt-1 text-xs text-gray-500">
                            The existing payroll calculator determines
                            chargeable emoluments.
                        </p>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Annual turnover exemption
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Configure the annual turnover threshold used by the
                    existing SDL exemption logic.
                </p>

                <div class="mt-5 max-w-md">
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Annual turnover threshold (K)
                    </label>

                    <input
                        v-model="form.annual_turnover_exemption_threshold"
                        type="number"
                        min="0"
                        step="0.01"
                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    />

                    <p class="mt-1 text-sm text-gray-500">
                        Current configured threshold: K{{ maximumTurnover }}
                    </p>

                    <p
                        v-if="form.errors.annual_turnover_exemption_threshold"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.annual_turnover_exemption_threshold }}
                    </p>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Exempt employer categories
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Select the employer categories recognised by this rule set
                    as exempt under the existing calculator logic.
                </p>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label
                        v-for="category in exemptCategories"
                        :key="category"
                        class="flex items-start gap-3 rounded-lg border border-gray-200 p-4"
                    >
                        <input
                            v-model="form.exempt_employer_categories"
                            type="checkbox"
                            :value="category"
                            class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />

                        <span class="text-sm font-medium text-gray-700">
                            {{ categoryLabels[category] ?? category }}
                        </span>
                    </label>
                </div>

                <p
                    v-if="form.errors.exempt_employer_categories"
                    class="mt-2 text-sm text-red-600"
                >
                    {{ form.errors.exempt_employer_categories }}
                </p>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Rule status and notes
                </h2>

                <div class="mt-5 space-y-5">
                    <label class="flex items-center gap-3">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />

                        <span class="text-sm font-medium text-gray-700">
                            Enable SDL calculation in this rule set
                        </span>
                    </label>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Notes
                        </label>

                        <textarea
                            v-model="form.notes"
                            rows="4"
                            maxlength="2000"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Add statutory references, explanations or internal notes..."
                        ></textarea>

                        <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                            {{ form.errors.notes }}
                        </p>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap justify-end gap-3">
                <Link
                    :href="route('statutory-rule-sets.show', ruleSet.id)"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ form.processing ? 'Saving...' : 'Save SDL Settings' }}
                </button>
            </div>
        </form>
    </div>
</template>
