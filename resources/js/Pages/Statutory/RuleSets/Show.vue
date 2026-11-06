<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

interface TaxBand {
    id: number
    [key: string]: any
}

interface StatutoryRule {
    id: number
    code?: string
    name?: string
    description?: string
    tax_bands?: TaxBand[]
    taxBands?: TaxBand[]
    [key: string]: any
}

interface RuleSet {
    id: number
    country_code: string
    country_name: string
    name: string
    tax_year: number
    version: number
    effective_from: string
    effective_to: string | null
    status: string
    notes: string | null
    created_at: string
    activated_at: string | null
    creator?: { id: number; name: string }
    rules: StatutoryRule[]
}

interface ActivationValidation {
    valid: boolean
    errors: string[]
}

const props = defineProps<{
    ruleSet: RuleSet
    activationValidation: ActivationValidation
}>()

const activationForm = useForm<Record<string, any>>({})

const activateRuleSet = () => {
    if (!confirm('Are you sure you want to activate this statutory rule set?')) {
        return
    }

    activationForm.post(
        route('statutory-rule-sets.activate', props.ruleSet.id),
        {
            preserveScroll: true,
        }
    )
}

const formatDate = (date: string | null) => {
    if (!date) return 'Not set'

    return new Date(date).toLocaleDateString()
}

const formatLabel = (value: string) => {
    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, letter => letter.toUpperCase())
}

const displayValue = (value: any) => {
    if (value === null || value === undefined || value === '') {
        return '—'
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No'
    }

    if (typeof value === 'object') {
        return JSON.stringify(value)
    }

    return String(value)
}

const excludedFields = [
    'id',
    'created_at',
    'updated_at',
    'statutory_rule_set_id',
    'tax_bands',
    'taxBands',
]

const getRuleFields = (rule: StatutoryRule) => {
    return Object.entries(rule).filter(
        ([key, value]) =>
            !excludedFields.includes(key) &&
            value !== null &&
            value !== undefined &&
            typeof value !== 'object'
    )
}

const getTaxBands = (rule: StatutoryRule) => {
    return rule.tax_bands ?? rule.taxBands ?? []
}

const statusClass = (status: string) => {
    return {
        draft: 'bg-yellow-100 text-yellow-800',
        active: 'bg-green-100 text-green-800',
        archived: 'bg-gray-100 text-gray-700',
    }[status] ?? 'bg-gray-100 text-gray-700'
}
</script>

<template>
    <Head :title="`Statutory Rules - ${ruleSet.name}`" />

    <div class="mx-auto max-w-7xl space-y-6">

        <!-- Header -->
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <div class="mb-2">
                    <Link
                        :href="route('statutory-rule-sets.index')"
                        class="text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        ← Back to Statutory Rule Sets
                    </Link>
                </div>

                <h1 class="text-2xl font-bold text-gray-900">
                    {{ ruleSet.name }}
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    {{ ruleSet.country_name }} ({{ ruleSet.country_code }})
                    · Tax Year {{ ruleSet.tax_year }}
                    · Version {{ ruleSet.version }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <span
                    class="rounded-full px-3 py-1 text-sm font-semibold capitalize"
                    :class="statusClass(ruleSet.status)"
                >
                    {{ ruleSet.status }}
                </span>

                <Link
                    v-if="ruleSet.status === 'draft'"
                    :href="route('statutory-rule-sets.edit', ruleSet.id)"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    Edit Rule Set
                </Link>
            </div>
        </div>

        <!-- Overview -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Tax Year</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">
                    {{ ruleSet.tax_year }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Version</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">
                    v{{ ruleSet.version }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Configured Rules</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">
                    {{ ruleSet.rules?.length ?? 0 }}
                </p>
            </div>
        </div>

        <!-- Rule Set Information -->
         
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">
                Rule Set Information
            </h2>

            <div class="mt-5 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-sm text-gray-500">Country</p>
                    <p class="mt-1 font-medium text-gray-900">
                        {{ ruleSet.country_name }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Country Code</p>
                    <p class="mt-1 font-medium text-gray-900">
                        {{ ruleSet.country_code }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Tax Year</p>
                    <p class="mt-1 font-medium text-gray-900">
                        {{ ruleSet.tax_year }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Effective From</p>
                    <p class="mt-1 font-medium text-gray-900">
                        {{ formatDate(ruleSet.effective_from) }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Effective To</p>
                    <p class="mt-1 font-medium text-gray-900">
                        {{ formatDate(ruleSet.effective_to) }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Created By</p>
                    <p class="mt-1 font-medium text-gray-900">
                        {{ ruleSet.creator?.name ?? 'Unknown' }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Activated At</p>
                    <p class="mt-1 font-medium text-gray-900">
                        {{ formatDate(ruleSet.activated_at) }}
                    </p>
                </div>
            </div>

            <div v-if="ruleSet.notes" class="mt-6 border-t border-gray-100 pt-4">
                <p class="text-sm text-gray-500">Notes</p>
                <p class="mt-2 whitespace-pre-line text-gray-800">
                    {{ ruleSet.notes }}
                </p>
            </div>
        </div>

        
        <!-- Activation Validation -->
        <div
            v-if="ruleSet.status === 'draft'"
            class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
        >
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Activation Readiness
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Validate all statutory configurations before activating this rule set.
                    </p>
                </div>

                <span
                    :class="activationValidation.valid
                        ? 'rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800'
                        : 'rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-800'"
                >
                    {{ activationValidation.valid ? 'Ready for activation' : 'Action required' }}
                </span>
            </div>

            <div
                v-if="activationValidation.valid"
                class="mt-5 rounded-lg bg-green-50 p-4 text-sm text-green-800"
            >
                All statutory validation checks have passed. This rule set is eligible for activation.
            </div>

            <div v-else class="mt-5 rounded-lg bg-red-50 p-4">
                <p class="font-semibold text-red-800">
                    Please resolve the following issues:
                </p>

                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-red-700">
                    <li
                        v-for="(error, index) in activationValidation.errors"
                        :key="index"
                    >
                        {{ error }}
                    </li>
                </ul>
            </div>

            <div class="mt-5 flex justify-end">
                <button
                    v-if="activationValidation.valid"
                    type="button"
                    @click="activateRuleSet"
                    :disabled="activationForm.processing"
                    class="rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ activationForm.processing ? 'Activating...' : 'Activate Rule Set' }}
                </button>

                <span
                    v-else
                    class="rounded-lg bg-gray-100 px-5 py-2.5 text-sm font-medium text-gray-500"
                >
                    Activation unavailable
                </span>
            </div>

            <div
                v-if="activationForm.errors.activation"
                class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700"
            >
                {{ activationForm.errors.activation }}
            </div>
        </div>


        <!-- Statutory Configuration Navigation -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-gray-900">
                    Statutory Configuration
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Manage statutory rates, contribution limits, tax bands and exemptions
                    for this rule set.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    :href="route('statutory-rule-sets.paye.index', ruleSet.id)"
                    class="group rounded-xl border border-gray-200 p-5 transition hover:border-indigo-400 hover:bg-indigo-50"
                >
                    <div class="mb-3 text-2xl">💰</div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-indigo-700">
                        PAYE
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Configure income tax rates and progressive tax bands.
                    </p>
                    <span class="mt-4 inline-block text-sm font-semibold text-indigo-600">
                        Manage PAYE →
                    </span>
                </Link>

                <Link
                    :href="route('statutory-rule-sets.napsa.index', ruleSet.id)"
                    class="group rounded-xl border border-gray-200 p-5 transition hover:border-indigo-400 hover:bg-indigo-50"
                >
                    <div class="mb-3 text-2xl">🏦</div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-indigo-700">
                        NAPSA
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Manage employee and employer pension contributions.
                    </p>
                    <span class="mt-4 inline-block text-sm font-semibold text-indigo-600">
                        Manage NAPSA →
                    </span>
                </Link>

                <Link
                    :href="route('statutory-rule-sets.nhima.index', ruleSet.id)"
                    class="group rounded-xl border border-gray-200 p-5 transition hover:border-indigo-400 hover:bg-indigo-50"
                >
                    <div class="mb-3 text-2xl">🏥</div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-indigo-700">
                        NHIMA
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Configure employee and employer health contributions.
                    </p>
                    <span class="mt-4 inline-block text-sm font-semibold text-indigo-600">
                        Manage NHIMA →
                    </span>
                </Link>

                <Link
                    :href="route('statutory-rule-sets.sdl.index', ruleSet.id)"
                    class="group rounded-xl border border-gray-200 p-5 transition hover:border-indigo-400 hover:bg-indigo-50"
                >
                    <div class="mb-3 text-2xl">🎓</div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-indigo-700">
                        SDL
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Manage Skills Development Levy rates and exemptions.
                    </p>
                    <span class="mt-4 inline-block text-sm font-semibold text-indigo-600">
                        Manage SDL →
                    </span>
                </Link>
            </div>
        </div>

        <!-- Statutory Rules -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Statutory Rules
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Configured payroll deductions and employer contributions.
                    </p>
                </div>
            </div>

            <div
                v-if="!ruleSet.rules || ruleSet.rules.length === 0"
                class="rounded-lg bg-gray-50 p-8 text-center"
            >
                <p class="font-medium text-gray-700">
                    No statutory rules configured yet.
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    Add statutory rules to this draft rule set.
                </p>
            </div>

            <div v-else class="space-y-5">
                <div
                    v-for="rule in ruleSet.rules"
                    :key="rule.id"
                    class="rounded-lg border border-gray-200 p-5"
                >
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-900">
                            {{ rule.name ?? rule.code ?? `Rule #${rule.id}` }}
                        </h3>

                        <p v-if="rule.description" class="mt-1 text-sm text-gray-500">
                            {{ rule.description }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="[key, value] in getRuleFields(rule)"
                            :key="key"
                            class="rounded-lg bg-gray-50 p-3"
                        >
                            <p class="text-xs text-gray-500">
                                {{ formatLabel(key) }}
                            </p>
                            <p class="mt-1 break-words text-sm font-semibold text-gray-900">
                                {{ displayValue(value) }}
                            </p>
                        </div>
                    </div>

                    <!-- Tax Bands -->
                    <div v-if="getTaxBands(rule).length" class="mt-5">
                        <h4 class="mb-3 text-sm font-semibold text-gray-800">
                            Tax Bands
                        </h4>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            v-for="key in Object.keys(getTaxBands(rule)[0]).filter(
                                                field => !['id', 'created_at', 'updated_at', 'statutory_rule_id'].includes(field)
                                            )"
                                            :key="key"
                                            class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500"
                                        >
                                            {{ formatLabel(key) }}
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="(band, index) in getTaxBands(rule)"
                                        :key="band.id ?? index"
                                    >
                                        <td
                                            v-for="key in Object.keys(getTaxBands(rule)[0]).filter(
                                                field => !['id', 'created_at', 'updated_at', 'statutory_rule_id'].includes(field)
                                            )"
                                            :key="key"
                                            class="whitespace-nowrap px-4 py-3 text-sm text-gray-700"
                                        >
                                            {{ displayValue(band[key]) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
