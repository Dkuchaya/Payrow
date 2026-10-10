<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})


const props = defineProps<{
    position: {
        id: number
        department_id: number | null
        name: string
        code: string | null
        description: string | null
        is_active: boolean
    }

    departments: Array<{
        id: number
        name: string
    }>
}>()

const form = useForm({
    department_id: props.position.department_id
        ? String(props.position.department_id)
        : '',
    name: props.position.name,
    code: props.position.code ?? '',
    description: props.position.description ?? '',
    is_active: props.position.is_active,
})

function submit() {
    form.put(route('positions.update', props.position.id))
}
</script>

<template>
    <div class="mx-auto max-w-3xl space-y-6">
        <!-- Header -->
        <div>
            <Link
                :href="route('positions.index')"
                class="text-sm font-medium text-blue-600 hover:text-blue-800"
            >
                ← Back to Positions
            </Link>

            <h1 class="mt-3 text-2xl font-bold text-gray-900">
                Edit Position
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Update the position details.
            </p>
        </div>

        <!-- Form -->
        <form
            @submit.prevent="submit"
            class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
        >
            <!-- Position Details -->
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Position Name
                    </label>

                    <input
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />

                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Position Code
                    </label>

                    <input
                        v-model="form.code"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />

                    <p
                        v-if="form.errors.code"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.code }}
                    </p>
                </div>
            </div>

            <!-- Department -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Department
                </label>

                <select
                    v-model="form.department_id"
                    class="mt-1 block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">
                        No Department
                    </option>

                    <option
                        v-for="department in departments"
                        :key="department.id"
                        :value="String(department.id)"
                    >
                        {{ department.name }}
                    </option>
                </select>

                <p
                    v-if="form.errors.department_id"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ form.errors.department_id }}
                </p>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Description
                </label>

                <textarea
                    v-model="form.description"
                    rows="4"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                ></textarea>

                <p
                    v-if="form.errors.description"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ form.errors.description }}
                </p>
            </div>

            <!-- Status -->
            <label class="flex items-center gap-3">
                <input
                    v-model="form.is_active"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />

                <span class="text-sm text-gray-700">
                    Active position
                </span>
            </label>

            <!-- Actions -->
            <div class="flex justify-end gap-3 border-t border-gray-200 pt-5">
                <Link
                    :href="route('positions.index')"
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
                            ? 'Updating...'
                            : 'Update Position'
                    }}
                </button>
            </div>
        </form>
    </div>
</template>