<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
})


interface Company {
    id: number;
    name: string;
}

interface AttendanceDevice {
    id: number;
    name: string;
    device_type: string;
    manufacturer: string | null;
    model: string | null;
    serial_number: string | null;
    connection_type: string;
    ip_address: string | null;
    port: number | null;
    timezone: string;
    sync_method: string;
    last_synced_at: string | null;
    is_active: boolean;
    punches_count: number;
    active_employee_mappings_count: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedDevices {
    data: AttendanceDevice[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    company: Company;
    devices: PaginatedDevices;
    manufacturers: string[];
    filters: {
        search?: string | null;
        manufacturer?: string | null;
        is_active?: string | boolean | null;
    };
}>();

const search = ref(props.filters.search ?? '');
const manufacturer = ref(props.filters.manufacturer ?? '');
const isActive = ref(
    props.filters.is_active === true ||
    props.filters.is_active === '1' ||
    props.filters.is_active === 'true'
        ? '1'
        : props.filters.is_active === false ||
          props.filters.is_active === '0' ||
          props.filters.is_active === 'false'
        ? '0'
        : ''
);

const processingDevice = ref<number | null>(null);

const applyFilters = () => {
    router.get(
        route('attendance-devices.index'),
        {
            search: search.value || undefined,
            manufacturer: manufacturer.value || undefined,
            is_active: isActive.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

const clearFilters = () => {
    search.value = '';
    manufacturer.value = '';
    isActive.value = '';

    router.get(
        route('attendance-devices.index'),
        {},
        {
            preserveState: true,
            replace: true,
        }
    );
};

const formatDateTime = (value: string | null) => {
    if (!value) {
        return 'Never';
    }

    const date = new Date(value);

    return date.toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const connectionLabel = (device: AttendanceDevice) => {
    if (device.ip_address) {
        return device.port
            ? `${device.ip_address}:${device.port}`
            : device.ip_address;
    }

    return device.connection_type
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const deviceTypeLabel = (type: string) => {
    return type
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const activeCount = computed(() => {
    return props.devices.data.filter(
        (device) => device.is_active
    ).length;
});

const inactiveCount = computed(() => {
    return props.devices.data.filter(
        (device) => !device.is_active
    ).length;
});

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

const testConnection = (device: AttendanceDevice) => {
    processingDevice.value = device.id;

    router.post(
        route('attendance-devices.test-connection', device.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processingDevice.value = null;
            },
        }
    );
};

const syncDevice = (device: AttendanceDevice) => {
    processingDevice.value = device.id;

    router.post(
        route('attendance-devices.sync', device.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processingDevice.value = null;
            },
        }
    );
};

const toggleDevice = (device: AttendanceDevice) => {
    processingDevice.value = device.id;

    router.patch(
        route('attendance-devices.toggle', device.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processingDevice.value = null;
            },
        }
    );
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">
                    Attendance Devices
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Manage biometric and attendance devices for
                    {{ company.name }}.
                </p>
            </div>

            <Link
                :href="route('attendance-devices.create')"
                class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800"
            >
                + Add Device
            </Link>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div
                class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <p class="text-sm text-slate-500">
                    Devices on this page
                </p>

                <p class="mt-2 text-2xl font-semibold text-slate-900">
                    {{ devices.data.length }}
                </p>
            </div>

            <div
                class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <p class="text-sm text-slate-500">
                    Active
                </p>

                <p class="mt-2 text-2xl font-semibold text-emerald-600">
                    {{ activeCount }}
                </p>
            </div>

            <div
                class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <p class="text-sm text-slate-500">
                    Inactive
                </p>

                <p class="mt-2 text-2xl font-semibold text-slate-500">
                    {{ inactiveCount }}
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
        >
            <div
                class="grid grid-cols-1 gap-3 md:grid-cols-4"
            >
                <div class="md:col-span-2">
                    <label
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Search
                    </label>

                    <input
                        v-model="search"
                        type="text"
                        placeholder="Name, manufacturer, model, serial or IP..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Manufacturer
                    </label>

                    <select
                        v-model="manufacturer"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    >
                        <option value="">
                            All manufacturers
                        </option>

                        <option
                            v-for="item in manufacturers"
                            :key="item"
                            :value="item"
                        >
                            {{ item }}
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Status
                    </label>

                    <select
                        v-model="isActive"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    >
                        <option value="">
                            All statuses
                        </option>

                        <option value="1">
                            Active
                        </option>

                        <option value="0">
                            Inactive
                        </option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <button
                    type="button"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
                    @click="applyFilters"
                >
                    Search
                </button>

                <button
                    type="button"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    @click="clearFilters"
                >
                    Clear
                </button>
            </div>
        </div>

        <!-- Table -->
        <div
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                Device
                            </th>

                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                Manufacturer
                            </th>

                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                Connection
                            </th>

                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                Employees
                            </th>

                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                Punches
                            </th>

                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                Last Sync
                            </th>

                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                Status
                            </th>

                            <th
                                class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        v-if="devices.data.length"
                        class="divide-y divide-slate-100"
                    >
                        <tr
                            v-for="device in devices.data"
                            :key="device.id"
                            class="hover:bg-slate-50"
                        >
                            <td class="px-4 py-4">
                                <div>
                                    <div
                                        class="font-medium text-slate-900"
                                    >
                                        {{ device.name }}
                                    </div>

                                    <div
                                        class="mt-1 text-xs text-slate-500"
                                    >
                                        {{ deviceTypeLabel(device.device_type) }}

                                        <span
                                            v-if="device.serial_number"
                                        >
                                            · {{ device.serial_number }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <div
                                    class="text-sm font-medium text-slate-800"
                                >
                                    {{ device.manufacturer || '—' }}
                                </div>

                                <div
                                    v-if="device.model"
                                    class="mt-1 text-xs text-slate-500"
                                >
                                    {{ device.model }}
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <div
                                    class="text-sm text-slate-800"
                                >
                                    {{ connectionLabel(device) }}
                                </div>

                                <div
                                    class="mt-1 text-xs text-slate-500"
                                >
                                    {{ device.sync_method }}
                                </div>
                            </td>

                            <td
                                class="px-4 py-4 text-center text-sm text-slate-700"
                            >
                                {{
                                    device.active_employee_mappings_count
                                }}
                            </td>

                            <td
                                class="px-4 py-4 text-center text-sm text-slate-700"
                            >
                                {{ device.punches_count }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-4">
                                <span
                                    class="text-sm text-slate-700"
                                >
                                    {{
                                        formatDateTime(
                                            device.last_synced_at
                                        )
                                    }}
                                </span>
                            </td>

                            <td class="px-4 py-4">
                                <span
                                    v-if="device.is_active"
                                    class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700"
                                >
                                    Active
                                </span>

                                <span
                                    v-else
                                    class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600"
                                >
                                    Inactive
                                </span>
                            </td>

                            <td class="px-4 py-4">
                                <div
                                    class="flex justify-end gap-2"
                                >
                                    <Link
                                        :href="
                                            route(
                                                'attendance-devices.edit',
                                                device.id
                                            )
                                        "
                                        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        Edit
                                    </Link>

                                    <Link
                                        :href="
                                            route(
                                                'attendance-devices.show',
                                                device.id
                                            )
                                        "
                                        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        View
                                    </Link>
                                </div>

                                <div
                                    class="mt-2 flex justify-end gap-2"
                                >
                                    <button
                                        type="button"
                                        :disabled="
                                            processingDevice ===
                                            device.id
                                        "
                                        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        @click="
                                            testConnection(device)
                                        "
                                    >
                                        {{
                                            processingDevice ===
                                            device.id
                                                ? 'Working...'
                                                : 'Test'
                                        }}
                                    </button>

                                    <button
                                        type="button"
                                        :disabled="
                                            processingDevice ===
                                            device.id ||
                                            !device.is_active
                                        "
                                        class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                                        @click="syncDevice(device)"
                                    >
                                        Sync
                                    </button>

                                    <button
                                        type="button"
                                        :disabled="
                                            processingDevice ===
                                            device.id
                                        "
                                        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        @click="
                                            toggleDevice(device)
                                        "
                                    >
                                        {{
                                            device.is_active
                                                ? 'Disable'
                                                : 'Enable'
                                        }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>

                    <tbody v-else>
                        <tr>
                            <td
                                colspan="8"
                                class="px-6 py-16 text-center"
                            >
                                <div
                                    class="text-sm font-medium text-slate-700"
                                >
                                    No attendance devices found
                                </div>

                                <p
                                    class="mt-1 text-sm text-slate-500"
                                >
                                    Add your first biometric or attendance
                                    device to get started.
                                </p>

                                <Link
                                    :href="
                                        route(
                                            'attendance-devices.create'
                                        )
                                    "
                                    class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
                                >
                                    Add Device
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="devices.last_page > 1"
                class="flex flex-col gap-3 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-sm text-slate-500">
                    Showing
                    <span class="font-medium text-slate-700">
                        {{ devices.data.length }}
                    </span>
                    of
                    <span class="font-medium text-slate-700">
                        {{ devices.total }}
                    </span>
                    devices
                </p>

                <div class="flex flex-wrap gap-1">
                    <button
                        v-for="(link, index) in devices.links"
                        :key="index"
                        type="button"
                        :disabled="!link.url"
                        class="rounded-lg border px-3 py-1.5 text-sm transition"
                        :class="
                            link.active
                                ? 'border-slate-900 bg-slate-900 text-white'
                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'
                        "
                        @click="goToPage(link.url)"
                    >
                        <span
                            v-html="link.label"
                        />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>