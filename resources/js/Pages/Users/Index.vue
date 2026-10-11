<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Company {
    id: number
    name: string
}

interface Role {
    id: number
    name: string
}
interface CompanyAccess {
    id: number
    name: string
    role: string | null
}

interface User {
    id: number
    name: string
    email: string
    is_active: boolean
    is_super_admin: boolean
    company_access?: CompanyAccess[]
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface UsersPagination {
    data: User[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    links: PaginationLink[]
}

interface Filters {
    search?: string
    status?: string
    role?: string
}

const props = defineProps<{
    users: UsersPagination
    filters: Filters
    roles: string[]
}>()

const page = usePage()

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')
const role = ref(props.filters.role ?? '')

const showFilters = ref(false)

const applyFilters = () => {
    router.get(
        route('users.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            role: role.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        }
    )
}

const clearFilters = () => {
    search.value = ''
    status.value = ''
    role.value = ''

    router.get(
        route('users.index'),
        {},
        {
            preserveState: true,
            replace: true,
        }
    )
}

const toggleStatus = (user: User) => {
    const action = user.is_active ? 'deactivate' : 'activate'

    if (
        !confirm(
            `Are you sure you want to ${action} ${user.name}?`
        )
    ) {
        return
    }

    router.post(
        route('users.toggle-status', user.id),
        {},
        {
            preserveScroll: true,
        }
    )
}

const roleLabel = (name: string | null) => {
    if (!name) {
        return 'No Role'
    }

    return name
        .replaceAll('_', ' ')
        .replace(/\b\w/g, char => char.toUpperCase())
}

const initials = (name: string) => {
    return name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .substring(0, 2)
        .toUpperCase()
}

const hasFilters = computed(() => {
    return !!search.value || !!status.value || !!role.value
})

const flash = computed(() => {
    return page.props.flash as {
        success?: string
        error?: string
    }
})
</script>

<template>
    <Head title="Users" />

    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Users
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Manage users, company access and roles.
                </p>
            </div>

            <Link
                :href="route('users.create')"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            >
                <span class="text-lg">+</span>
                Add User
            </Link>

        </div>

        <!-- Search / Filters -->
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">

            <div class="flex flex-col gap-3 lg:flex-row">

                <!-- Search -->
                <div class="relative flex-1">

                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by name or email..."
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        @keyup.enter="applyFilters"
                    />

                </div>

                <!-- Filters button -->
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    @click="showFilters = !showFilters"
                >
                    Filters
                </button>

                <button
                    type="button"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                    @click="applyFilters"
                >
                    Search
                </button>

                <button
                    v-if="hasFilters"
                    type="button"
                    class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50"
                    @click="clearFilters"
                >
                    Clear
                </button>

            </div>

            <!-- Filter options -->
            <div
                v-if="showFilters"
                class="mt-4 grid gap-4 border-t border-gray-100 pt-4 md:grid-cols-2"
            >

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">
                        Status
                    </label>

                    <select
                        v-model="status"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">
                        Role
                    </label>

                    <select
                        v-model="role"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >
                        <option value="">All Roles</option>

                        <option
                            v-for="item in roles"
                            :key="item"
                            :value="item"
                        >
                            {{ roleLabel(item) }}
                        </option>
                    </select>
                </div>

            </div>

        </div>

        <!-- Users table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-200">

                    <thead class="bg-gray-50">

                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                User
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Company
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Role
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Status
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Actions
                            </th>
                        </tr>

                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="transition hover:bg-gray-50"
                        >

                            <!-- User -->
                            <td class="whitespace-nowrap px-6 py-4">

                                <div class="flex items-center gap-3">

                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700"
                                    >
                                        {{ initials(user.name) }}
                                    </div>

                                    <div>
                                        <div class="font-semibold text-gray-900">
                                            {{ user.name }}

                                            <span
                                                v-if="user.is_super_admin"
                                                class="ml-2 rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700"
                                            >
                                                Super Admin
                                            </span>
                                        </div>

                                        <div class="text-sm text-gray-500">
                                            {{ user.email }}
                                        </div>
                                    </div>

                                </div>

                            </td>

                            <!-- Companies -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <span
                                            v-for="access in (user.company_access ?? [])"
                                            :key="access.id"
                                            class="block text-sm text-gray-700"
                                        >
                                            {{ access.name }}
                                        </span>

                                        <span
                                            v-if="!(user.company_access ?? []).length"
                                            class="text-sm text-gray-400"
                                        >
                                            No company
                                        </span>
                                    </div>
                                </td>

                            <!-- Roles -->
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <div
                                        v-for="access in (user.company_access ?? [])"
                                        :key="access.id"
                                        class="text-sm"
                                    >
                                        <span class="font-medium text-gray-900">
                                            {{ access.name }}
                                        </span>

                                        <span class="text-gray-500">
                                            — {{ roleLabel(access.role) }}
                                        </span>
                                    </div>

                                    <span
                                        v-if="!(user.company_access ?? []).length"
                                        class="text-sm text-gray-400"
                                    >
                                        No company access
                                    </span>
                                </div>
                            </td>
                            <!-- Status -->
                            <td class="whitespace-nowrap px-6 py-4">

                                <span
                                    v-if="user.is_active"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                    Active
                                </span>

                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                    Inactive
                                </span>

                            </td>

                            <!-- Actions -->
                            <td class="whitespace-nowrap px-6 py-4 text-right">

                                <div class="flex justify-end gap-2">

                                    <Link
                                        :href="route('users.show', user.id)"
                                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        View
                                    </Link>

                                    <Link
                                        :href="route('users.edit', user.id)"
                                        class="rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-medium text-blue-600 hover:bg-blue-50"
                                    >
                                        Edit
                                    </Link>

                                    <button
                                        v-if="!user.is_super_admin"
                                        type="button"
                                        class="rounded-lg border px-3 py-1.5 text-xs font-medium"
                                        :class="
                                            user.is_active
                                                ? 'border-red-200 text-red-600 hover:bg-red-50'
                                                : 'border-green-200 text-green-600 hover:bg-green-50'
                                        "
                                        @click="toggleStatus(user)"
                                    >
                                        {{ user.is_active ? 'Deactivate' : 'Activate' }}
                                    </button>

                                </div>

                            </td>

                        </tr>

                        <!-- Empty -->
                        <tr v-if="!users.data.length">

                            <td
                                colspan="5"
                                class="px-6 py-12 text-center"
                            >
                                <div class="text-gray-400">
                                    <div class="text-4xl">👥</div>

                                    <p class="mt-3 text-sm font-medium text-gray-600">
                                        No users found
                                    </p>

                                    <p class="mt-1 text-sm text-gray-400">
                                        Try changing your search or filters.
                                    </p>
                                </div>
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

            <!-- Pagination -->
            <div
                v-if="users.last_page > 1"
                class="flex flex-col gap-3 border-t border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
            >

                <p class="text-sm text-gray-500">
                    Showing
                    <span class="font-medium text-gray-700">
                        {{ (users.current_page - 1) * users.per_page + 1 }}
                    </span>
                    to
                    <span class="font-medium text-gray-700">
                        {{ Math.min(users.current_page * users.per_page, users.total) }}
                    </span>
                    of
                    <span class="font-medium text-gray-700">
                        {{ users.total }}
                    </span>
                    users
                </p>

                <div class="flex flex-wrap gap-1">

                    <Link
                        v-for="(link, index) in users.links"
                        :key="index"
                        :href="link.url || '#'"
                        :class="[
                            'rounded-lg px-3 py-1.5 text-sm',
                            link.active
                                ? 'bg-blue-600 font-semibold text-white'
                                : 'text-gray-600 hover:bg-gray-100',
                            !link.url
                                ? 'pointer-events-none opacity-40'
                                : ''
                        ]"
                        preserve-scroll
                    >
                        <span v-html="link.label"></span>
                    </Link>

                </div>

            </div>

        </div>

    </div>
</template>