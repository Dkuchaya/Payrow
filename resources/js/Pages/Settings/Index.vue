<script setup lang="ts">

import { computed } from 'vue'
import { Link,useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface User {
    name: string
    email: string
    phone: string | null
}

interface Settings {
    timezone: string
    date_format: string
    language: string
    email_notifications: boolean
    system_notifications: boolean
}

const page = usePage()

const user = computed<User>(() => page.props.user as User)

const settings = computed<Settings>(
    () => page.props.settings as Settings
)

const timezones = computed<string[]>(
    () => (page.props.timezones as string[]) ?? []
)

/*
|--------------------------------------------------------------------------
| Account
|--------------------------------------------------------------------------
*/

const accountForm = useForm({
    name: user.value?.name ?? '',
    email: user.value?.email ?? '',
    phone: user.value?.phone ?? '',
})

const updateAccount = () => {
    accountForm.put('/settings/account', {
        preserveScroll: true,
    })
}

/*
|--------------------------------------------------------------------------
| Preferences
|--------------------------------------------------------------------------
*/

const preferencesForm = useForm({
    timezone: settings.value?.timezone ?? 'Africa/Lusaka',
    date_format: settings.value?.date_format ?? 'd/m/Y',
    language: settings.value?.language ?? 'en',
})

const updatePreferences = () => {
    preferencesForm.put('/settings/preferences', {
        preserveScroll: true,
    })
}

/*
|--------------------------------------------------------------------------
| Notifications
|--------------------------------------------------------------------------
*/

const notificationsForm = useForm({
    email_notifications:
        settings.value?.email_notifications ?? true,

    system_notifications:
        settings.value?.system_notifications ?? true,
})

const updateNotifications = () => {
    notificationsForm.put('/settings/notifications', {
        preserveScroll: true,
    })
}

/*
|--------------------------------------------------------------------------
| Password
|--------------------------------------------------------------------------
*/

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const updatePassword = () => {
    passwordForm.put('/settings/password', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset()
        },
    })
}
</script>

<template>
    <div class="mx-auto max-w-5xl space-y-8">

        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                Settings
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Manage your PAYROW account, preferences and security.
            </p>
        </div>

        <div
    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
>
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Company Settings
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Manage company information, branding and financial settings.
            </p>
        </div>

        <Link
            href="/settings/company"
            class="shrink-0 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
        >
            Manage Company
        </Link>
    </div>
</div>


        <!-- Account -->
        <section
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
        >
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-900">
                    Account
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Update your personal account information.
                </p>
            </div>

            <form
                @submit.prevent="updateAccount"
                class="space-y-6 p-6"
            >
                <div class="grid gap-6 md:grid-cols-2">

                    <!-- Name -->
                    <div>
                        <label
                            for="name"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Full Name
                        </label>

                        <input
                            id="name"
                            v-model="accountForm.name"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <p
                            v-if="accountForm.errors.name"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ accountForm.errors.name }}
                        </p>
                    </div>


                    <!-- Email -->
                    <div>
                        <label
                            for="email"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Email Address
                        </label>

                        <input
                            id="email"
                            v-model="accountForm.email"
                            type="email"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <p
                            v-if="accountForm.errors.email"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ accountForm.errors.email }}
                        </p>
                    </div>


                    <!-- Phone -->
                    <div>
                        <label
                            for="phone"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Phone Number
                        </label>

                        <input
                            id="phone"
                            v-model="accountForm.phone"
                            type="text"
                            placeholder="+260..."
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <p
                            v-if="accountForm.errors.phone"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ accountForm.errors.phone }}
                        </p>
                    </div>

                </div>


                <div class="flex justify-end border-t border-gray-100 pt-5">

                    <button
                        type="submit"
                        :disabled="accountForm.processing"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            accountForm.processing
                                ? 'Saving...'
                                : 'Save Changes'
                        }}
                    </button>

                </div>
            </form>
        </section>


        <!-- Preferences -->
        <section
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
        >
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-900">
                    Preferences
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Configure your regional and display preferences.
                </p>
            </div>

            <form
                @submit.prevent="updatePreferences"
                class="space-y-6 p-6"
            >

                <div class="grid gap-6 md:grid-cols-3">

                    <!-- Timezone -->
                    <div>
                        <label
                            for="timezone"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Timezone
                        </label>

                        <select
                            id="timezone"
                            v-model="preferencesForm.timezone"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option
                                v-for="timezone in timezones"
                                :key="timezone"
                                :value="timezone"
                            >
                                {{ timezone }}
                            </option>
                        </select>

                        <p
                            v-if="preferencesForm.errors.timezone"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ preferencesForm.errors.timezone }}
                        </p>
                    </div>


                    <!-- Date Format -->
                    <div>
                        <label
                            for="date_format"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Date Format
                        </label>

                        <select
                            id="date_format"
                            v-model="preferencesForm.date_format"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="d/m/Y">
                                31/12/2026
                            </option>

                            <option value="m/d/Y">
                                12/31/2026
                            </option>

                            <option value="Y-m-d">
                                2026-12-31
                            </option>

                            <option value="d-m-Y">
                                31-12-2026
                            </option>

                            <option value="d.m.Y">
                                31.12.2026
                            </option>
                        </select>

                        <p
                            v-if="preferencesForm.errors.date_format"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ preferencesForm.errors.date_format }}
                        </p>
                    </div>


                    <!-- Language -->
                    <div>
                        <label
                            for="language"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Language
                        </label>

                        <select
                            id="language"
                            v-model="preferencesForm.language"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="en">
                                English
                            </option>

                            <option value="bem">
                                Bemba
                            </option>
                        </select>

                        <p
                            v-if="preferencesForm.errors.language"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ preferencesForm.errors.language }}
                        </p>
                    </div>

                </div>


                <div class="flex justify-end border-t border-gray-100 pt-5">

                    <button
                        type="submit"
                        :disabled="preferencesForm.processing"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            preferencesForm.processing
                                ? 'Saving...'
                                : 'Save Preferences'
                        }}
                    </button>

                </div>

            </form>
        </section>


        <!-- Notifications -->
        <section
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
        >
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-900">
                    Notifications
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Choose which notifications you want to receive.
                </p>
            </div>

            <form
                @submit.prevent="updateNotifications"
                class="space-y-5 p-6"
            >

                <!-- Email -->
                <label
                    class="flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 p-4 transition hover:bg-gray-50"
                >
                    <div>
                        <div class="font-medium text-gray-900">
                            Email Notifications
                        </div>

                        <div class="mt-1 text-sm text-gray-500">
                            Receive important PAYROW notifications by email.
                        </div>
                    </div>

                    <input
                        v-model="notificationsForm.email_notifications"
                        type="checkbox"
                        class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                </label>


                <!-- System -->
                <label
                    class="flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 p-4 transition hover:bg-gray-50"
                >
                    <div>
                        <div class="font-medium text-gray-900">
                            System Notifications
                        </div>

                        <div class="mt-1 text-sm text-gray-500">
                            Receive notifications inside PAYROW.
                        </div>
                    </div>

                    <input
                        v-model="notificationsForm.system_notifications"
                        type="checkbox"
                        class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                </label>


                <div class="flex justify-end border-t border-gray-100 pt-5">

                    <button
                        type="submit"
                        :disabled="notificationsForm.processing"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            notificationsForm.processing
                                ? 'Saving...'
                                : 'Save Notifications'
                        }}
                    </button>

                </div>

            </form>
        </section>


        <!-- Security -->
        <section
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
        >
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-900">
                    Security
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Change your PAYROW account password.
                </p>
            </div>

            <form
                @submit.prevent="updatePassword"
                class="space-y-6 p-6"
            >

                <div class="grid gap-6 md:grid-cols-3">

                    <!-- Current Password -->
                    <div>
                        <label
                            for="current_password"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Current Password
                        </label>

                        <input
                            id="current_password"
                            v-model="passwordForm.current_password"
                            type="password"
                            autocomplete="current-password"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <p
                            v-if="passwordForm.errors.current_password"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>


                    <!-- New Password -->
                    <div>
                        <label
                            for="password"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            New Password
                        </label>

                        <input
                            id="password"
                            v-model="passwordForm.password"
                            type="password"
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <p
                            v-if="passwordForm.errors.password"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ passwordForm.errors.password }}
                        </p>
                    </div>


                    <!-- Confirm -->
                    <div>
                        <label
                            for="password_confirmation"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Confirm Password
                        </label>

                        <input
                            id="password_confirmation"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <p
                            v-if="passwordForm.errors.password_confirmation"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ passwordForm.errors.password_confirmation }}
                        </p>
                    </div>

                </div>


                <div class="flex justify-end border-t border-gray-100 pt-5">

                    <button
                        type="submit"
                        :disabled="passwordForm.processing"
                        class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            passwordForm.processing
                                ? 'Updating...'
                                : 'Change Password'
                        }}
                    </button>

                </div>

            </form>
        </section>

    </div>
</template>