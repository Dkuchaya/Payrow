<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface CompanyAccess {
    id: number
    name: string
    role: string | null
}

interface User {
    id: number
    name: string
    email: string
    phone?: string | null
    is_active: boolean
    is_super_admin: boolean
    company_access?: CompanyAccess[]
}

const props = defineProps<{
    user: User
}>()

const roleLabel = (name: string | null) => {
    if (!name) {
        return 'No role assigned'
    }

    return name
        .replaceAll('_', ' ')
        .replace(/\b\w/g, char => char.toUpperCase())
}

const initials = (name: string) => {
    return name
        .split(' ')
        .filter(Boolean)
        .map(word => word.charAt(0))
        .join('')
        .substring(0, 2)
        .toUpperCase()
}

const toggleStatus = () => {
    const action = props.user.is_active
        ? 'deactivate'
        : 'activate'

    if (!confirm(`Are you sure you want to ${action} this user?`)) {
        return
    }

    router.post(
        route('users.toggle-status', props.user.id),
        {},
        {
            preserveScroll: true,
        }
    )
}

const removeCompanyAccess = (company: CompanyAccess) => {
    if (
        !confirm(
            `Remove ${props.user.name} from ${company.name}?`
        )
    ) {
        return
    }

    router.delete(
        route(
            'users.remove-company-access',
            props.user.id
        ),
        {
            data: {
                company_id: company.id,
            },
            preserveScroll: true,
        }
    )
}
</script>

<template>
    <Head :title="user.name" />

    <div class="mx-auto max-w-5xl space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div
                    class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-lg font-bold text-blue-700"
                >
                    {{ initials(user.name) }}
                </div>

                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold text-gray-900">
                            {{ user.name }}
                        </h1>

                        <span
                            v-if="user.is_super_admin"
                            class="rounded-full bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-700"
                        >
                            Super Admin
                        </span>
                    </div>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ user.email }}
                    </p>
                </div>
            </div>

            <div class="flex gap-2">
                <Link
                    v-if="!user.is_super_admin"
                    :href="route('users.company-access', user.id)"
                    class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100"
                >
                    Company Access
                </Link>

                <Link
                    :href="route('users.index')"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    ← Back
                </Link>

                <Link
                    v-if="!user.is_super_admin"
                    :href="route('users.edit', user.id)"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                >
                    Edit User
                </Link>
            </div>
        </div>

        <!-- Status -->
        <div
            class="rounded-xl border p-4"
            :class="
                user.is_active
                    ? 'border-green-200 bg-green-50'
                    : 'border-red-200 bg-red-50'
            "
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p
                        class="text-sm font-semibold"
                        :class="
                            user.is_active
                                ? 'text-green-800'
                                : 'text-red-800'
                        "
                    >
                        Account
                        {{ user.is_active ? 'Active' : 'Inactive' }}
                    </p>

                    <p
                        class="mt-1 text-sm"
                        :class="
                            user.is_active
                                ? 'text-green-700'
                                : 'text-red-700'
                        "
                    >
                        {{
                            user.is_active
                                ? 'This user can access PAYROW.'
                                : 'This user cannot access PAYROW.'
                        }}
                    </p>
                </div>

                <button
                    v-if="!user.is_super_admin"
                    type="button"
                    class="rounded-lg border px-4 py-2 text-sm font-semibold"
                    :class="
                        user.is_active
                            ? 'border-red-300 bg-white text-red-600 hover:bg-red-50'
                            : 'border-green-300 bg-white text-green-600 hover:bg-green-50'
                    "
                    @click="toggleStatus"
                >
                    {{ user.is_active ? 'Deactivate User' : 'Activate User' }}
                </button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Account information -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
                <h2 class="text-lg font-semibold text-gray-900">
                    Account Information
                </h2>

                <div class="mt-6 grid gap-6 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Full Name
                        </p>

                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ user.name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Email
                        </p>

                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ user.email }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Phone
                        </p>

                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ user.phone || 'Not provided' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Account Type
                        </p>

                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ user.is_super_admin ? 'Super Admin' : 'Company User' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Roles by Company -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Roles by Company
                </h2>

                <div class="mt-4 space-y-3">
                    <div
                        v-for="access in (user.company_access ?? [])"
                        :key="`role-${access.id}`"
                        class="rounded-lg border border-gray-100 bg-gray-50 p-3"
                    >
                        <p class="text-sm font-semibold text-gray-900">
                            {{ access.name }}
                        </p>

                        <span
                            class="mt-2 inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"
                        >
                            {{ roleLabel(access.role) }}
                        </span>
                    </div>

                    <p
                        <p v-if="!(user.company_access ?? []).length"
                        class="text-sm text-gray-400"
                    >
                        No company role assigned.
                    </p>
                </div>
            </div>
        </div>

        <!-- Company Access -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-900">
                    Company Access
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Companies this user can access and their assigned roles.
                </p>
            </div>

            <div class="divide-y divide-gray-100">
                <div
                    v-for="access in user.company_access"
                    :key="access.id"
                    class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-sm font-bold text-gray-600"
                        >
                            {{ access.name?.charAt(0)?.toUpperCase() || '?' }}
                        </div>

                        <div>
                            <p class="font-medium text-gray-900">
                                {{ access.name }}
                            </p>

                            <p class="mt-1 text-xs text-gray-500">
                                Role:
                                <span class="font-semibold text-gray-700">
                                    {{ roleLabel(access.role) }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <button
                        v-if="!user.is_super_admin"
                        type="button"
                        class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50"
                        @click="removeCompanyAccess(access)"
                    >
                        Remove Access
                    </button>
                </div>

                <div
                     v-if="!(user.company_access ?? []).length"
                    class="px-6 py-10 text-center text-sm text-gray-400"
                >
                    This user has no company access.
                </div>
            </div>
        </div>
    </div>
</template>
