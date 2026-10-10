<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

const form = useForm({
    name: '',
    code: '',
    description: '',
    is_active: true,
})

function submit() {
    form.post(route('departments.store'))
}
</script>

<template>
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <Link
                :href="route('departments.index')"
                class="text-sm text-blue-600 hover:text-blue-800"
            >
                ← Back to Departments
            </Link>

            <h1 class="mt-3 text-2xl font-bold text-gray-900">
                Add Department
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Create a department for the active company.
            </p>
        </div>

        <form
            @submit.prevent="submit"
            class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
        >
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Department Name
                    </label>

                    <input
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Human Resources"
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
                        Department Code
                    </label>

                    <input
                        v-model="form.code"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="HR"
                    />

                    <p
                        v-if="form.errors.code"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.code }}
                    </p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Description
                </label>

                <textarea
                    v-model="form.description"
                    rows="4"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Describe the department..."
                ></textarea>

                <p
                    v-if="form.errors.description"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ form.errors.description }}
                </p>
            </div>

            <label class="flex items-center gap-3">
                <input
                    v-model="form.is_active"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />

                <span class="text-sm text-gray-700">
                    Active department
                </span>
            </label>

            <div class="flex justify-end gap-3 border-t pt-5">
                <Link
                    :href="route('departments.index')"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ form.processing ? 'Saving...' : 'Save Department' }}
                </button>
            </div>
        </form>
    </div>
</template>