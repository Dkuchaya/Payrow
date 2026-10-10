<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import CompanyLogo from '@/Components/CompanyLogo.vue'

interface User {
    id: number
    name: string
    email: string
    is_active: boolean
    joined_at: string | null
    roles: string[]
}

interface Company {
    id: number
    name: string
    logo: string | null
}

interface Pagination {
    data: User[]
    current_page: number
    last_page: number
    total: number
}

const props = defineProps<{
    company: Company
    users: Pagination
    roles: string[]
}>()

const showAddUser = ref(false)

const form = useForm({
    email: '',
    role: 'employee',
})

const roleLabels: Record<string, string> = {
    company_admin: 'Company Admin',
    hr_manager: 'HR Manager',
    hr_officer: 'HR Officer',
    payroll_manager: 'Payroll Manager',
    accountant: 'Accountant',
    supervisor: 'Supervisor',
    employee: 'Employee',
    auditor: 'Auditor',
}

const submit = () => {
    form.post(`/companies/${props.company.id}/users`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            form.role = 'employee'
            showAddUser.value = false
        },
    })
}

const toggleStatus = (user: User) => {
    router.patch(
        `/companies/${props.company.id}/users/${user.id}/status`,
        {},
        {
            preserveScroll: true,
        }
    )
}

const removeUser = (user: User) => {
    if (
        !confirm(
            `Remove ${user.name} from ${props.company.name}?`
        )
    ) {
        return
    }

    router.delete(
        `/companies/${props.company.id}/users/${user.id}`,
        {
            preserveScroll: true,
        }
    )
}

const updateRole = (user: User, role: string) => {
    router.patch(
        `/companies/${props.company.id}/users/${user.id}`,
        {
            role,
        },
        {
            preserveScroll: true,
        }
    )
}

const formatRole = (role: string) => {
    return roleLabels[role] ?? role
}

const totalUsers = computed(() => props.users.total)
</script>

<template>
    <Head :title="`${company.name} - Users`" />

    <AppLayout>
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex items-center gap-4">
                    <CompanyLogo
                        :logo="company.logo"
                        :name="company.name"
                        size="lg"
                    />

                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            Company Users
                        </h1>

                        <p class="text-sm text-gray-500">
                            Manage users and roles for
                            <span class="font-medium">
                                {{ company.name }}
                            </span>
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                    @click="showAddUser = true"
                >
                    + Add User
                </button>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 sm:grid-cols-3">

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-sm text-gray-500">
                        Total Users
                    </div>

                    <div class="mt-2 text-3xl font-bold text-gray-900">
                        {{ totalUsers }}
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-sm text-gray-500">
                        Active
                    </div>

                    <div class="mt-2 text-3xl font-bold text-green-600">
                        {{
                            users.data.filter(
                                user => user.is_active
                            ).length
                        }}
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-sm text-gray-500">
                        Inactive
                    </div>

                    <div class="mt-2 text-3xl font-bold text-gray-400">
                        {{
                            users.data.filter(
                                user => !user.is_active
                            ).length
                        }}
                    </div>
                </div>

            </div>

            <!-- Users -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="font-semibold text-gray-900">
                        Users
                    </h2>
                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    User
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Role
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Status
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
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

                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">
                                        {{ user.name }}
                                    </div>

                                    <div class="text-sm text-gray-500">
                                        {{ user.email }}
                                    </div>
                                </td>

                                <td class="px-6 py-4">

                                    <select
                                        :value="user.roles[0]"
                                        class="rounded-lg border-gray-300 text-sm"
                                        @change="
                                            updateRole(
                                                user,
                                                ($event.target as HTMLSelectElement).value
                                            )
                                        "
                                    >
                                        <option
                                            v-for="role in roles"
                                            :key="role"
                                            :value="role"
                                        >
                                            {{ formatRole(role) }}
                                        </option>
                                    </select>

                                </td>

                                <td class="px-6 py-4">

                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="
                                            user.is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-gray-100 text-gray-600'
                                        "
                                    >
                                        {{
                                            user.is_active
                                                ? 'Active'
                                                : 'Inactive'
                                        }}
                                    </span>

                                </td>

                                <td class="px-6 py-4">

                                    <div class="flex justify-end gap-2">

                                        <button
                                            type="button"
                                            class="rounded-lg px-3 py-2 text-xs font-semibold"
                                            :class="
                                                user.is_active
                                                    ? 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100'
                                                    : 'bg-green-50 text-green-700 hover:bg-green-100'
                                            "
                                            @click="toggleStatus(user)"
                                        >
                                            {{
                                                user.is_active
                                                    ? 'Deactivate'
                                                    : 'Activate'
                                            }}
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"
                                            @click="removeUser(user)"
                                        >
                                            Remove
                                        </button>

                                    </div>

                                </td>

                            </tr>

                            <tr v-if="users.data.length === 0">
                                <td
                                    colspan="4"
                                    class="px-6 py-12 text-center text-sm text-gray-500"
                                >
                                    No users have been assigned to this company yet.
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

            <!-- Add User Modal -->
            <div
                v-if="showAddUser"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            >

                <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">

                    <div class="border-b border-gray-200 px-6 py-5">
                        <h2 class="text-lg font-bold text-gray-900">
                            Add User
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Assign an existing PAYROW user to this company.
                        </p>
                    </div>

                    <form
                        class="space-y-5 p-6"
                        @submit.prevent="submit"
                    >

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">
                                Email Address
                            </label>

                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full rounded-xl border-gray-300"
                                placeholder="user@example.com"
                                required
                            />

                            <p
                                v-if="form.errors.email"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">
                                Company Role
                            </label>

                            <select
                                v-model="form.role"
                                class="w-full rounded-xl border-gray-300"
                            >
                                <option
                                    v-for="role in roles"
                                    :key="role"
                                    :value="role"
                                >
                                    {{ formatRole(role) }}
                                </option>
                            </select>

                            <p
                                v-if="form.errors.role"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.role }}
                            </p>
                        </div>

                        <div class="flex justify-end gap-3">

                            <button
                                type="button"
                                class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                                @click="showAddUser = false"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                            >
                                {{
                                    form.processing
                                        ? 'Adding...'
                                        : 'Add User'
                                }}
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>
    </AppLayout>
</template>