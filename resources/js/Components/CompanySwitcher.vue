<script setup lang="ts">

import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import CompanyLogo from '@/Components/CompanyLogo.vue'

const page = usePage()

const open = ref(false)

const activeCompany = computed(() => {
    return page.props.company.active
})

const companies = computed(() => {
    return page.props.company.available ?? []
})

const switchCompany = (companyId: number) => {

    open.value = false

    router.post(
        `/company/switch/${companyId}`,
        {},
        {
            preserveScroll: true,
        }
    )
}

</script>

<template>

    <div class="relative">

        <!-- Current Company -->

        <button
            type="button"
            class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left transition hover:bg-gray-100"
            @click="open = !open"
        >

            <CompanyLogo
                v-if="activeCompany"
                :logo="activeCompany.logo"
                :name="activeCompany.name"
                size="sm"
            />

            <div class="min-w-0 flex-1">

                <p class="truncate text-sm font-semibold text-gray-900">
                    {{ activeCompany?.name ?? 'Select Company' }}
                </p>

                <p class="text-xs text-gray-500">
                    Active company
                </p>

            </div>

            <svg
                class="h-4 w-4 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="m19 9-7 7-7-7"
                />
            </svg>

        </button>


        <!-- Dropdown -->

        <div
            v-if="open"
            class="absolute left-0 z-50 mt-2 w-72 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl"
        >

            <div class="border-b border-gray-100 px-4 py-3">

                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                    Switch Company
                </p>

            </div>


            <div class="max-h-80 overflow-y-auto p-2">

                <button
                    v-for="company in companies"
                    :key="company.id"
                    type="button"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-3 text-left transition hover:bg-gray-50"
                    :class="{
                        'bg-blue-50': activeCompany?.id === company.id
                    }"
                    @click="switchCompany(company.id)"
                >

                    <CompanyLogo
                        :logo="company.logo"
                        :name="company.name"
                        size="sm"
                    />

                    <div class="min-w-0 flex-1">

                        <p class="truncate text-sm font-medium text-gray-900">
                            {{ company.name }}
                        </p>

                        <p class="text-xs text-gray-500">
                            {{ company.currency }}
                        </p>

                    </div>

                    <svg
                        v-if="activeCompany?.id === company.id"
                        class="h-5 w-5 text-blue-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="m5 13 4 4L19 7"
                        />
                    </svg>

                </button>

                <div
                    v-if="companies.length === 0"
                    class="px-3 py-6 text-center text-sm text-gray-500"
                >
                    No companies assigned.
                </div>

            </div>

        </div>

    </div>

</template>