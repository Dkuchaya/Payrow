<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})


defineProps<{
    company: {
        id: number
        name: string
    }
}>()

const items = [
    {
        title: 'Leave Requests',
        description: 'Submit, review and manage employee leave requests.',
        href: '/leave/requests',
        icon: '📋',
    },
    {
        title: 'Leave Balances',
        description: 'View and adjust employee leave balances.',
        href: '/leave/balances',
        icon: '📊',
    },
    {
        title: 'Leave Policies',
        description: 'Configure leave entitlement and approval rules.',
        href: '/leave/policies',
        icon: '⚙️',
    },
    {
        title: 'Leave Types',
        description: 'Manage annual, sick, maternity and other leave types.',
        href: '/leave/types',
        icon: '📅',
    },
]
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Leave Management
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Manage employee leave, balances, policies and approvals.
            </p>
        </div>

        <!-- Cards -->
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">

            <Link
                v-for="item in items"
                :key="item.title"
                :href="item.href"
                class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
            >
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-2xl"
                >
                    {{ item.icon }}
                </div>

                <h2
                    class="mt-5 font-semibold text-slate-900 group-hover:text-blue-600"
                >
                    {{ item.title }}
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    {{ item.description }}
                </p>

                <div class="mt-5 text-sm font-medium text-blue-600">
                    Open →
                </div>
            </Link>

        </div>

        <!-- Quick information -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-slate-900">
                Leave Management
            </h2>

            <p class="mt-2 text-sm leading-6 text-slate-500">
                Use this module to manage employee leave requests,
                balances, leave policies and leave types for
                {{ company.name }}.
            </p>
        </div>

    </div>
</template>