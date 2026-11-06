<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3';

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
    taxBands: {
        type: Array,
        default: () => [],
    },
});

const isDraft = computed(() => props.ruleSet.status === 'draft');

const ruleForm = useForm({
    name: props.rule?.name ?? 'Pay As You Earn (PAYE)',
    description: props.rule?.description ?? '',
    earnings_basis: props.rule?.earnings_basis ?? 'taxable_income',
    notes: props.rule?.notes ?? '',
    is_active: props.rule?.is_active ?? true,
});

const bandForm = useForm({
    name: '',
    lower_limit: 0,
    upper_limit: null,
    tax_rate: 0,
    base_tax: 0,
    sort_order: 1,
});

const editingBandId = ref(null);
const showBandForm = ref(false);

const sortedBands = computed(() => {
    return [...props.taxBands].sort(
        (a, b) => a.sort_order - b.sort_order
    );
});

function saveRule() {
    ruleForm.put(
        route('statutory-rule-sets.paye.rule.save', props.ruleSet.id)
    );
}

function openCreateBand() {
    editingBandId.value = null;

    bandForm.reset();
    bandForm.clearErrors();

    bandForm.name = '';
    bandForm.lower_limit = 0;
    bandForm.upper_limit = null;
    bandForm.tax_rate = 0;
    bandForm.base_tax = 0;
    bandForm.sort_order = sortedBands.value.length + 1;

    showBandForm.value = true;
}

function openEditBand(band) {
    editingBandId.value = band.id;

    bandForm.clearErrors();
    bandForm.name = band.name;
    bandForm.lower_limit = Number(band.lower_limit);
    bandForm.upper_limit = band.upper_limit !== null
        ? Number(band.upper_limit)
        : null;
    bandForm.tax_rate = Number(band.tax_rate);
    bandForm.base_tax = Number(band.base_tax);
    bandForm.sort_order = Number(band.sort_order);

    showBandForm.value = true;
}

function saveBand() {
    const url = editingBandId.value
        ? route('statutory-rule-sets.paye.bands.update', [
            props.ruleSet.id,
            editingBandId.value,
        ])
        : route('statutory-rule-sets.paye.bands.store', props.ruleSet.id);

    const method = editingBandId.value ? 'put' : 'post';

    bandForm[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            showBandForm.value = false;
            editingBandId.value = null;
            bandForm.reset();
        },
    });
}

function deleteBand(band) {
    if (!confirm(`Are you sure you want to delete "${band.name}"?`)) {
        return;
    }

    router.delete(
        route('statutory-rule-sets.paye.bands.destroy', [
            props.ruleSet.id,
            band.id,
        ]),
        {
            preserveScroll: true,
        }
    );
}

function formatMoney(value) {
    return new Intl.NumberFormat('en-ZM', {
        style: 'currency',
        currency: 'ZMW',
        minimumFractionDigits: 2,
    }).format(Number(value ?? 0));
}

function formatUpperLimit(value) {
    return value === null ? 'No upper limit' : formatMoney(value);
}
</script>

<template>
    <Head title="PAYE Configuration" />

    <div class="space-y-6 p-6">
        <!-- Header -->
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <div class="mb-2">
                    <Link
                        :href="route('statutory-rule-sets.show', ruleSet.id)"
                        class="text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        ← Back to Rule Set
                    </Link>
                </div>

                <h1 class="text-2xl font-bold text-gray-900">
                    PAYE Configuration
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Configure Pay As You Earn tax settings and progressive tax bands.
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

        <!-- Rule Set Information -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">Tax Year</p>
                <p class="mt-2 text-xl font-bold text-gray-900">
                    {{ ruleSet.tax_year }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">Rule Set Version</p>
                <p class="mt-2 text-xl font-bold text-gray-900">
                    v{{ ruleSet.version }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">Configured Tax Bands</p>
                <p class="mt-2 text-xl font-bold text-gray-900">
                    {{ taxBands.length }}
                </p>
            </div>
        </div>

        <!-- Draft Notice -->
        <div
            v-if="!isDraft"
            class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800"
        >
            This rule set is {{ ruleSet.status }}. PAYE configuration is read-only.
        </div>

        <!-- PAYE Rule Settings -->
        <section class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    PAYE Rule Settings
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Define the general settings for this PAYE rule.
                </p>
            </div>

            <form @submit.prevent="saveRule" class="space-y-5 p-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Rule Name
                        </label>
                        <input
                            v-model="ruleForm.name"
                            type="text"
                            :disabled="!isDraft"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                        />
                        <p v-if="ruleForm.errors.name" class="mt-1 text-sm text-red-600">
                            {{ ruleForm.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Earnings Basis
                        </label>
                        <input
                            v-model="ruleForm.earnings_basis"
                            type="text"
                            :disabled="!isDraft"
                            placeholder="e.g. taxable_income"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                        />
                        <p v-if="ruleForm.errors.earnings_basis" class="mt-1 text-sm text-red-600">
                            {{ ruleForm.errors.earnings_basis }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Description
                        </label>
                        <textarea
                            v-model="ruleForm.description"
                            rows="3"
                            :disabled="!isDraft"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                        ></textarea>
                        <p v-if="ruleForm.errors.description" class="mt-1 text-sm text-red-600">
                            {{ ruleForm.errors.description }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Internal Notes
                        </label>
                        <textarea
                            v-model="ruleForm.notes"
                            rows="2"
                            :disabled="!isDraft"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                        ></textarea>
                        <p v-if="ruleForm.errors.notes" class="mt-1 text-sm text-red-600">
                            {{ ruleForm.errors.notes }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="inline-flex items-center gap-2">
                            <input
                                v-model="ruleForm.is_active"
                                type="checkbox"
                                :disabled="!isDraft"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm font-medium text-gray-700">
                                Enable PAYE rule
                            </span>
                        </label>
                    </div>
                </div>

                <div v-if="isDraft" class="flex justify-end border-t border-gray-100 pt-4">
                    <button
                        type="submit"
                        :disabled="ruleForm.processing"
                        class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{ ruleForm.processing ? 'Saving...' : 'Save PAYE Settings' }}
                    </button>
                </div>
            </form>
        </section>

        <!-- Tax Bands -->
        <section class="rounded-xl border border-gray-200 bg-white">
            <div class="flex flex-col justify-between gap-3 border-b border-gray-200 px-6 py-4 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Progressive Tax Bands
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Configure the income ranges, tax rates and base tax amounts.
                    </p>
                </div>

                <button
                    v-if="isDraft && rule"
                    type="button"
                    @click="openCreateBand"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    + Add Tax Band
                </button>
            </div>

            <div v-if="!rule" class="p-8 text-center">
                <h3 class="font-semibold text-gray-900">
                    PAYE rule not configured
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                    Save the PAYE rule settings above before adding tax bands.
                </p>
            </div>

            <div v-else-if="sortedBands.length === 0" class="p-8 text-center">
                <p class="font-medium text-gray-800">
                    No tax bands configured yet.
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    Add your first progressive tax band to get started.
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Position
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Tax Band
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                Lower Limit
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                Upper Limit
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                Tax Rate
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                Base Tax
                            </th>
                            <th v-if="isDraft" class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="band in sortedBands" :key="band.id">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                {{ band.sort_order }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                {{ band.name }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-700">
                                {{ formatMoney(band.lower_limit) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-700">
                                {{ formatUpperLimit(band.upper_limit) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-gray-900">
                                {{ Number(band.tax_rate).toFixed(2) }}%
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-700">
                                {{ formatMoney(band.base_tax) }}
                            </td>
                            <td v-if="isDraft" class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <button
                                    type="button"
                                    @click="openEditBand(band)"
                                    class="mr-3 font-medium text-indigo-600 hover:text-indigo-800"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    @click="deleteBand(band)"
                                    class="font-medium text-red-600 hover:text-red-800"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Tax Band Form -->
        <div
            v-if="showBandForm"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4"
        >
            <div class="my-8 w-full max-w-2xl rounded-xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ editingBandId ? 'Edit Tax Band' : 'Add Tax Band' }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Enter the income range and applicable tax calculation.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="showBandForm = false"
                        class="text-2xl text-gray-400 hover:text-gray-700"
                    >
                        &times;
                    </button>
                </div>

                <form @submit.prevent="saveBand" class="space-y-5 p-6">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Tax Band Name
                        </label>
                        <input
                            v-model="bandForm.name"
                            type="text"
                            placeholder="e.g. First Tax Band"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        />
                        <p v-if="bandForm.errors.name" class="mt-1 text-sm text-red-600">
                            {{ bandForm.errors.name }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Lower Income Limit (K)
                            </label>
                            <input
                                v-model.number="bandForm.lower_limit"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            />
                            <p v-if="bandForm.errors.lower_limit" class="mt-1 text-sm text-red-600">
                                {{ bandForm.errors.lower_limit }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Upper Income Limit (K)
                            </label>
                            <input
                                v-model.number="bandForm.upper_limit"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="Leave blank for no upper limit"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            />
                            <p v-if="bandForm.errors.upper_limit" class="mt-1 text-sm text-red-600">
                                {{ bandForm.errors.upper_limit }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Tax Rate (%)
                            </label>
                            <input
                                v-model.number="bandForm.tax_rate"
                                type="number"
                                min="0"
                                max="100"
                                step="0.000001"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            />
                            <p v-if="bandForm.errors.tax_rate" class="mt-1 text-sm text-red-600">
                                {{ bandForm.errors.tax_rate }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Base Tax (K)
                            </label>
                            <input
                                v-model.number="bandForm.base_tax"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            />
                            <p v-if="bandForm.errors.base_tax" class="mt-1 text-sm text-red-600">
                                {{ bandForm.errors.base_tax }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Display Position
                            </label>
                            <input
                                v-model.number="bandForm.sort_order"
                                type="number"
                                min="1"
                                step="1"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            />
                            <p v-if="bandForm.errors.sort_order" class="mt-1 text-sm text-red-600">
                                {{ bandForm.errors.sort_order }}
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            @click="showBandForm = false"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="bandForm.processing"
                            class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ bandForm.processing ? 'Saving...' : 'Save Tax Band' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
