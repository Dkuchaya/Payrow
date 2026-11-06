
<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

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
}

const props = defineProps<{
    ruleSet: RuleSet
}>()

const form = useForm({
    country_name: props.ruleSet.country_name,
    name: props.ruleSet.name,
    effective_from: props.ruleSet.effective_from?.substring(0, 10) ?? '',
    effective_to: props.ruleSet.effective_to?.substring(0, 10) ?? '',
    notes: props.ruleSet.notes ?? '',
})

const submit = () => {
    form.put(route('statutory-rule-sets.update', props.ruleSet.id))
}
</script>

<template>
    <Head :title="`Edit ${ruleSet.name}`" />

    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <Link
                :href="route('statutory-rule-sets.show', ruleSet.id)"
                class="text-sm text-indigo-600 hover:text-indigo-800"
            >
                ← Back to Rule Set
            </Link>

            <h1 class="mt-3 text-2xl font-bold text-gray-900">
                Edit Statutory Rule Set
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Update the details of this draft statutory configuration.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-6 border-b border-gray-100 pb-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Rule Set Information
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Country: {{ ruleSet.country_name }} |
                    Tax Year: {{ ruleSet.tax_year }} |
                    Version: {{ ruleSet.version }}
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Country Name
                    </label>
                    <input
                        v-model="form.country_name"
                        type="text"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p v-if="form.errors.country_name" class="mt-1 text-sm text-red-600">
                        {{ form.errors.country_name }}
                    </p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Rule Set Name
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Effective From
                        </label>
                        <input
                            v-model="form.effective_from"
                            type="date"
                            required
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.effective_from" class="mt-1 text-sm text-red-600">
                            {{ form.errors.effective_from }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Effective To
                        </label>
                        <input
                            v-model="form.effective_to"
                            type="date"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.effective_to" class="mt-1 text-sm text-red-600">
                            {{ form.errors.effective_to }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Notes
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="4"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Enter notes about this statutory configuration..."
                    />
                    <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                        {{ form.errors.notes }}
                    </p>
                </div>

                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <p class="text-sm font-medium text-blue-900">
                        Draft configuration
                    </p>
                    <p class="mt-1 text-sm text-blue-800">
                        Only draft rule sets can be edited. Country code, tax year
                        and version are retained to protect version history.
                    </p>
                </div>

                <div class="flex flex-col-reverse justify-end gap-3 border-t border-gray-100 pt-5 sm:flex-row">
                    <Link
                        :href="route('statutory-rule-sets.show', ruleSet.id)"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
