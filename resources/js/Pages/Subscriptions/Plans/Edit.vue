<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Price {
    id: number
    billing_cycle: string
    amount: string | number
    is_active: boolean
}

interface Plan {
    id: number
    name: string
    slug: string
    description: string | null
    employee_limit: number | null
    is_active: boolean
    prices: Price[]
}

const props = defineProps<{
    plan: Plan
}>()

function getPrice(cycle: string): number {
    const price = props.plan.prices.find(
        (item) => item.billing_cycle === cycle
    )

    return price ? Number(price.amount) : 0
}

const form = useForm({
    name: props.plan.name,
    slug: props.plan.slug,
    description: props.plan.description ?? '',
    employee_limit: props.plan.employee_limit as number | null,
    is_active: props.plan.is_active,

    prices: {
        monthly: getPrice('monthly'),
        quarterly: getPrice('quarterly'),
        semi_annual: getPrice('semi_annual'),
        annual: getPrice('annual'),
    },
})

function submit() {
    form.put(
        route('subscription-plans.update', props.plan.id)
    )
}
</script>

<template>
    <div class="mx-auto max-w-4xl space-y-6">

        <!-- Header -->
        <div>
            <Link
                :href="route('subscription-plans.index')"
                class="text-sm font-medium text-blue-600 hover:text-blue-800"
            >
                ← Back to Subscription Plans
            </Link>

            <h1 class="mt-3 text-2xl font-bold text-gray-900">
                Edit Subscription Plan
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Update plan information, employee limits and pricing.
            </p>
        </div>

        <form
            @submit.prevent="submit"
            class="space-y-6"
        >

            <!-- Plan Information -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

                <h2 class="text-lg font-semibold text-gray-900">
                    Plan Information
                </h2>

                <div class="mt-5 grid gap-6 md:grid-cols-2">

                    <!-- Name -->
                    <div>
                        <label class="label">
                            Plan Name
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            class="input"
                        />

                        <p
                            v-if="form.errors.name"
                            class="error"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Slug -->
                    <div>
                        <label class="label">
                            Slug
                        </label>

                        <input
                            v-model="form.slug"
                            type="text"
                            class="input"
                        />

                        <p
                            v-if="form.errors.slug"
                            class="error"
                        >
                            {{ form.errors.slug }}
                        </p>
                    </div>

                    <!-- Employee Limit -->
                    <div>
                        <label class="label">
                            Employee Limit
                        </label>

                        <input
                            v-model.number="form.employee_limit"
                            type="number"
                            min="1"
                            class="input"
                            placeholder="Leave empty for unlimited"
                        />

                        <p class="mt-1 text-xs text-gray-500">
                            Leave empty for unlimited employees.
                        </p>

                        <p
                            v-if="form.errors.employee_limit"
                            class="error"
                        >
                            {{ form.errors.employee_limit }}
                        </p>
                    </div>

                    <!-- Status -->
                    <div class="flex items-center pt-7">
                        <label class="flex items-center gap-3">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />

                            <span class="text-sm font-medium text-gray-700">
                                Active plan
                            </span>
                        </label>
                    </div>

                </div>

                <!-- Description -->
                <div class="mt-6">
                    <label class="label">
                        Description
                    </label>

                    <textarea
                        v-model="form.description"
                        rows="4"
                        class="input"
                    ></textarea>

                    <p
                        v-if="form.errors.description"
                        class="error"
                    >
                        {{ form.errors.description }}
                    </p>
                </div>

            </div>

            <!-- Pricing -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

                <h2 class="text-lg font-semibold text-gray-900">
                    Pricing
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Update the price for each billing cycle.
                </p>

                <div class="mt-5 grid gap-6 md:grid-cols-2">

                    <!-- Monthly -->
                    <div>
                        <label class="label">
                            Monthly Price
                        </label>

                        <div class="relative mt-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">
                                K
                            </span>

                            <input
                                v-model.number="form.prices.monthly"
                                type="number"
                                min="0"
                                step="0.01"
                                class="input pl-8"
                            />
                        </div>

                        <p
                            v-if="form.errors['prices.monthly']"
                            class="error"
                        >
                            {{ form.errors['prices.monthly'] }}
                        </p>
                    </div>

                    <!-- Quarterly -->
                    <div>
                        <label class="label">
                            Quarterly Price
                        </label>

                        <div class="relative mt-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">
                                K
                            </span>

                            <input
                                v-model.number="form.prices.quarterly"
                                type="number"
                                min="0"
                                step="0.01"
                                class="input pl-8"
                            />
                        </div>

                        <p
                            v-if="form.errors['prices.quarterly']"
                            class="error"
                        >
                            {{ form.errors['prices.quarterly'] }}
                        </p>
                    </div>

                    <!-- Semi Annual -->
                    <div>
                        <label class="label">
                            Semi-Annual Price
                        </label>

                        <div class="relative mt-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">
                                K
                            </span>

                            <input
                                v-model.number="form.prices.semi_annual"
                                type="number"
                                min="0"
                                step="0.01"
                                class="input pl-8"
                            />
                        </div>

                        <p
                            v-if="form.errors['prices.semi_annual']"
                            class="error"
                        >
                            {{ form.errors['prices.semi_annual'] }}
                        </p>
                    </div>

                    <!-- Annual -->
                    <div>
                        <label class="label">
                            Annual Price
                        </label>

                        <div class="relative mt-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">
                                K
                            </span>

                            <input
                                v-model.number="form.prices.annual"
                                type="number"
                                min="0"
                                step="0.01"
                                class="input pl-8"
                            />
                        </div>

                        <p
                            v-if="form.errors['prices.annual']"
                            class="error"
                        >
                            {{ form.errors['prices.annual'] }}
                        </p>
                    </div>

                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 border-t pt-5">

                <Link
                    :href="route('subscription-plans.index')"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </button>

            </div>

        </form>
    </div>
</template>

<style scoped>
.label {
    @apply block text-sm font-medium text-gray-700;
}

.input {
    @apply mt-1 block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500;
}

.error {
    @apply mt-1 text-sm text-red-600;
}
</style>