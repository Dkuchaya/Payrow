<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

defineOptions({
    layout: AppLayout,
})

const form = useForm({
    country_code: 'ZM',
    country_name: 'Zambia',
    name: '',
    tax_year: 2027,
    effective_from: '2027-01-01',
    effective_to: '',
    notes: '',
})

const submit = () => {
    form.post(route('statutory-rule-sets.store'))
}
</script>

<template>
    <Head title="Create Statutory Rule Set" />

    <div class="mx-auto max-w-4xl space-y-6">

        <!-- Header -->
        <div>
            <Link
                :href="route('statutory-rule-sets.index')"
                class="text-sm font-medium text-blue-600 hover:text-blue-800"
            >
                ← Back to Rule Sets
            </Link>

            <h1 class="mt-3 text-2xl font-bold text-gray-900">
                Create Statutory Rule Set
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Create a new statutory configuration for a country and tax year.
            </p>
        </div>

        <form
            @submit.prevent="submit"
            class="space-y-6"
        >

            <!-- Basic Information -->
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                <h2 class="text-lg font-semibold text-gray-900">
                    General Information
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Define the country and statutory rule set details.
                </p>

                <div class="mt-6 grid gap-5 md:grid-cols-2">

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Country Code *
                        </label>

                        <input
                            v-model="form.country_code"
                            type="text"
                            maxlength="2"
                            required
                            placeholder="ZM"
                            class="mt-2 w-full rounded-lg border-gray-300 uppercase"
                        />

                        <p v-if="form.errors.country_code" class="mt-1 text-sm text-red-600">
                            {{ form.errors.country_code }}
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Country Name *
                        </label>

                        <input
                            v-model="form.country_name"
                            type="text"
                            required
                            placeholder="Zambia"
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />

                        <p v-if="form.errors.country_name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.country_name }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">
                            Rule Set Name *
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Zambia Statutory Rules 2027"
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />

                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Tax Year *
                        </label>

                        <input
                            v-model.number="form.tax_year"
                            type="number"
                            min="2000"
                            max="2100"
                            required
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />

                        <p v-if="form.errors.tax_year" class="mt-1 text-sm text-red-600">
                            {{ form.errors.tax_year }}
                        </p>
                    </div>

                </div>
            </section>

            <!-- Effective Dates -->
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                <h2 class="text-lg font-semibold text-gray-900">
                    Effective Period
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Define when this statutory rule set is intended to apply.
                </p>

                <div class="mt-6 grid gap-5 md:grid-cols-2">

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Effective From *
                        </label>

                        <input
                            v-model="form.effective_from"
                            type="date"
                            required
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />

                        <p v-if="form.errors.effective_from" class="mt-1 text-sm text-red-600">
                            {{ form.errors.effective_from }}
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Effective To
                        </label>

                        <input
                            v-model="form.effective_to"
                            type="date"
                            :min="form.effective_from"
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />

                        <p v-if="form.errors.effective_to" class="mt-1 text-sm text-red-600">
                            {{ form.errors.effective_to }}
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Leave blank if there is no defined end date.
                        </p>
                    </div>

                </div>
            </section>

            <!-- Notes -->
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                <h2 class="text-lg font-semibold text-gray-900">
                    Notes
                </h2>

                <textarea
                    v-model="form.notes"
                    rows="4"
                    placeholder="Add any relevant statutory references or administrative notes..."
                    class="mt-4 w-full rounded-lg border-gray-300"
                />

                <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                    {{ form.errors.notes }}
                </p>

            </section>

            <!-- Status Information -->
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-5">

                <div class="flex items-start gap-3">
                    <div class="rounded-lg bg-amber-100 p-2">
                        <span class="text-amber-700">!</span>
                    </div>

                    <div>
                        <h3 class="font-semibold text-amber-900">
                            Draft Rule Set
                        </h3>

                        <p class="mt-1 text-sm text-amber-800">
                            This configuration will be saved as a draft.
                            It will not affect payroll calculations until
                            its statutory rules have been configured,
                            reviewed and activated.
                        </p>
                    </div>
                </div>

            </section>

            <!-- Actions -->
            <div class="flex flex-col-reverse justify-end gap-3 border-t border-gray-200 pt-5 sm:flex-row">

                <Link
                    :href="route('statutory-rule-sets.index')"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ form.processing ? 'Creating...' : 'Create Draft Rule Set' }}
                </button>

            </div>

        </form>
    </div>
</template>