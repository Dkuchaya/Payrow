<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

const props = defineProps<{
    positions: {
        data: Array<{
            id: number
            name: string
            code: string | null
            description: string | null
            is_active: boolean
            employees_count: number
            department: {
                id: number
                name: string
            } | null
        }>
        current_page: number
        last_page: number
        total: number
    }
    filters: {
        search: string
    }
}>()

const search = ref(props.filters.search ?? '')

let searchTimeout: ReturnType<typeof setTimeout>

watch(search, (value) => {
    clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        router.get(
            route('positions.index'),
            { search: value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        )
    }, 400)
})

function deletePosition(id: number) {
    if (!confirm('Are you sure you want to delete this position?')) {
        return
    }

    router.delete(route('positions.destroy', id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Positions
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Manage job positions for the active company.
                </p>
            </div>

            <Link
                :href="route('positions.create')"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
            >
                + Add Position
            </Link>
        </div>

        <!-- Search -->
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
            <div class="max-w-md">
                <label class="block text-sm font-medium text-gray-700">
                    Search
                </label>

                <input
                    v-model="search"
                    type="text"
                    placeholder="Search position or code..."
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Position
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Code
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Department
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Employees
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Status
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr
                            v-for="position in positions.data"
                            :key="position.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="font-medium text-gray-900">
                                    {{ position.name }}
                                </div>

                                <div
                                    v-if="position.description"
                                    class="mt-1 max-w-xs truncate text-sm text-gray-500"
                                >
                                    {{ position.description }}
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                {{ position.code || '—' }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                {{ position.department?.name || '—' }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                {{ position.employees_count }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    :class="position.is_active
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-gray-100 text-gray-600'"
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                >
                                    {{ position.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <Link
                                        :href="route('positions.edit', position.id)"
                                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Edit
                                    </Link>

                                    <button
                                        type="button"
                                        @click="deletePosition(position.id)"
                                        class="rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="positions.data.length === 0">
                            <td
                                colspan="6"
                                class="px-6 py-12 text-center text-sm text-gray-500"
                            >
                                No positions found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="positions.last_page > 1"
                class="flex items-center justify-between border-t border-gray-200 px-6 py-4"
            >
                <div class="text-sm text-gray-500">
                    Page {{ positions.current_page }}
                    of {{ positions.last_page }}
                </div>

                <div class="text-sm text-gray-500">
                    {{ positions.total }} positions
                </div>
            </div>
        </div>
    </div>
</template>