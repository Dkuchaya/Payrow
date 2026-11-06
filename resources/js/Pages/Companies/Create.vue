<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

defineOptions({
    layout: AppLayout,
})

const form = useForm({
    name: '',
    legal_name: '',
    registration_number: '',
    tax_number: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    country: 'Zambia',
    currency: 'ZMW',
    organisation_type: '',
    annual_turnover: '',
    is_sdl_exempt: false,
    sdl_exemption_reason: '',
    sdl_exemption_reference: '',
    logo: null as File | null,
})

const logoPreview = ref<string | null>(null)

const handleLogoUpload = (event: Event) => {

    const target = event.target as HTMLInputElement

    if (!target.files || !target.files[0]) {
        return
    }

    const file = target.files[0]

    form.logo = file

    logoPreview.value = URL.createObjectURL(file)
}

const submit = () => {
    form.post('/companies')
}
</script>

<template>

    <Head title="Create Company" />

    <div class="mx-auto max-w-4xl space-y-6">

        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                Create Company
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Add a new company to PAYROW.
            </p>
        </div>

        <form
            @submit.prevent="submit"
            class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
        >

            <div class="grid gap-6 md:grid-cols-2">

                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">
                        Company Name
                    </label>

                    <input
                        v-model="form.name"
                        type="text"
                        class="mt-2 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="ABC Limited"
                    />

                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">
                        Legal Name
                    </label>

                    <input
                        v-model="form.legal_name"
                        type="text"
                        class="mt-2 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
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
                        Country
                    </label>

                    <input
                        v-model="form.country"
                        type="text"
                        class="mt-2 w-full rounded-lg border-gray-300"
                    />
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">
                        Currency
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

                <div >

                        <label class="text-sm font-medium text-gray-700">
                            Company Logo
                        </label>

                        <div class="mt-2 flex items-center gap-5">

                            <div
                                class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-gray-300 bg-gray-50"
                            >
                                <img
                                    v-if="logoPreview"
                                    :src="logoPreview"
                                    alt="Company logo preview"
                                    class="h-full w-full object-contain"
                                />

                                <span
                                    v-else
                                    class="text-xs text-gray-400"
                                >
                                    No logo
                                </span>
                            </div>

                            <div>
                                <input
                                    id="logo"
                                    type="file"
                                    accept="image/png,image/jpeg,image/jpg,image/webp"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                                    @change="handleLogoUpload"
                                />

                                <p class="mt-2 text-xs text-gray-500">
                                    PNG, JPG or WEBP. Maximum 2MB.
                                </p>

                                <p
                                    v-if="form.errors.logo"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.logo }}
                                </p>
                            </div>

                        </div>

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


                
            <!-- SDL Eligibility Information -->
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">
                        SDL Eligibility Information
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Capture the organisation's statutory eligibility information.
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
                                Enable only when the exemption has been verified.
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="form.is_sdl_exempt"
                        class="mt-5 space-y-5 border-t border-gray-100 pt-5"
                    >
                        <div>
                            <label class="text-sm font-medium text-gray-700">
                                Exemption Reason
                            </label>

                            <textarea
                                v-model="form.sdl_exemption_reason"
                                rows="3"
                                placeholder="Explain the exemption reason"
                                class="mt-2 w-full rounded-lg border-gray-300"
                            />
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700">
                                Exemption Reference
                            </label>

                            <input
                                v-model="form.sdl_exemption_reference"
                                type="text"
                                placeholder="Approval or exemption reference"
                                class="mt-2 w-full rounded-lg border-gray-300"
                            />
                        </div>
                    </div>

                </div>

            </section>


            </div>

            <div class="flex justify-end gap-3 border-t pt-6">

                <Link
                    href="/companies"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ form.processing ? 'Creating...' : 'Create Company' }}
                </button>

            </div>

        </form>

    </div>

</template>