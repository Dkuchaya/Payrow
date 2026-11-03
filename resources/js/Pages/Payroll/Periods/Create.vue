<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
    name: '',
    start_date: '',
    end_date: '',
    pay_date: '',
    frequency: 'monthly',
    notes: '',
})

const submit = () => {
    form.post(route('payroll.periods.store'))
}
</script>

<template>
    <Head title="Create Payroll Period" />

    <AppLayout>
        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        Create Payroll Period
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Set up a new payroll period for your company.
                    </p>
                </div>

                <Link
                    :href="route('payroll.periods.index')"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Back to Payroll Periods
                </Link>
            </div>

            <form
                @submit.prevent="submit"
                class="max-w-3xl space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Period Name
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="e.g. October 2026 Payroll"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Start Date
                        </label>
                        <input
                            v-model="form.start_date"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        />
                        <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">
                            {{ form.errors.start_date }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            End Date
                        </label>
                        <input
                            v-model="form.end_date"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        />
                        <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">
                            {{ form.errors.end_date }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Pay Date
                        </label>
                        <input
                            v-model="form.pay_date"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        />
                        <p v-if="form.errors.pay_date" class="mt-1 text-sm text-red-600">
                            {{ form.errors.pay_date }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Payroll Frequency
                        </label>
                        <select
                            v-model="form.frequency"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        >
                            <option value="weekly">Weekly</option>
                            <option value="biweekly">Biweekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                        <p v-if="form.errors.frequency" class="mt-1 text-sm text-red-600">
                            {{ form.errors.frequency }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Notes
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        placeholder="Optional notes about this payroll period"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    />
                    <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                        {{ form.errors.notes }}
                    </p>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                    <Link
                        :href="route('payroll.periods.index')"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Create Payroll Period' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>