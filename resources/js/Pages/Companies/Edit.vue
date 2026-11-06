
<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

defineOptions({
    layout: AppLayout,
})

interface Company {
    id: number
    name: string
    legal_name: string | null
    registration_number: string | null
    tax_number: string | null
    email: string | null
    phone: string | null
    address: string | null
    city: string | null
    country: string
    currency: string
    logo: string | null
    organisation_type: string | null
    annual_turnover: string | number | null
    is_sdl_exempt: boolean
    sdl_exemption_reason: string | null
    sdl_exemption_reference: string | null
}

const props = defineProps<{
    company: Company
}>()

const form = useForm({
    name: props.company.name ?? '',
    legal_name: props.company.legal_name ?? '',
    registration_number: props.company.registration_number ?? '',
    tax_number: props.company.tax_number ?? '',
    email: props.company.email ?? '',
    phone: props.company.phone ?? '',
    address: props.company.address ?? '',
    city: props.company.city ?? '',
    country: props.company.country ?? 'Zambia',
    currency: props.company.currency ?? 'ZMW',

    organisation_type: props.company.organisation_type ?? '',
    annual_turnover: props.company.annual_turnover === null
        ? ''
        : String(props.company.annual_turnover),

    is_sdl_exempt: Boolean(props.company.is_sdl_exempt),
    sdl_exemption_reason: props.company.sdl_exemption_reason ?? '',
    sdl_exemption_reference: props.company.sdl_exemption_reference ?? '',

    logo: null as File | null,
})

const logoPreview = ref<string | null>(null)

const existingLogo = computed(() => {
    if (!props.company.logo) return null

    return `/storage/${props.company.logo}`
})

const displayedLogo = computed(() => {
    return logoPreview.value || existingLogo.value
})

const handleLogoUpload = (event: Event) => {
    const target = event.target as HTMLInputElement

    if (!target.files || !target.files[0]) return

    const file = target.files[0]

    form.logo = file
    logoPreview.value = URL.createObjectURL(file)
}

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(`/companies/${props.company.id}`, {
        forceFormData: true,
    })
}
</script>

<template>
    <Head :title="`Edit ${company.name}`" />

    <div class="mx-auto max-w-5xl space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Edit Company
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Update company information and statutory settings.
                </p>
            </div>

            <Link
                href="/companies"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Back to Companies
            </Link>
        </div>

        <form
            @submit.prevent="submit"
            class="space-y-6"
        >

            <!-- Company Information -->
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Company Information
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Basic company registration and contact information.
                    </p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">

                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">
                            Company Name *
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />

                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Legal Name
                        </label>

                        <input
                            v-model="form.legal_name"
                            type="text"
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Registration Number
                        </label>

                        <input
                            v-model="form.registration_number"
                            type="text"
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Tax Number
                        </label>

                        <input
                            v-model="form.tax_number"
                            type="text"
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Email
                        </label>

                        <input
                            v-model="form.email"
                            type="email"
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />

                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Phone
                        </label>

                        <input
                            v-model="form.phone"
                            type="text"
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            City
                        </label>

                        <input
                            v-model="form.city"
                            type="text"
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Country *
                        </label>

                        <input
                            v-model="form.country"
                            type="text"
                            required
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Currency *
                        </label>

                        <select
                            v-model="form.currency"
                            class="mt-2 w-full rounded-lg border-gray-300"
                        >
                            <option value="ZMW">ZMW - Zambian Kwacha</option>
                            <option value="USD">USD - US Dollar</option>
                            <option value="GBP">GBP - Pound</option>
                            <option value="EUR">EUR - Euro</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">
                            Address
                        </label>

                        <textarea
                            v-model="form.address"
                            rows="3"
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />
                    </div>

                </div>
            </section>

            <!-- Company Logo -->
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Company Logo
                </h2>

                <div class="mt-4 flex flex-col gap-5 sm:flex-row sm:items-center">
                    <div class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
                        <img
                            v-if="displayedLogo"
                            :src="displayedLogo"
                            alt="Company logo"
                            class="h-full w-full object-contain"
                        />

                        <span v-else class="text-xs text-gray-400">
                            No logo
                        </span>
                    </div>

                    <div>
                        <input
                            type="file"
                            accept="image/png,image/jpeg,image/jpg,image/webp"
                            class="block w-full text-sm text-gray-500"
                            @change="handleLogoUpload"
                        />

                        <p class="mt-2 text-xs text-gray-500">
                            Upload PNG, JPG or WEBP. Maximum 2MB.
                            Leave empty to keep the current logo.
                        </p>

                        <p v-if="form.errors.logo" class="mt-1 text-sm text-red-600">
                            {{ form.errors.logo }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- SDL Eligibility -->
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">
                        SDL Eligibility Information
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Maintain the organisation and turnover information
                        used when assessing Skills Development Levy eligibility.
                    </p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Organisation Type
                        </label>

                        <input
                            v-model="form.organisation_type"
                            type="text"
                            placeholder="e.g. private_company"
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />

                        <p v-if="form.errors.organisation_type" class="mt-1 text-sm text-red-600">
                            {{ form.errors.organisation_type }}
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Use the organisation category configured in your statutory rules.
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">
                            Annual Turnover (ZMW)
                        </label>

                        <input
                            v-model="form.annual_turnover"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="Enter annual turnover"
                            class="mt-2 w-full rounded-lg border-gray-300"
                        />

                        <p v-if="form.errors.annual_turnover" class="mt-1 text-sm text-red-600">
                            {{ form.errors.annual_turnover }}
                        </p>
                    </div>

                </div>

                <div class="mt-6 rounded-lg border border-gray-200 p-5">

                    <div class="flex items-start gap-3">
                        <input
                            id="is_sdl_exempt"
                            v-model="form.is_sdl_exempt"
                            type="checkbox"
                            class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600"
                        />

                        <div>
                            <label
                                for="is_sdl_exempt"
                                class="font-medium text-gray-900"
                            >
                                Mark company as SDL exempt
                            </label>

                            <p class="mt-1 text-sm text-gray-500">
                                Enable only when the company's exemption
                                has been verified and documented.
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="form.is_sdl_exempt"
                        class="mt-5 grid gap-5 border-t border-gray-100 pt-5 md:grid-cols-2"
                    >

                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-gray-700">
                                Exemption Reason *
                            </label>

                            <textarea
                                v-model="form.sdl_exemption_reason"
                                rows="3"
                                placeholder="Explain the reason for the SDL exemption"
                                class="mt-2 w-full rounded-lg border-gray-300"
                            />

                            <p v-if="form.errors.sdl_exemption_reason" class="mt-1 text-sm text-red-600">
                                {{ form.errors.sdl_exemption_reason }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-gray-700">
                                Exemption Reference
                            </label>

                            <input
                                v-model="form.sdl_exemption_reference"
                                type="text"
                                placeholder="Enter approval or exemption reference"
                                class="mt-2 w-full rounded-lg border-gray-300"
                            />

                            <p v-if="form.errors.sdl_exemption_reference" class="mt-1 text-sm text-red-600">
                                {{ form.errors.sdl_exemption_reference }}
                            </p>
                        </div>

                    </div>

                </div>

            </section>

            <!-- Actions -->
            <div class="flex flex-col-reverse justify-end gap-3 border-t border-gray-200 pt-5 sm:flex-row">

                <Link
                    href="/companies"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ form.processing ? 'Saving Changes...' : 'Save Changes' }}
                </button>

            </div>

        </form>
    </div>
</template>
