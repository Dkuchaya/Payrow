<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

const props = defineProps<{
    employees: {
        data: Array<{
            id: number
            employee_number: string
            first_name: string
            middle_name: string | null
            last_name: string
            email: string | null
            phone: string | null
            employment_status: string
            employment_type: string
            department: {
                id: number
                name: string
            } | null
            position: {
                id: number
                name: string
            } | null
        }>
        current_page: number
        last_page: number
        total: number
    }

    filters: {
        search: string
        status: string
        department_id: number | null
        position_id: number | null
        employment_type: string
    }

    stats: {
        total: number
        active: number
        on_leave: number
        suspended: number
        separated: number
    }

    departmentStats: Array<{
        id: number
        name: string
        employee_count: number
    }>

    employmentTypeStats: Array<{
        employment_type: string
        employee_count: number
    }>

    departments: Array<{
        id: number
        name: string
    }>

    positions: Array<{
        id: number
        department_id: number | null
        name: string
    }>

    usage: {
        employee_count: number
        employee_limit: number | null
        remaining_seats: number | null
        unlimited: boolean
        limit_reached: boolean
        plan: {
            name: string
        } | null
    }
}>()

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')
const departmentId = ref(
    props.filters.department_id
        ? String(props.filters.department_id)
        : ''
)
const positionId = ref(
    props.filters.position_id
        ? String(props.filters.position_id)
        : ''
)
const employmentType = ref(props.filters.employment_type ?? '')

let searchTimeout: ReturnType<typeof setTimeout>

const filteredPositions = computed(() => {
    if (!departmentId.value) {
        return props.positions
    }

    return props.positions.filter(
        position =>
            String(position.department_id) === departmentId.value
    )
})

function applyFilters() {
    router.get(
        route('employees.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            department_id: departmentId.value || undefined,
            position_id: positionId.value || undefined,
            employment_type: employmentType.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}

function resetFilters() {
    search.value = ''
    status.value = ''
    departmentId.value = ''
    positionId.value = ''
    employmentType.value = ''

    applyFilters()
}

watch(search, () => {
    clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        applyFilters()
    }, 400)
})

watch(status, () => {
    applyFilters()
})

watch(departmentId, () => {
    positionId.value = ''
    applyFilters()
})

watch(positionId, () => {
    applyFilters()
})

watch(employmentType, () => {
    applyFilters()
})

function fullName(employee: {
    first_name: string
    middle_name: string | null
    last_name: string
}) {
    return [
        employee.first_name,
        employee.middle_name,
        employee.last_name,
    ]
        .filter(Boolean)
        .join(' ')
}

function statusLabel(value: string) {
    return value
        .replace('_', ' ')
        .replace(/\b\w/g, letter => letter.toUpperCase())
}

function employmentTypeLabel(value: string) {
    return value
        .replace('_', ' ')
        .replace(/\b\w/g, letter => letter.toUpperCase())
}

function statusClass(status: string) {
    return {
        active: 'bg-green-100 text-green-700',
        on_leave: 'bg-yellow-100 text-yellow-700',
        suspended: 'bg-orange-100 text-orange-700',
        separated: 'bg-red-100 text-red-700',
    }[status] ?? 'bg-gray-100 text-gray-600'
}

function percentage(value: number, total: number) {
    if (!total) {
        return 0
    }

    return Math.round((value / total) * 100)
}

function usagePercentage() {
    if (
        props.usage.unlimited ||
        !props.usage.employee_limit
    ) {
        return 0
    }

    return Math.min(
        100,
        Math.round(
            (props.usage.employee_count /
                props.usage.employee_limit) *
                100
        )
    )
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Employees
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Manage your company's workforce.
                </p>
            </div>

            <Link
                v-if="!usage.limit_reached"
                :href="route('employees.create')"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            >
                <span class="mr-2 text-lg">+</span>
                Add Employee
            </Link>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">

            <!-- Total -->
            <div
                class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Total Employees
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ stats.total }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-100 text-blue-600"
                    >
                        👥
                    </div>
                </div>
            </div>

            <!-- Active -->
            <div
                class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Active
                        </p>

                        <p class="mt-2 text-3xl font-bold text-green-600">
                            {{ stats.active }}
                        </p>

                        <p class="mt-1 text-xs text-gray-400">
                            {{ percentage(stats.active, stats.total) }}%
                            of workforce
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg bg-green-100 text-green-600"
                    >
                        ✓
                    </div>
                </div>
            </div>

            <!-- On Leave -->
            <div
                class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            On Leave
                        </p>

                        <p class="mt-2 text-3xl font-bold text-yellow-600">
                            {{ stats.on_leave }}
                        </p>

                        <p class="mt-1 text-xs text-gray-400">
                            {{ percentage(stats.on_leave, stats.total) }}%
                            of workforce
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg bg-yellow-100 text-yellow-600"
                    >
                        ⏳
                    </div>
                </div>
            </div>

            <!-- Suspended -->
            <div
                class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Suspended
                        </p>

                        <p class="mt-2 text-3xl font-bold text-orange-600">
                            {{ stats.suspended }}
                        </p>

                        <p class="mt-1 text-xs text-gray-400">
                            {{ percentage(stats.suspended, stats.total) }}%
                            of workforce
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg bg-orange-100 text-orange-600"
                    >
                        !
                    </div>
                </div>
            </div>

            <!-- Separated -->
            <div
                class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Separated
                        </p>

                        <p class="mt-2 text-3xl font-bold text-red-600">
                            {{ stats.separated }}
                        </p>

                        <p class="mt-1 text-xs text-gray-400">
                            {{ percentage(stats.separated, stats.total) }}%
                            of workforce
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg bg-red-100 text-red-600"
                    >
                        ×
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscription Usage -->
        <div
            class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Employee Seats
                    </p>

                    <div class="mt-1 flex items-baseline gap-2">
                        <span
                            class="text-2xl font-bold text-gray-900"
                        >
                            {{ usage.employee_count }}
                        </span>

                        <span
                            v-if="usage.unlimited"
                            class="text-sm text-gray-500"
                        >
                            / Unlimited
                        </span>

                        <span
                            v-else
                            class="text-sm text-gray-500"
                        >
                            / {{ usage.employee_limit }}
                        </span>
                    </div>
                </div>

                <div
                    v-if="usage.plan"
                    class="text-sm text-gray-500"
                >
                    Plan:
                    <span class="font-semibold text-gray-800">
                        {{ usage.plan.name }}
                    </span>
                </div>
            </div>

            <div
                v-if="!usage.unlimited"
                class="mt-4"
            >
                <div
                    class="h-2 overflow-hidden rounded-full bg-gray-200"
                >
                    <div
                        class="h-full rounded-full transition-all"
                        :class="
                            usage.limit_reached
                                ? 'bg-red-500'
                                : usagePercentage() >= 90
                                    ? 'bg-orange-500'
                                    : 'bg-blue-600'
                        "
                        :style="{
                            width: `${usagePercentage()}%`,
                        }"
                    />
                </div>

                <div
                    class="mt-2 flex justify-between text-xs text-gray-500"
                >
                    <span>
                        {{ usagePercentage() }}% used
                    </span>

                    <span>
                        {{ usage.remaining_seats }} seats remaining
                    </span>
                </div>
            </div>

            <div
                v-if="usage.limit_reached"
                class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700"
            >
                Your employee seat limit has been reached.
                Upgrade your subscription to add more employees.
            </div>

            <div
                v-else-if="
                    !usage.unlimited &&
                    usage.employee_limit &&
                    usage.employee_count / usage.employee_limit >= 0.8
                "
                class="mt-4 rounded-lg bg-yellow-50 p-3 text-sm text-yellow-700"
            >
                You are approaching your employee seat limit.
                {{ usage.remaining_seats }} seats remaining.
            </div>
        </div>

        <!-- Workforce Overview -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            <!-- Department -->
            <div
                class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
            >
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Employees by Department
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Workforce distribution across departments.
                    </p>
                </div>

                <div
                    v-if="departmentStats.length"
                    class="space-y-4"
                >
                    <div
                        v-for="department in departmentStats"
                        :key="department.id"
                    >
                        <div
                            class="mb-1 flex items-center justify-between text-sm"
                        >
                            <span class="font-medium text-gray-700">
                                {{ department.name }}
                            </span>

                            <span class="text-gray-500">
                                {{ department.employee_count }}
                            </span>
                        </div>

                        <div
                            class="h-2 overflow-hidden rounded-full bg-gray-100"
                        >
                            <div
                                class="h-full rounded-full bg-blue-600 transition-all"
                                :style="{
                                    width: `${percentage(
                                        department.employee_count,
                                        stats.total
                                    )}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="py-8 text-center text-sm text-gray-500"
                >
                    No department data available.
                </div>
            </div>

            <!-- Employment Type -->
            <div
                class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
            >
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Employment Type
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Breakdown of employment contracts and types.
                    </p>
                </div>

                <div
                    v-if="employmentTypeStats.length"
                    class="space-y-4"
                >
                    <div
                        v-for="type in employmentTypeStats"
                        :key="type.employment_type"
                    >
                        <div
                            class="mb-1 flex items-center justify-between text-sm"
                        >
                            <span
                                class="font-medium capitalize text-gray-700"
                            >
                                {{
                                    employmentTypeLabel(
                                        type.employment_type
                                    )
                                }}
                            </span>

                            <span class="text-gray-500">
                                {{ type.employee_count }}
                            </span>
                        </div>

                        <div
                            class="h-2 overflow-hidden rounded-full bg-gray-100"
                        >
                            <div
                                class="h-full rounded-full bg-indigo-600 transition-all"
                                :style="{
                                    width: `${percentage(
                                        type.employee_count,
                                        stats.total
                                    )}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="py-8 text-center text-sm text-gray-500"
                >
                    No employment type data available.
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
        >
            <div
                class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center"
            >
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Employee Directory
                    </h2>

                    <p class="text-sm text-gray-500">
                        Search and filter your employees.
                    </p>
                </div>

                <button
                    v-if="
                        search ||
                        status ||
                        departmentId ||
                        positionId ||
                        employmentType
                    "
                    type="button"
                    class="text-sm font-medium text-blue-600 hover:text-blue-800"
                    @click="resetFilters"
                >
                    Reset Filters
                </button>
            </div>

            <div
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5"
            >
                <!-- Search -->
                <div class="lg:col-span-2">
                    <label
                        class="block text-sm font-medium text-gray-700"
                    >
                        Search
                    </label>

                    <input
                        v-model="search"
                        type="text"
                        placeholder="Employee number, name, email or phone..."
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                <!-- Status -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700"
                    >
                        Status
                    </label>

                    <select
                        v-model="status"
                        class="mt-1 block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">
                            All Statuses
                        </option>

                        <option value="active">
                            Active
                        </option>

                        <option value="on_leave">
                            On Leave
                        </option>

                        <option value="suspended">
                            Suspended
                        </option>

                        <option value="separated">
                            Separated
                        </option>
                    </select>
                </div>

                <!-- Department -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700"
                    >
                        Department
                    </label>

                    <select
                        v-model="departmentId"
                        class="mt-1 block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">
                            All Departments
                        </option>

                        <option
                            v-for="department in departments"
                            :key="department.id"
                            :value="String(department.id)"
                        >
                            {{ department.name }}
                        </option>
                    </select>
                </div>

                <!-- Employment Type -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700"
                    >
                        Employment Type
                    </label>

                    <select
                        v-model="employmentType"
                        class="mt-1 block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">
                            All Types
                        </option>

                        <option value="permanent">
                            Permanent
                        </option>

                        <option value="contract">
                            Contract
                        </option>

                        <option value="temporary">
                            Temporary
                        </option>

                        <option value="casual">
                            Casual
                        </option>

                        <option value="intern">
                            Intern
                        </option>

                        <option value="part_time">
                            Part Time
                        </option>
                    </select>
                </div>
            </div>

            <!-- Position -->
            <div class="mt-4 max-w-md">
                <label
                    class="block text-sm font-medium text-gray-700"
                >
                    Position
                </label>

                <select
                    v-model="positionId"
                    class="mt-1 block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">
                        All Positions
                    </option>

                    <option
                        v-for="position in filteredPositions"
                        :key="position.id"
                        :value="String(position.id)"
                    >
                        {{ position.name }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Employee Table -->
        <div
            class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200"
        >
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Employee
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Department
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Position
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Employment Type
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Contact
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Status
                            </th>

                            <th
                                class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        class="divide-y divide-gray-200 bg-white"
                    >
                        <tr
                            v-for="employee in employees.data"
                            :key="employee.id"
                            class="transition hover:bg-gray-50"
                        >
                            <!-- Employee -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700"
                                    >
                                        {{
                                            employee.first_name.charAt(0)
                                        }}{{
                                            employee.last_name.charAt(0)
                                        }}
                                    </div>

                                    <div>
                                        <div
                                            class="font-medium text-gray-900"
                                        >
                                            {{ fullName(employee) }}
                                        </div>

                                        <div
                                            class="text-sm text-gray-500"
                                        >
                                            {{ employee.employee_number }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Department -->
                            <td
                                class="whitespace-nowrap px-6 py-4 text-sm text-gray-600"
                            >
                                {{
                                    employee.department?.name || '—'
                                }}
                            </td>

                            <!-- Position -->
                            <td
                                class="whitespace-nowrap px-6 py-4 text-sm text-gray-600"
                            >
                                {{
                                    employee.position?.name || '—'
                                }}
                            </td>

                            <!-- Employment Type -->
                            <td
                                class="whitespace-nowrap px-6 py-4 text-sm text-gray-600"
                            >
                                {{
                                    employmentTypeLabel(
                                        employee.employment_type
                                    )
                                }}
                            </td>

                            <!-- Contact -->
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div>
                                    {{ employee.phone || '—' }}
                                </div>

                                <div
                                    v-if="employee.email"
                                    class="mt-1 text-xs text-gray-400"
                                >
                                    {{ employee.email }}
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        statusClass(
                                            employee.employment_status
                                        )
                                    "
                                >
                                    {{
                                        statusLabel(
                                            employee.employment_status
                                        )
                                    }}
                                </span>
                            </td>

                            <!-- Actions -->
                        <td
                            class="whitespace-nowrap px-6 py-4 text-right"
                        >
                            <div class="flex items-center justify-end gap-2">

                                <Link
                                    :href="
                                        route(
                                            'employees.show',
                                            employee.id
                                        )
                                    "
                                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                >
                                    View
                                </Link>

                                <Link
                                    :href="
                                        route(
                                            'employees.work-schedules.index',
                                            employee.id
                                        )
                                    "
                                    class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 transition hover:bg-blue-100"
                                >
                                    Work Schedule
                                </Link>

                            </div>
                        </td>
                        </tr>

                        <tr
                            v-if="employees.data.length === 0"
                        >
                            <td
                                colspan="7"
                                class="px-6 py-16 text-center"
                            >
                                <div
                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-xl"
                                >
                                    👥
                                </div>

                                <p
                                    class="mt-3 text-sm font-medium text-gray-900"
                                >
                                    No employees found
                                </p>

                                <p
                                    class="mt-1 text-sm text-gray-500"
                                >
                                    Try changing your search or filters.
                                </p>

                                <button
                                    v-if="
                                        search ||
                                        status ||
                                        departmentId ||
                                        positionId ||
                                        employmentType
                                    "
                                    type="button"
                                    class="mt-4 text-sm font-medium text-blue-600 hover:text-blue-800"
                                    @click="resetFilters"
                                >
                                    Clear filters
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="employees.total > 0"
                class="flex flex-col gap-2 border-t border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="text-sm text-gray-500">
                    Showing
                    <span class="font-medium text-gray-700">
                        {{ employees.data.length }}
                    </span>
                    of
                    <span class="font-medium text-gray-700">
                        {{ employees.total }}
                    </span>
                    employees
                </div>

                <div class="text-sm text-gray-500">
                    Page {{ employees.current_page }}
                    of {{ employees.last_page }}
                </div>
            </div>
        </div>
    </div>
</template>