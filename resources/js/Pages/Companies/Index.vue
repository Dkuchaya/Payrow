<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

defineOptions({
    layout: AppLayout,
})

defineProps<{
    companies: {
        data: Array<{
            id: number
            name: string
            legal_name: string | null
            email: string | null
            city: string | null
            currency: string
            is_active: boolean
            users_count: number
        }>
    }
}>()
</script>

<template>

    <Head title="Companies" />

    <div class="space-y-6">

        <div class="flex items-center justify-between">

            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Companies
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Manage companies registered on PAYROW.
                </p>
            </div>

            <Link
                href="/companies/create"
                class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
            >
                + Add Company
            </Link>

        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            <table class="min-w-full divide-y divide-gray-200">

                <thead class="bg-gray-50">

                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500">
                            Company
                        </th>

                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500">
                            Location
                        </th>

                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500">
                            Users
                        </th>

                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500">
                            Status
                        </th>

                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase text-gray-500">
                            Action
                        </th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-gray-100">

                    <tr
                        v-for="company in companies.data"
                        :key="company.id"
                        class="hover:bg-gray-50"
                    >

                    <td class="px-6 py-4">

                            <div class="flex items-center gap-3">

                                <div
                                    class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50"
                                >

                                    <img
                                        v-if="company.logo"
                                        :src="`/storage/${company.logo}`"
                                        :alt="company.name"
                                        class="h-full w-full object-contain"
                                    />

                                    <span
                                        v-else
                                        class="font-bold text-gray-400"
                                    >
                                        {{ company.name.charAt(0) }}
                                    </span>

                                </div>

                                <div>
                                    <div class="font-semibold text-gray-900">
                                        {{ company.name }}
                                    </div>

                                    <div class="text-sm text-gray-500">
                                        {{ company.legal_name }}
                                    </div>
                                </div>

                            </div>

                        </td>

                        <td class="px-6 py-4">

                            <div class="font-semibold text-gray-900">
                                {{ company.name }}
                            </div>

                            <div class="text-sm text-gray-500">
                                {{ company.legal_name }}
                            </div>

                        </td>

                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ company.city || '—' }}
                        </td>

                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ company.users_count }}
                        </td>

                        <td class="px-6 py-4">

                            <span
                                class="rounded-full px-3 py-1 text-xs font-semibold"
                                :class="company.is_active
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700'"
                            >
                                {{ company.is_active ? 'Active' : 'Inactive' }}
                            </span>

                        </td>

                        <td class="px-6 py-4 text-right">

                            <Link
                                :href="`/companies/${company.id}/edit`"
                                class="text-sm font-medium text-blue-600 hover:text-blue-800"
                            >
                                Edit
                            </Link>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</template>