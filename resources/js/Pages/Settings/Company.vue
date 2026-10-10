<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Company {
    id: number
    name: string
    legal_name: string | null
    slug: string
    registration_number: string | null
    tax_number: string | null
    email: string | null
    phone: string | null
    address: string | null
    city: string | null
    country: string
    currency: string
    logo: string | null
    is_active: boolean
}

const props = defineProps<{
    company: Company
}>()

/*
|--------------------------------------------------------------------------
| Company Information
|--------------------------------------------------------------------------
*/

const companyForm = useForm({
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
})

const updateCompany = () => {
    companyForm.put('/settings/company', {
        preserveScroll: true,
    })
}

/*
|--------------------------------------------------------------------------
| Logo
|--------------------------------------------------------------------------
*/

const logoInput = ref<HTMLInputElement | null>(null)

const logoPreview = ref<string | null>(
    props.company.logo ?? null
)

const logoForm = useForm<{
    logo: File | null
}>({
    logo: null,
})

const selectLogo = () => {
    logoInput.value?.click()
}

const handleLogoChange = (event: Event) => {
    const target = event.target as HTMLInputElement

    if (!target.files || !target.files.length) {
        return
    }

    const file = target.files[0]

    logoForm.logo = file

    logoPreview.value = URL.createObjectURL(file)
}

const uploadLogo = () => {
    if (!logoForm.logo) {
        return
    }

    logoForm.post('/settings/company/logo', {
        forceFormData: true,
        preserveScroll: true,

        onSuccess: () => {
            logoForm.reset()
        },
    })
}

const removeLogo = () => {
    if (!props.company.logo && !logoPreview.value) {
        return
    }

    if (logoForm.logo) {
        logoForm.reset()
    }

    logoForm.delete('/settings/company/logo', {
        preserveScroll: true,

        onSuccess: () => {
            logoPreview.value = null
        },
    })
}
</script>

<template>
    <div class="mx-auto max-w-6xl space-y-8">

        <!-- Header -->
        <div>
            <div class="flex items-center gap-3">
                <a
                    href="/settings"
                    class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700"
                >
                    ←
                </a>

                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Company Settings
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Manage your company's information, branding and
                        financial settings.
                    </p>
                </div>
            </div>
        </div>


        <!-- Company Information -->
        <section
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
        >
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-900">
                    Company Information
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Basic information about your company.
                </p>
            </div>

            <form
                @submit.prevent="updateCompany"
                class="space-y-6 p-6"
            >

                <div class="grid gap-6 md:grid-cols-2">

                    <!-- Company Name -->
                    <div>
                        <label
                            for="name"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Company Name
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="name"
                            v-model="companyForm.name"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <p
                            v-if="companyForm.errors.name"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ companyForm.errors.name }}
                        </p>
                    </div>


                    <!-- Legal Name -->
                    <div>
                        <label
                            for="legal_name"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Legal Name
                        </label>

                        <input
                            id="legal_name"
                            v-model="companyForm.legal_name"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <p
                            v-if="companyForm.errors.legal_name"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ companyForm.errors.legal_name }}
                        </p>
                    </div>


                    <!-- Registration -->
                    <div>
                        <label
                            for="registration_number"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Registration Number
                        </label>

                        <input
                            id="registration_number"
                            v-model="companyForm.registration_number"
                            type="text"
                            placeholder="e.g. 12345/2024"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <p
                            v-if="companyForm.errors.registration_number"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ companyForm.errors.registration_number }}
                        </p>
                    </div>


                    <!-- Tax -->
                    <div>
                        <label
                            for="tax_number"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Tax Number
                        </label>

                        <input
                            id="tax_number"
                            v-model="companyForm.tax_number"
                            type="text"
                            placeholder="TPIN"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <p
                            v-if="companyForm.errors.tax_number"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ companyForm.errors.tax_number }}
                        </p>
                    </div>

                </div>


                <div class="flex justify-end border-t border-gray-100 pt-5">

                    <button
                        type="submit"
                        :disabled="companyForm.processing"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            companyForm.processing
                                ? 'Saving...'
                                : 'Save Company Information'
                        }}
                    </button>

                </div>
            </form>
        </section>


        <!-- Contact -->
        <section
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
        >
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-900">
                    Contact & Address
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Company contact and location information.
                </p>
            </div>

            <form
                @submit.prevent="updateCompany"
                class="space-y-6 p-6"
            >

                <div class="grid gap-6 md:grid-cols-2">

                    <!-- Email -->
                    <div>
                        <label
                            for="email"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Company Email
                        </label>

                        <input
                            id="email"
                            v-model="companyForm.email"
                            type="email"
                            placeholder="company@example.com"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <p
                            v-if="companyForm.errors.email"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ companyForm.errors.email }}
                        </p>
                    </div>


                    <!-- Phone -->
                    <div>
                        <label
                            for="phone"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Company Phone
                        </label>

                        <input
                            id="phone"
                            v-model="companyForm.phone"
                            type="text"
                            placeholder="+260..."
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <p
                            v-if="companyForm.errors.phone"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ companyForm.errors.phone }}
                        </p>
                    </div>


                    <!-- City -->
                    <div>
                        <label
                            for="city"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            City
                        </label>

                        <input
                            id="city"
                            v-model="companyForm.city"
                            type="text"
                            placeholder="Lusaka"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <p
                            v-if="companyForm.errors.city"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ companyForm.errors.city }}
                        </p>
                    </div>


                    <!-- Country -->
                    <div>
                        <label
                            for="country"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Country
                        </label>

                        <input
                            id="country"
                            v-model="companyForm.country"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        <p
                            v-if="companyForm.errors.country"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ companyForm.errors.country }}
                        </p>
                    </div>

                </div>


                <!-- Address -->
                <div>
                    <label
                        for="address"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Address
                    </label>

                    <textarea
                        id="address"
                        v-model="companyForm.address"
                        rows="3"
                        placeholder="Company physical address"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    ></textarea>

                    <p
                        v-if="companyForm.errors.address"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ companyForm.errors.address }}
                    </p>
                </div>


                <div class="flex justify-end border-t border-gray-100 pt-5">

                    <button
                        type="submit"
                        :disabled="companyForm.processing"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            companyForm.processing
                                ? 'Saving...'
                                : 'Save Contact Information'
                        }}
                    </button>

                </div>
            </form>
        </section>


        <!-- Financial -->
        <section
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
        >
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-900">
                    Financial Settings
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Configure the currency used by this company.
                </p>
            </div>

            <form
                @submit.prevent="updateCompany"
                class="space-y-6 p-6"
            >

                <div class="max-w-sm">
                    <label
                        for="currency"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Currency
                    </label>

                    <select
                        id="currency"
                        v-model="companyForm.currency"
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >
                        <option value="ZMW">
                            ZMW — Zambian Kwacha
                        </option>

                        <option value="USD">
                            USD — US Dollar
                        </option>

                        <option value="GBP">
                            GBP — British Pound
                        </option>

                        <option value="EUR">
                            EUR — Euro
                        </option>

                        <option value="ZAR">
                            ZAR — South African Rand
                        </option>

                        <option value="KES">
                            KES — Kenyan Shilling
                        </option>

                        <option value="TZS">
                            TZS — Tanzanian Shilling
                        </option>
                    </select>

                    <p
                        v-if="companyForm.errors.currency"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ companyForm.errors.currency }}
                    </p>
                </div>


                <div class="flex justify-end border-t border-gray-100 pt-5">

                    <button
                        type="submit"
                        :disabled="companyForm.processing"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            companyForm.processing
                                ? 'Saving...'
                                : 'Save Financial Settings'
                        }}
                    </button>

                </div>
            </form>
        </section>


        <!-- Branding -->
        <section
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
        >
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-lg font-semibold text-gray-900">
                    Company Branding
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Upload your company logo. It will be used throughout
                    PAYROW.
                </p>
            </div>

            <div class="space-y-6 p-6">

                <div class="flex flex-col gap-6 sm:flex-row sm:items-center">

                    <!-- Logo -->
                    <div
                        class="flex h-32 w-32 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-gray-50"
                    >
                        <img
                            v-if="logoPreview"
                            :src="logoPreview"
                            :alt="company.name"
                            class="h-full w-full object-contain p-3"
                        />

                        <div
                            v-else
                            class="text-center text-sm font-semibold text-gray-400"
                        >
                            No Logo
                        </div>
                    </div>


                    <!-- Upload controls -->
                    <div class="space-y-3">

                        <input
                            ref="logoInput"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp"
                            class="hidden"
                            @change="handleLogoChange"
                        />

                        <div class="flex flex-wrap gap-3">

                            <button
                                type="button"
                                @click="selectLogo"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                            >
                                Choose Logo
                            </button>

                            <button
                                v-if="logoForm.logo"
                                type="button"
                                :disabled="logoForm.processing"
                                @click="uploadLogo"
                                class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
                            >
                                {{
                                    logoForm.processing
                                        ? 'Uploading...'
                                        : 'Upload Logo'
                                }}
                            </button>

                            <button
                                v-if="company.logo"
                                type="button"
                                :disabled="logoForm.processing"
                                @click="removeLogo"
                                class="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50 disabled:opacity-50"
                            >
                                Remove Logo
                            </button>

                        </div>

                        <p class="text-xs text-gray-500">
                            JPG, PNG or WebP. Maximum file size: 2 MB.
                        </p>

                        <p
                            v-if="logoForm.errors.logo"
                            class="text-sm text-red-600"
                        >
                            {{ logoForm.errors.logo }}
                        </p>

                    </div>

                </div>

            </div>
        </section>

    </div>
</template>