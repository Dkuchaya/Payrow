<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

defineOptions({
    layout: AppLayout,
})

interface RuleSet {
    id: number
    country_code: string
    country_name: string
    name: string
    tax_year: number
    version: number
    effective_from: string
    effective_to: string | null
    status: 'draft' | 'active' | 'archived'
    rules_count: number
    creator?: {
        id: number
        name: string
    }
}

interface PaginatedRuleSets {
    data: RuleSet[]
    current_page: number
    last_page: number
    total: number
    links: Array<{
        url: string | null
        label: string
        active: boolean
    }>
}

defineProps<{
    ruleSets: PaginatedRuleSets
}>()

const statusClasses = (status: string) => {
    const classes: Record<string, string> = {
        draft: 'bg-amber-100 text-amber-800',
        active: 'bg-green-100 text-green-800',
        archived: 'bg-gray-100 text-gray-700',
    }

    return classes[status] ?? 'bg-gray-100 text-gray-700'
}

const formatDate = (date: string | null) => {
    if (!date) return 'No end date'

    return new Date(date).toLocaleDateString()
}
</script>

<template>
    <Head title="Statutory Rule Sets" />

    <div class="mx-auto max-w-7xl space-y-6">

        <!-- Header -->
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Statutory Rule Sets
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Manage statutory rules, tax years and contribution configurations.
                </p>
            </div>

            <Link
                :href="route('statutory-rule-sets.create')"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
            >
                + Create Rule Set
            </Link>
        </div>

        <!-- Summary -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Total Rule Sets</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">
                    {{ ruleSets.total }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Active</p>
                <p class="mt-2 text-2xl font-bold text-green-600">
                    {{ ruleSets.data.filter(item => item.status === 'active').length }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Draft</p>
                <p class="mt-2 text-2xl font-bold text-amber-600">
                    {{ ruleSets.data.filter(item => item.status === 'draft').length }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Archived</p>
                <p class="mt-2 text-2xl font-bold text-gray-600">
                    {{ ruleSets.data.filter(item => item.status === 'archived').length }}
                </p>
            </div>

        </div>

        <!-- Rule Sets Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="font-semibold text-gray-900">
                    Configured Rule Sets
                </h2>
            </div>

            <div v-if="ruleSets.data.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">

                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Rule Set
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Country
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Tax Year
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Version
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Effective Period
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Rules
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                Status
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        <tr
                            v-for="item in ruleSets.data"
                            :key="item.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-4">
                                <p class="font-medium text-gray-900">
                                    {{ item.name }}
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    Created by {{ item.creator?.name ?? 'System' }}
                                </p>
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ item.country_name }}
                                <span class="text-xs text-gray-400">
                                    ({{ item.country_code }})
                                </span>
                            </td>

                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ item.tax_year }}
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-700">
                                v{{ item.version }}
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div>{{ formatDate(item.effective_from) }}</div>
                                <div class="text-xs text-gray-400">
                                    to {{ formatDate(item.effective_to) }}
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ item.rules_count }}
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-semibold capitalize"
                                    :class="statusClasses(item.status)"
                                >
                                    {{ item.status }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-3">

                                    <Link
                                        :href="route('statutory-rule-sets.show', item.id)"
                                        class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                    >
                                        View
                                    </Link>

                                    <Link
                                        v-if="item.status === 'draft'"
                                        :href="route('statutory-rule-sets.edit', item.id)"
                                        class="text-sm font-medium text-gray-600 hover:text-gray-900"
                                    >
                                        Edit
                                    </Link>

                                </div>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <div
                v-else
                class="px-6 py-16 text-center"
            >
                <h3 class="font-semibold text-gray-900">
                    No statutory rule sets found
                </h3>

                <p class="mt-2 text-sm text-gray-500">
                    Create your first statutory rule set to get started.
                </p>

                <Link
                    :href="route('statutory-rule-sets.create')"
                    class="mt-5 inline-flex rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                >
                    Create Rule Set
                </Link>
            </div>

            <!-- Pagination -->
            <div
                v-if="ruleSets.last_page > 1"
                class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-6 py-4"
            >
                <p class="text-sm text-gray-500">
                    Page {{ ruleSets.current_page }} of {{ ruleSets.last_page }}
                    ({{ ruleSets.total }} rule sets)
                </p>

                <div class="flex flex-wrap gap-2">
                    <template v-for="link in ruleSets.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-lg border px-3 py-1.5 text-sm"
                            :class="link.active
                                ? 'border-blue-600 bg-blue-600 text-white'
                                : 'border-gray-200 text-gray-700 hover:bg-gray-50'"
                        >
                            <span v-html="link.label" />
                        </Link>

                        <span
                            v-else
                            class="rounded-lg border border-gray-100 px-3 py-1.5 text-sm text-gray-300"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>

        </div>

    </div>
</template>
