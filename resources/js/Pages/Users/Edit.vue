<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Company {
    id: number
    name: string
}

interface UserData {
    id: number
    name: string
    email: string
    phone?: string | null
    is_active: boolean
    company_id?: number | null
    role?: string | null
}

const props = defineProps<{
    user: UserData
    companies: Company[]
    roles: string[]
}>()

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone ?? '',
    company_id: props.user.company_id ?? '',
    role: props.user.role ?? '',
    is_active: props.user.is_active,
})

const roleLabel = (name: string) => {
    return name
        .replaceAll('_', ' ')
        .replace(/\b\w/g, char => char.toUpperCase())
}

const submit = () => {
    form.put(route('users.update', props.user.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Edit User" />

    <div class="mx-auto max-w-4xl space-y-6">

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Edit User
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Update user account information and access.
                </p>
            </div>

            <div class="flex gap-2">

                <Link
                    :href="route('users.show', user.id)"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    ← Back
                </Link>

            </div>

        </div>

        <form
            @submit.prevent="submit"
            class="space-y-6"
        >

            <!-- Account -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Account Information
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Update the user's basic account details.
                    </p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">

                    <!-- Name -->
                    <div class="md:col-span-2">

                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                            Full Name
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            :class="{ 'border-red-500': form.errors.name }"
                        />

                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.name }}
                        </p>

                    </div>

                    <!-- Email -->
                    <div>

                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                            Email Address
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            :class="{ 'border-red-500': form.errors.email }"
                        />

                        <p
                            v-if="form.errors.email"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.email }}
                        </p>

                    </div>

                    <!-- Phone -->
                    <div>

                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                            Phone Number
                        </label>

                        <input
                            v-model="form.phone"
                            type="text"
                            placeholder="+260..."
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            :class="{ 'border-red-500': form.errors.phone }"
                        />

                        <p
                            v-if="form.errors.phone"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.phone }}
                        </p>

                    </div>

                </div>

            </div>

            <!-- Company Access -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Company Access
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Manage the user's company and role.
                    </p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">

                    <!-- Company -->
                    <div>

                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                            Company
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            v-model="form.company_id"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            :class="{ 'border-red-500': form.errors.company_id }"
                        >
                            <option value="" disabled>
                                Select company
                            </option>

                            <option
                                v-for="company in companies"
                                :key="company.id"
                                :value="company.id"
                            >
                                {{ company.name }}
                            </option>
                        </select>

                        <p
                            v-if="form.errors.company_id"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.company_id }}
                        </p>

                    </div>

                    <!-- Role -->
                    <div>

                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                            Role
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            v-model="form.role"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            :class="{ 'border-red-500': form.errors.role }"
                        >
                            <option value="" disabled>
                                Select role
                            </option>

                            <option
                                v-for="role in roles"
                                :key="role"
                                :value="role"
                            >
                                {{ roleLabel(role) }}
                            </option>
                        </select>

                        <p
                            v-if="form.errors.role"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.role }}
                        </p>

                    </div>

                </div>

            </div>

            <!-- Account Status -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                <h2 class="text-lg font-semibold text-gray-900">
                    Account Status
                </h2>

                <div class="mt-4 flex items-center justify-between rounded-lg border border-gray-200 p-4">

                    <div>
                        <p class="text-sm font-medium text-gray-900">
                            User account
                        </p>

                        <p class="mt-1 text-sm text-gray-500">
                            Inactive users cannot sign in to PAYROW.
                        </p>
                    </div>

                    <label class="relative inline-flex cursor-pointer items-center">

                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="peer sr-only"
                        />

                        <div class="h-6 w-11 rounded-full bg-gray-300 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white"></div>

                    </label>

                </div>

            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3">

                <Link
                    :href="route('users.show', user.id)"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </button>

            </div>

        </form>

    </div>
</template>