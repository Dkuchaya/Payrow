<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Department {
    id: number
    name: string
    code: string | null
    description: string | null
    employees_count: number
    manager: {
        id: number
        first_name: string
        last_name: string
    } | null
    is_active: boolean
}

const props = defineProps<{
    departments: {
        data: Department[]
        current_page: number
        last_page: number
        total: number
    }
    filters: {
        search: string
    }
}>()

const search = ref(props.filters.search || '')

function searchDepartments() {
    router.get(
        route('departments.index'),
        {
            search: search.value,
        },
        {
            preserveState: true,
            replace: true,
        }
    )
}

function deleteDepartment(department: Department) {
    if (!confirm(
        `Delete department "${department.name}"?`
    )) {
        return
    }

    router.delete(
        route('departments.destroy', department.id)
    )
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Departments
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Manage your company's departments.
                </p>
            </div>

            <Link
                :href="route('departments.create')"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
            >
                + Add Department
            </Link>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
            <form
                @submit.prevent="searchDepartments"
                class="flex gap-3"
            >
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search departments..."
                    class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />

                <button
                    type="submit"
                    class="rounded-lg bg-gray-900 px-5 py-2 text-sm font-medium text-white hover:bg-gray-800"
                >
                    Search
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                            Department
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                            Code
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                            Manager
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                            Employees
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                            Status
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    <tr
                        v-for="department in departments.data"
                        :key="department.id"
                        class="hover:bg-gray-50"
                    >
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">
                                {{ department.name }}
                            </div>

                            <div
                                v-if="department.description"
                                class="text-xs text-gray-500"
                            >
                                {{ department.description }}
                            </div>
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ department.code || '—' }}
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{
                                department.manager
                                    ? `${department.manager.first_name} ${department.manager.last_name}`
                                    : 'Not assigned'
                            }}
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ department.employees_count }}
                        </td>

                        <td class="px-6 py-4">
                            <span
                                :class="department.is_active
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-gray-100 text-gray-600'"
                                class="rounded-full px-3 py-1 text-xs font-medium"
                            >
                                {{ department.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-right text-sm">
                            <Link
                                :href="route('departments.edit', department.id)"
                                class="mr-3 font-medium text-blue-600 hover:text-blue-800"
                            >
                                Edit
                            </Link>

                            <button
                                @click="deleteDepartment(department)"
                                class="font-medium text-red-600 hover:text-red-800"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>

                    <tr v-if="departments.data.length === 0">
                        <td
                            colspan="6"
                            class="px-6 py-12 text-center text-sm text-gray-500"
                        >
                            No departments found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>