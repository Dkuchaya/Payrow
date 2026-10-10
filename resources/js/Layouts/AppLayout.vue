<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'

import CompanyLogo from '@/Components/CompanyLogo.vue'
import CompanySwitcher from '@/Components/CompanySwitcher.vue'
import FlashMessage from '@/Components/FlashMessage.vue'

const page = usePage()

const sidebarOpen = ref(false)
const profileOpen = ref(false)

const activeCompany = computed(() => {
    return page.props.company?.active ?? null
})

const user = computed(() => {
    return page.props.auth?.user ?? null
})

const userInitial = computed(() => {
    return user.value?.name?.charAt(0)?.toUpperCase() ?? 'U'
})

const logout = () => {
    router.post('/logout')
}

const closeProfileMenu = (event: MouseEvent) => {
    const target = event.target as HTMLElement

    if (!target.closest('#profile-menu')) {
        profileOpen.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', closeProfileMenu)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', closeProfileMenu)
})

const navigation = [
    {
        name: 'Dashboard',
        href: '/dashboard',
        icon: '⌂',
    },
    {
        name: 'Employees',
        href: '/employees',
        icon: '👥',
    },
    {
        name: 'Leave',
        href: '/leave',
        icon: '📅',
    },
    {
        name: 'Attendance',
        href: '/attendance',
        icon: '🕐',
    },
    {
        name: 'Payroll',
        href: '/payroll',
        icon: '💰',
    },
    {
        name: 'Documents',
        href: '/documents',
        icon: '📄',
    },
    {
        name: 'Reports',
        href: '/reports',
        icon: '📊',
    },
]
</script>

<template>
    <div class="min-h-screen bg-gray-50">

        <FlashMessage />

        <!-- Mobile overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-black/40 lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-slate-950 text-white transition-transform duration-200 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >

            <!-- Company Branding -->
            <div class="border-b border-white/10 px-4 py-4">

                <div class="flex items-center gap-3">

                    <CompanyLogo
                        v-if="activeCompany"
                        :logo="activeCompany.logo"
                        :name="activeCompany.name"
                        size="md"
                    />

                    <div class="min-w-0">

                        <div class="truncate text-sm font-bold text-white">
                            {{ activeCompany?.name ?? 'PAYROW' }}
                        </div>

                        <div class="text-xs text-slate-400">
                            HR & Payroll
                        </div>

                    </div>

                </div>

            </div>


            <!-- Navigation -->
            <nav class="flex-1 space-y-1 overflow-y-auto p-4">

                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white"
                >
                    <span class="w-6 text-center">
                        {{ item.icon }}
                    </span>

                    {{ item.name }}
                </Link>

            </nav>


            <!-- Settings -->
            <div class="border-t border-white/10 p-4">

                <Link
                    href="/settings"
                    class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm text-slate-300 hover:bg-white/10 hover:text-white"
                >
                    ⚙️
                    Settings
                </Link>

            </div>

        </aside>


        <!-- Main -->
        <div class="lg:pl-64">

            <!-- Topbar -->
            <header
                class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 shadow-sm sm:px-6"
            >

                <!-- Left -->
                <div class="flex items-center gap-4">

                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-600 hover:bg-gray-100 lg:hidden"
                        @click="sidebarOpen = true"
                    >
                        ☰
                    </button>

                    <!-- Company Switcher -->
                    <div class="w-72 max-w-[50vw]">
                        <CompanySwitcher />
                    </div>

                </div>


                <!-- User -->
<!-- User -->
<div class="flex items-center gap-4">

    <!-- Notifications -->
    <button
        type="button"
        class="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100"
    >
        🔔

        <span
            class="absolute right-1 top-1 h-2 w-2 rounded-full bg-red-500"
        />
    </button>


    <!-- Profile -->
    <div id="profile-menu" class="relative">

        <!-- Profile Button -->
        <button
            type="button"
            @click.stop="profileOpen = !profileOpen"
            class="flex items-center gap-3 rounded-lg px-2 py-1.5 transition hover:bg-gray-100"
        >

            <!-- Name -->
            <div class="hidden text-right sm:block">

                <div class="text-sm font-semibold text-gray-900">
                    {{ user?.name }}
                </div>

                <div class="text-xs text-gray-500">
                    {{ user?.is_super_admin ? 'Super Administrator' : 'Administrator' }}
                </div>

            </div>


            <!-- Avatar -->
            <div
                class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white"
            >
                {{ userInitial }}
            </div>


            <!-- Arrow -->
            <svg
                class="hidden h-4 w-4 text-gray-400 sm:block"
                :class="{ 'rotate-180': profileOpen }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                />
            </svg>

        </button>


        <!-- Dropdown -->
        <div
            v-if="profileOpen"
            class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl"
        >

            <!-- User information -->
            <div class="border-b border-gray-100 px-4 py-4">

                <div class="flex items-center gap-3">

                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-blue-600 text-base font-bold text-white"
                    >
                        {{ userInitial }}
                    </div>

                    <div class="min-w-0">

                        <div class="truncate text-sm font-semibold text-gray-900">
                            {{ user?.name }}
                        </div>

                        <div class="truncate text-xs text-gray-500">
                            {{ user?.email }}
                        </div>

                    </div>

                </div>

            </div>


            <!-- Menu -->
            <div class="py-2">

                <!-- Profile -->
                <Link
                    href="/profile"
                    @click="profileOpen = false"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition hover:bg-gray-50"
                >
                    <span class="w-5 text-center">👤</span>

                    <span>My Profile</span>
                </Link>


                <!-- Settings -->
                <Link
                    href="/settings"
                    @click="profileOpen = false"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition hover:bg-gray-50"
                >
                    <span class="w-5 text-center">⚙️</span>

                    <span>Settings</span>
                </Link>

            </div>


            <!-- Logout -->
            <div class="border-t border-gray-100 py-2">

                <button
                    type="button"
                    @click="logout"
                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50"
                >
                    <span class="w-5 text-center">🚪</span>

                    <span>Logout</span>
                </button>

            </div>

        </div>

    </div>

</div>

            </header>


            <!-- Page content -->
            <main class="p-4 sm:p-6 lg:p-8">
                <slot />
            </main>

        </div>

    </div>
</template>