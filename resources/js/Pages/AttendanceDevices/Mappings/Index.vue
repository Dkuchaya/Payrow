<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

interface Company {
    id: number;
    name: string;
}

interface AttendanceDevice {
    id: number;
    name: string;
    manufacturer: string | null;
    model: string | null;
    serial_number: string | null;
}

interface Employee {
    id: number;
    employee_number: string;
    first_name: string;
    last_name: string;
}

interface EmployeeMapping {
    id: number;
    device_user_id: string;
    device_username: string | null;
    is_active: boolean;
    notes: string | null;
    employee: Employee;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedMappings {
    data: EmployeeMapping[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    company: Company;
    device: AttendanceDevice;
    mappings: PaginatedMappings;
    filters: {
        search?: string | null;
        status?: string | null;
    };
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const processingMapping = ref<number | null>(null);

const activeCount = computed(() =>
    props.mappings.data.filter((mapping) => mapping.is_active).length
);

const inactiveCount = computed(() =>
    props.mappings.data.filter((mapping) => !mapping.is_active).length
);

const employeeName = (employee: Employee) =>
    `${employee.first_name} ${employee.last_name}`.trim();

const applyFilters = () => {
    router.get(
        route('attendance-devices.mappings.index', props.device.id),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

const clearFilters = () => {
    search.value = '';
    status.value = '';

    router.get(
        route('attendance-devices.mappings.index', props.device.id),
        {},
        {
            preserveState: true,
            replace: true,
        }
    );
};

const goToPage = (url: string | null) => {
    if (!url) {
        return;
    }

    router.get(
        url,
        {},
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const toggleMapping = (mapping: EmployeeMapping) => {
    processingMapping.value = mapping.id;

    router.patch(
        route('attendance-devices.mappings.toggle', [
            props.device.id,
            mapping.id,
        ]),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processingMapping.value = null;
            },
        }
    );
};
</script>

<template>
    <Head :title="`Employee Mappings — ${device.name}`" />

    <div class="space-y-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <Link
                    :href="route('attendance-devices.show', device.id)"
                    class="text-sm text-slate-500 transition hover:text-slate-700"
                >
                    ← {{ device.name }}
                </Link>

                <h1 class="mt-2 text-2xl font-semibold text-slate-900">
                    Employee Mappings
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Connect PAYROW employees with users registered on this attendance device.
                </p>
            </div>

            <Link
                :href="route('attendance-devices.mappings.create', device.id)"
                class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800"
            >
                + Map Employee
            </Link>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Attendance device
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        {{ device.name }}
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ device.manufacturer || 'Unknown manufacturer' }}
                        <span v-if="device.model">· {{ device.model }}</span>
                        <span v-if="device.serial_number">· {{ device.serial_number }}</span>
                    </p>
                </div>

                <Link
                    :href="route('attendance-devices.show', device.id)"
                    class="text-sm font-medium text-slate-700 hover:text-slate-900"
                >
                    View device
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Mappings on this page</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">
                    {{ mappings.data.length }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Active</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-600">
                    {{ activeCount }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Inactive</p>
                <p class="mt-2 text-2xl font-semibold text-slate-500">
                    {{ inactiveCount }}
                </p>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label
                        for="search"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Search
                    </label>

                    <input
                        id="search"
                        v-model="search"
                        type="search"
                        placeholder="Employee name, employee number, device user ID..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div>
                    <label
                        for="status"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Status
                    </label>

                    <select
                        id="status"
                        v-model="status"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    >
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <button
                    type="button"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                    @click="applyFilters"
                >
                    Search
                </button>

                <button
                    type="button"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    @click="clearFilters"
                >
                    Clear
                </button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Employee
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Device User ID
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Device Username
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Status
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        v-if="mappings.data.length"
                        class="divide-y divide-slate-100"
                    >
                        <tr
                            v-for="mapping in mappings.data"
                            :key="mapping.id"
                            class="hover:bg-slate-50"
                        >
                            <td class="px-4 py-4">
                                <p class="font-medium text-slate-900">
                                    {{ employeeName(mapping.employee) }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ mapping.employee.employee_number }}
                                </p>
                            </td>
                            <td class="px-4 py-4 text-sm font-medium text-slate-800">
                                {{ mapping.device_user_id }}
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-700">
                                {{ mapping.device_username || '—' }}
                            </td>
                            <td class="px-4 py-4">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="mapping.is_active
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-slate-100 text-slate-600'"
                                >
                                    {{ mapping.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <Link
                                        :href="route('attendance-devices.mappings.edit', [device.id, mapping.id])"
                                        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                    >
                                        Edit
                                    </Link>

                                    <button
                                        type="button"
                                        :disabled="processingMapping === mapping.id"
                                        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        @click="toggleMapping(mapping)"
                                    >
                                        {{
                                            processingMapping === mapping.id
                                                ? 'Saving...'
                                                : mapping.is_active
                                                ? 'Deactivate'
                                                : 'Activate'
                                        }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="!mappings.data.length"
                class="px-6 py-14 text-center"
            >
                <p class="text-sm font-medium text-slate-700">
                    No employee mappings found
                </p>
                <p class="mt-1 text-sm text-slate-500">
                    Add an employee to connect their PAYROW profile to this device.
                </p>
                <Link
                    :href="route('attendance-devices.mappings.create', device.id)"
                    class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                >
                    Map Employee
                </Link>
            </div>

            <div
                v-if="mappings.last_page > 1"
                class="flex flex-col gap-3 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-sm text-slate-500">
                    {{ mappings.total }} total mapping{{ mappings.total === 1 ? '' : 's' }}
                </p>

                <div class="flex flex-wrap gap-1">
                    <button
                        v-for="(link, index) in mappings.links"
                        :key="`${link.label}-${index}`"
                        type="button"
                        :disabled="!link.url"
                        class="min-w-9 rounded-md border px-3 py-1.5 text-sm transition disabled:cursor-not-allowed disabled:opacity-40"
                        :class="link.active
                            ? 'border-slate-900 bg-slate-900 text-white'
                            : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'"
                        @click="goToPage(link.url)"
                    >
                        <span v-html="link.label" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
