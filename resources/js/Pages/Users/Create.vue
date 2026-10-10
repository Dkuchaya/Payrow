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

const props = defineProps<{
    companies: Company[]
    roles: string[]
}>()

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    company_id: props.companies[0]?.id ?? '',
    role: props.roles[0] ?? '',
})

const roleLabel = (name: string) => {
    return name
        .replaceAll('_', ' ')
        .replace(/\b\w/g, char => char.toUpperCase())
}

const submit = () => {
    form.post(route('users.store'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Create User" />

    <div class="mx-auto max-w-4xl space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">

            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Create User
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Create a user account and assign company access.
                </p>
            </div>

            <Link
                :href="route('users.index')"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                ← Back
            </Link>

        </div>

        <!-- Form -->
        <form
            @submit.prevent="submit"
            class="space-y-6"
        >

            <!-- Account Information -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Account Information
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Basic information used to access PAYROW.
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
                            placeholder="Enter full name"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
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
                            placeholder="user@example.com"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
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
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            :class="{ 'border-red-500': form.errors.phone }"
                        />

                        <p
                            v-if="form.errors.phone"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.phone }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                            Password
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            v-model="form.password"
                            type="password"
                            placeholder="Minimum 8 characters"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            :class="{ 'border-red-500': form.errors.password }"
                        />

                        <p
                            v-if="form.errors.password"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Confirm password -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                            Confirm Password
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Repeat password"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
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
                        Select the company and role this user will have.
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

                <!-- Role warning -->
                <div class="mt-5 rounded-lg bg-blue-50 p-4">
                    <p class="text-sm text-blue-800">
                        <strong>Note:</strong>
                        The selected role controls what this user can access
                        within the selected company.
                    </p>
                </div>

            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3">

                <Link
                    :href="route('users.index')"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ form.processing ? 'Creating...' : 'Create User' }}
                </button>

            </div>

        </form>

    </div>
</template>