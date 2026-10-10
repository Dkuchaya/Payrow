<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Company {
    id: number
    name: string
    is_active: boolean
    joined_at: string | null
    role: string | null
}

interface AvailableCompany {
    id: number
    name: string
}

interface User {
    id: number
    name: string
    email: string
    is_super_admin: boolean
}

const props = defineProps<{
    user: User
    companies: Company[]
    availableCompanies: AvailableCompany[]
    roles: string[]
}>()

const showAddModal = ref(false)
const editingCompany = ref<Company | null>(null)
const deletingCompany = ref<Company | null>(null)

const addForm = useForm({
    company_id: '',
    role: '',
})

const editForm = useForm({
    role: '',
    is_active: true,
})

const addCompany = () => {
    addForm.post(route('users.company-access.store', props.user.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false
            addForm.reset()
        },
    })
}

const openEdit = (company: Company) => {
    editingCompany.value = company

    editForm.role = company.role ?? ''
    editForm.is_active = company.is_active
}

const updateCompany = () => {
    if (!editingCompany.value) {
        return
    }

    editForm.put(
        route(
            'users.company-access.update',
            {
                user: props.user.id,
                company: editingCompany.value.id,
            }
        ),
        {
            preserveScroll: true,
            onSuccess: () => {
                editingCompany.value = null
            },
        }
    )
}

const removeCompany = () => {
    if (!deletingCompany.value) {
        return
    }

    router.delete(
        route(
            'users.company-access.destroy',
            {
                user: props.user.id,
                company: deletingCompany.value.id,
            }
        ),
        {
            preserveScroll: true,
            onSuccess: () => {
                deletingCompany.value = null
            },
        }
    )
}

const formatRole = (role: string | null) => {
    if (!role) {
        return 'No role'
    }

    return role
        .replace(/_/g, ' ')
        .replace(/\b\w/g, char => char.toUpperCase())
}

const availableCompanyOptions = computed(() => {
    return props.availableCompanies
})
</script>

<template>
    <Head title="Company Access" />

    <div class="min-h-screen bg-gray-50 p-6">
        <div class="mx-auto max-w-6xl">

            <!-- Header -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="mb-2 flex items-center gap-2 text-sm text-gray-500">
                        <Link
                            :href="route('users.index')"
                            class="hover:text-blue-600"
                        >
                            Users
                        </Link>

                        <span>/</span>

                        <span>Company Access</span>
                    </div>

                    <h1 class="text-2xl font-bold text-gray-900">
                        Company Access
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Manage which companies this user can access and their
                        role in each company.
                    </p>
                </div>

                <div class="flex gap-2">
                    <Link
                        :href="route('users.show', props.user.id)"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Back to User
                    </Link>

                    <button
                        v-if="!props.user.is_super_admin"
                        type="button"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        @click="showAddModal = true"
                    >
                        + Add Company
                    </button>
                </div>
            </div>

            <!-- User Card -->
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-lg font-bold text-blue-700"
                    >
                        {{ props.user.name.charAt(0).toUpperCase() }}
                    </div>

                    <div>
                        <h2 class="font-semibold text-gray-900">
                            {{ props.user.name }}
                        </h2>

                        <p class="text-sm text-gray-500">
                            {{ props.user.email }}
                        </p>
                    </div>

                    <div class="ml-auto">
                        <span
                            v-if="props.user.is_super_admin"
                            class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700"
                        >
                            Super Admin
                        </span>
                    </div>
                </div>
            </div>

            <!-- Super Admin Notice -->
            <div
                v-if="props.user.is_super_admin"
                class="mb-6 rounded-xl border border-purple-200 bg-purple-50 p-4"
            >
                <div class="flex gap-3">
                    <div class="text-purple-600">
                        ⚡
                    </div>

                    <div>
                        <h3 class="font-semibold text-purple-900">
                            Super Admin
                        </h3>

                        <p class="mt-1 text-sm text-purple-700">
                            This user has platform-level access and does not
                            require individual company memberships.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Company Access -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-semibold text-gray-900">
                        Company Access
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ companies.length }}
                        {{ companies.length === 1 ? 'company' : 'companies' }}
                    </p>
                </div>

                <div
                    v-if="companies.length === 0"
                    class="p-10 text-center"
                >
                    <div class="text-4xl">
                        🏢
                    </div>

                    <h3 class="mt-3 font-semibold text-gray-900">
                        No company access
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        This user has not been assigned to any company.
                    </p>
                </div>

                <div v-else class="divide-y divide-gray-100">

                    <div
                        v-for="company in companies"
                        :key="company.id"
                        class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-center gap-4">

                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-lg bg-gray-100 text-gray-600"
                            >
                                🏢
                            </div>

                            <div>
                                <h3 class="font-semibold text-gray-900">
                                    {{ company.name }}
                                </h3>

                                <div class="mt-1 flex flex-wrap items-center gap-2">

                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="
                                            company.is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-gray-100 text-gray-600'
                                        "
                                    >
                                        {{
                                            company.is_active
                                                ? 'Active'
                                                : 'Inactive'
                                        }}
                                    </span>

                                    <span
                                        class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700"
                                    >
                                        {{ formatRole(company.role) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2">

                            <button
                                type="button"
                                class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                @click="openEdit(company)"
                            >
                                Edit
                            </button>

                            <button
                                v-if="!props.user.is_super_admin"
                                type="button"
                                class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                                @click="deletingCompany = company"
                            >
                                Remove
                            </button>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Add Company Modal -->
    <div
        v-if="showAddModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
        <div class="w-full max-w-md rounded-xl bg-white shadow-xl">

            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="font-semibold text-gray-900">
                    Add Company Access
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Give {{ props.user.name }} access to another company.
                </p>
            </div>

            <form @submit.prevent="addCompany">

                <div class="space-y-5 p-6">

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Company
                        </label>

                        <select
                            v-model="addForm.company_id"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            required
                        >
                            <option value="">
                                Select company
                            </option>

                            <option
                                v-for="company in availableCompanyOptions"
                                :key="company.id"
                                :value="company.id"
                            >
                                {{ company.name }}
                            </option>
                        </select>

                        <p
                            v-if="addForm.errors.company_id"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ addForm.errors.company_id }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Role
                        </label>

                        <select
                            v-model="addForm.role"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            required
                        >
                            <option value="">
                                Select role
                            </option>

                            <option
                                v-for="role in roles"
                                :key="role"
                                :value="role"
                            >
                                {{ formatRole(role) }}
                            </option>
                        </select>

                        <p
                            v-if="addForm.errors.role"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ addForm.errors.role }}
                        </p>
                    </div>

                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">

                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        @click="showAddModal = false"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="addForm.processing"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{
                            addForm.processing
                                ? 'Adding...'
                                : 'Add Company'
                        }}
                    </button>

                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div
        v-if="editingCompany"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
        <div class="w-full max-w-md rounded-xl bg-white shadow-xl">

            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="font-semibold text-gray-900">
                    Edit Company Access
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    {{ editingCompany.name }}
                </p>
            </div>

            <form @submit.prevent="updateCompany">

                <div class="space-y-5 p-6">

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Role
                        </label>

                        <select
                            v-model="editForm.role"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            required
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
                            v-if="editForm.errors.role"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ editForm.errors.role }}
                        </p>
                    </div>

                    <label class="flex cursor-pointer items-center gap-3">
                        <input
                            v-model="editForm.is_active"
                            type="checkbox"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        >

                        <span class="text-sm font-medium text-gray-700">
                            Active access
                        </span>
                    </label>

                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">

                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        @click="editingCompany = null"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="editForm.processing"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{
                            editForm.processing
                                ? 'Saving...'
                                : 'Save Changes'
                        }}
                    </button>

                </div>

            </form>
        </div>
    </div>

    <!-- Delete Confirmation -->
    <div
        v-if="deletingCompany"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
        <div class="w-full max-w-md rounded-xl bg-white shadow-xl">

            <div class="p-6">

                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600">
                    ⚠
                </div>

                <h2 class="text-lg font-semibold text-gray-900">
                    Remove company access?
                </h2>

                <p class="mt-2 text-sm text-gray-500">
                    Are you sure you want to remove
                    <strong>{{ props.user.name }}</strong>
                    from
                    <strong>{{ deletingCompany.name }}</strong>?
                </p>

                <p class="mt-2 text-sm text-red-600">
                    The user will no longer be able to access this company.
                </p>

            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">

                <button
                    type="button"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    @click="deletingCompany = null"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                    @click="removeCompany"
                >
                    Remove Access
                </button>

            </div>
        </div>
    </div>
</template>