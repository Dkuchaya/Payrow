<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'

defineOptions({
    layout: AppLayout,
})

const statistics = [
    {
        title: 'Employees',
        value: '245',
        change: '+12 this month',
        icon: '👥',
    },
    {
        title: 'On Leave',
        value: '18',
        change: '7.3% of employees',
        icon: '📅',
    },
    {
        title: 'Payroll',
        value: 'K482,350',
        change: 'October payroll',
        icon: '💰',
    },
    {
        title: 'Pending',
        value: '12',
        change: 'Items requiring action',
        icon: '⚠️',
    },
]
</script>

<template>

    <Head title="Dashboard" />

    

    <div class="space-y-8">

        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                Dashboard
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Here's what's happening with your company today.
            </p>
        </div>

        <!-- Statistics -->
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            <div
                v-for="stat in statistics"
                :key="stat.title"
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >

                <div class="flex items-start justify-between">

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            {{ stat.title }}
                        </p>

                        <p class="mt-2 text-2xl font-bold text-gray-900">
                            {{ stat.value }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-xl"
                    >
                        {{ stat.icon }}
                    </div>

                </div>

                <p class="mt-4 text-xs text-gray-500">
                    {{ stat.change }}
                </p>

            </div>

        </div>

        <!-- Main dashboard -->
        <div class="grid gap-6 xl:grid-cols-3">

            <!-- Payroll -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm xl:col-span-2">

                <div class="flex items-center justify-between">

                    <div>
                        <h2 class="font-semibold text-gray-900">
                            Payroll Overview
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Current payroll activity
                        </p>
                    </div>

                    <button
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        View Payroll
                    </button>

                </div>

                <div class="mt-8 h-64 rounded-lg bg-gray-50 flex items-center justify-center">

                    <div class="text-center">

                        <div class="text-4xl">
                            📊
                        </div>

                        <p class="mt-3 text-sm text-gray-500">
                            Payroll chart will appear here
                        </p>

                    </div>

                </div>

            </div>

            <!-- Quick actions -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                <h2 class="font-semibold text-gray-900">
                    Quick Actions
                </h2>

                <div class="mt-5 space-y-3">

                    <button
                        class="w-full rounded-lg border border-gray-200 p-4 text-left hover:bg-gray-50"
                    >
                        <div class="font-medium text-gray-900">
                            Add Employee
                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            Create a new employee record
                        </div>
                    </button>

                    <button
                        class="w-full rounded-lg border border-gray-200 p-4 text-left hover:bg-gray-50"
                    >
                        <div class="font-medium text-gray-900">
                            Run Payroll
                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            Start a payroll run
                        </div>
                    </button>

                    <button
                        class="w-full rounded-lg border border-gray-200 p-4 text-left hover:bg-gray-50"
                    >
                        <div class="font-medium text-gray-900">
                            Upload Document
                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            Add an employee document
                        </div>
                    </button>

                </div>

            </div>

        </div>

        <!-- Recent activity -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 p-6">

                <h2 class="font-semibold text-gray-900">
                    Recent Activity
                </h2>

            </div>

            <div class="p-6">

                <div class="py-8 text-center text-sm text-gray-500">
                    No recent activity yet.
                </div>

            </div>

        </div>

    </div>

</template>