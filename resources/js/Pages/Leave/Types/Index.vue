<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface LeaveType {
    id: number
    name: string
    code: string
    description: string | null
    is_paid: boolean
    requires_balance: boolean
    requires_attachment: boolean
    is_active: boolean
    sort_order: number
}

interface Company {
    id: number
    name: string
}

const props = defineProps<{
    leaveTypes: LeaveType[]
    company: Company
}>()

const page = usePage<any>()

const search = ref('')
const showModal = ref(false)
const editing = ref<LeaveType | null>(null)

const form = useForm({
    name: '',
    code: '',
    description: '',
    is_paid: true,
    requires_balance: true,
    requires_attachment: false,
    is_active: true,
    sort_order: 0,
})

const permissions = computed<string[]>(() => {
    return page.props.auth?.permissions ?? []
})

const isSuperAdmin = computed(() => {
    return page.props.auth?.user?.is_super_admin === true
})

const canManage = computed(() => {
    return (
        isSuperAdmin.value ||
        permissions.value.includes('leave.manage')
    )
})

const filteredLeaveTypes = computed(() => {
    const term = search.value.trim().toLowerCase()

    if (!term) {
        return props.leaveTypes
    }

    return props.leaveTypes.filter((leaveType) => {
        return (
            leaveType.name.toLowerCase().includes(term) ||
            leaveType.code.toLowerCase().includes(term) ||
            (leaveType.description ?? '').toLowerCase().includes(term)
        )
    })
})

const totalTypes = computed(() => props.leaveTypes.length)

const activeTypes = computed(() => {
    return props.leaveTypes.filter((item) => item.is_active).length
})

const paidTypes = computed(() => {
    return props.leaveTypes.filter((item) => item.is_paid).length
})

const unpaidTypes = computed(() => {
    return props.leaveTypes.filter((item) => !item.is_paid).length
})

function openCreateModal() {
    editing.value = null

    form.reset()

    form.name = ''
    form.code = ''
    form.description = ''
    form.is_paid = true
    form.requires_balance = true
    form.requires_attachment = false
    form.is_active = true
    form.sort_order = props.leaveTypes.length

    form.clearErrors()

    showModal.value = true
}

function openEditModal(leaveType: LeaveType) {
    editing.value = leaveType

    form.name = leaveType.name
    form.code = leaveType.code
    form.description = leaveType.description ?? ''
    form.is_paid = leaveType.is_paid
    form.requires_balance = leaveType.requires_balance
    form.requires_attachment = leaveType.requires_attachment
    form.is_active = leaveType.is_active
    form.sort_order = leaveType.sort_order

    form.clearErrors()

    showModal.value = true
}

function closeModal() {
    if (form.processing) {
        return
    }

    showModal.value = false
    editing.value = null
    form.clearErrors()
}

function submit() {
    if (editing.value) {
        form.put(
            route('leave.types.update', editing.value.id),
            {
                preserveScroll: true,

                onSuccess: () => {
                    closeModal()

                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: 'Leave type updated successfully.',
                        timer: 1800,
                        showConfirmButton: false,
                    })
                },
            }
        )

        return
    }

    form.post(route('leave.types.store'), {
        preserveScroll: true,

        onSuccess: () => {
            closeModal()

            Swal.fire({
                icon: 'success',
                title: 'Created',
                text: 'Leave type created successfully.',
                timer: 1800,
                showConfirmButton: false,
            })
        },
    })
}

function toggleStatus(leaveType: LeaveType) {
    const action = leaveType.is_active
        ? 'deactivate'
        : 'activate'

    Swal.fire({
        title: `${action.charAt(0).toUpperCase() + action.slice(1)} leave type?`,
        text: `${leaveType.name} will be ${action}d.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: `Yes, ${action}`,
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#2563eb',
    }).then((result) => {
        if (!result.isConfirmed) {
            return
        }

        router.patch(
            route(
                'leave.types.toggle-status',
                leaveType.id
            ),
            {},
            {
                preserveScroll: true,

                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: `Leave type ${action}d successfully.`,
                        timer: 1500,
                        showConfirmButton: false,
                    })
                },
            }
        )
    })
}

function deleteLeaveType(leaveType: LeaveType) {
    Swal.fire({
        title: 'Delete leave type?',
        text: `${leaveType.name} will be permanently deleted if it is not already in use.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
    }).then((result) => {
        if (!result.isConfirmed) {
            return
        }

        router.delete(
            route(
                'leave.types.destroy',
                leaveType.id
            ),
            {
                preserveScroll: true,

                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted',
                        text: 'Leave type deleted successfully.',
                        timer: 1500,
                        showConfirmButton: false,
                    })
                },
            }
        )
    })
}
</script>

<template>
    <Head title="Leave Types" />

    <div class="space-y-6 bg-white">
        <!-- Header -->
       <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">
            Leave Types
        </h1>

        <p class="mt-1 text-sm text-gray-500">
            Configure the types of leave available to employees in {{ company.name }}.
        </p>
    </div>

    <button
        type="button"
        @click="openCreateModal"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 4v16m8-8H4"
            />
        </svg>

        Create Leave Type
    </button>
</div>

        <!-- Statistics -->
        <div
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            <!-- Total -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Total Types
                        </p>

                        <p
                            class="mt-2 text-3xl font-bold text-gray-900"
                        >
                            {{ totalTypes }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-blue-600"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm2 3h8a1 1 0 110 2H6a1 1 0 110-2zm0 4h8a1 1 0 110 2H6a1 1 0 110-2zm0 4h5a1 1 0 110 2H6a1 1 0 110-2z"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Active -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Active
                        </p>

                        <p
                            class="mt-2 text-3xl font-bold text-green-600"
                        >
                            {{ activeTypes }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg bg-green-50 text-green-600"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.172 7.707 8.879a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l3-3z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Paid -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Paid Leave
                        </p>

                        <p
                            class="mt-2 text-3xl font-bold text-blue-600"
                        >
                            {{ paidTypes }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-blue-600"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                d="M8.433 7.418c.155-.46.617-.918 1.377-.918.865 0 1.317.53 1.317 1.15 0 .555-.27.943-.795 1.282-.528.342-.88.623-.88 1.24v.328h1.54v-.27c0-.31.13-.54.557-.812.67-.424 1.214-.99 1.214-1.91 0-1.326-1.08-2.508-2.954-2.508-1.598 0-2.82.93-3.21 2.418l1.834.5z"
                            />
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-2a6 6 0 100-12 6 6 0 000 12z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Unpaid -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Unpaid Leave
                        </p>

                        <p
                            class="mt-2 text-3xl font-bold text-orange-600"
                        >
                            {{ unpaidTypes }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg bg-orange-50 text-orange-600"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3a1 1 0 00.293.707l2 2a1 1 0 001.414-1.414L11 9.586V7z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
        >
            <!-- Toolbar -->
            <div
                class="flex flex-col gap-3 border-b border-gray-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="relative w-full sm:max-w-md">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.817-4.817A6 6 0 012 8z"
                            clip-rule="evenodd"
                        />
                    </svg>

                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search leave types..."
                        class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                    />
                </div>

                <div
                    v-if="search"
                    class="text-sm text-gray-500"
                >
                    {{ filteredLeaveTypes.length }}
                    result{{ filteredLeaveTypes.length === 1 ? '' : 's' }}
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="filteredLeaveTypes.length === 0"
                class="px-6 py-16 text-center"
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-8 w-8 text-gray-400"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            d="M5 3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5zm3 3h4a1 1 0 110 2H8a1 1 0 110-2zm0 4h4a1 1 0 110 2H8a1 1 0 110-2zm0 4h2a1 1 0 110 2H8a1 1 0 110-2z"
                        />
                    </svg>
                </div>

                <h3
                    class="mt-4 text-base font-semibold text-gray-900"
                >
                    No leave types found
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    {{
                        search
                            ? 'Try changing your search.'
                            : 'Create your first leave type to get started.'
                    }}
                </p>

                <button
                    v-if="canManage && !search"
                    type="button"
                    class="mt-5 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                    @click="openCreateModal"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                            clip-rule="evenodd"
                        />
                    </svg>

                    Add Leave Type
                </button>
            </div>

            <!-- Table -->
            <div
                v-else
                class="overflow-x-auto"
            >
                <table
                    class="min-w-full divide-y divide-gray-200"
                >
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Leave Type
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Code
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Payment
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Balance
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Attachment
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Status
                            </th>

                            <th
                                v-if="canManage"
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
                            v-for="leaveType in filteredLeaveTypes"
                            :key="leaveType.id"
                            class="transition hover:bg-gray-50"
                        >
                            <!-- Name -->
                            <td class="px-6 py-4">
                                <div
                                    class="font-semibold text-gray-900"
                                >
                                    {{ leaveType.name }}
                                </div>

                                <div
                                    v-if="leaveType.description"
                                    class="mt-1 max-w-xs truncate text-xs text-gray-500"
                                >
                                    {{ leaveType.description }}
                                </div>
                            </td>

                            <!-- Code -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    class="rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700"
                                >
                                    {{ leaveType.code }}
                                </span>
                            </td>

                            <!-- Payment -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    v-if="leaveType.is_paid"
                                    class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700"
                                >
                                    Paid
                                </span>

                                <span
                                    v-else
                                    class="inline-flex rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700"
                                >
                                    Unpaid
                                </span>
                            </td>

                            <!-- Balance -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    v-if="leaveType.requires_balance"
                                    class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700"
                                >
                                    Required
                                </span>

                                <span
                                    v-else
                                    class="text-sm text-gray-400"
                                >
                                    Not required
                                </span>
                            </td>

                            <!-- Attachment -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    v-if="leaveType.requires_attachment"
                                    class="inline-flex rounded-full bg-purple-50 px-2.5 py-1 text-xs font-medium text-purple-700"
                                >
                                    Required
                                </span>

                                <span
                                    v-else
                                    class="text-sm text-gray-400"
                                >
                                    Optional
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    v-if="leaveType.is_active"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-green-500"
                                    ></span>

                                    Active
                                </span>

                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-gray-400"
                                    ></span>

                                    Inactive
                                </span>
                            </td>

                            <!-- Actions -->
                            <td
                                v-if="canManage"
                                class="whitespace-nowrap px-6 py-4 text-right"
                            >
                                <div
                                    class="flex justify-end gap-1"
                                >
                                    <!-- Edit -->
                                    <button
                                        type="button"
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-blue-50 hover:text-blue-600"
                                        title="Edit"
                                        @click="openEditModal(leaveType)"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 6.207l2.828 2.828-7.5 7.5H4v-2.707l7.379-7.621z"
                                            />
                                        </svg>
                                    </button>

                                    <!-- Toggle -->
                                    <button
                                        type="button"
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-yellow-50 hover:text-yellow-600"
                                        :title="
                                            leaveType.is_active
                                                ? 'Deactivate'
                                                : 'Activate'
                                        "
                                        @click="toggleStatus(leaveType)"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                v-if="leaveType.is_active"
                                                d="M10 3a1 1 0 011 1v5.586l3.707 3.707a1 1 0 01-1.414 1.414l-4-4A1 1 0 019 10V4a1 1 0 011-1z"
                                            />

                                            <path
                                                v-else
                                                d="M10 2a8 8 0 108 8 8 8 0 00-8-8zm1 12H9V9h2zm0-7H9V5h2z"
                                            />
                                        </svg>
                                    </button>

                                    <!-- Delete -->
                                    <button
                                        type="button"
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-red-50 hover:text-red-600"
                                        title="Delete"
                                        @click="deleteLeaveType(leaveType)"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 100 2h.293l.707 10.243A2 2 0 006.994 18h6.012a2 2 0 001.994-1.757L15.707 6H16a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM8 8a1 1 0 012 0v7a1 1 0 11-2 0V8zm4 0a1 1 0 012 0v7a1 1 0 11-2 0V8z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create / Edit Modal -->
    <div
        v-if="showModal"
        class="fixed inset-0 z-50 overflow-y-auto"
    >
        <div
            class="flex min-h-screen items-center justify-center px-4 py-8"
        >
            <!-- Overlay -->
            <div
                class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm"
                @click="closeModal"
            ></div>

            <!-- Modal -->
            <div
                class="relative z-10 w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between border-b border-gray-200 px-6 py-5"
                >
                    <div>
                        <h2
                            class="text-lg font-bold text-gray-900"
                        >
                            {{
                                editing
                                    ? 'Edit Leave Type'
                                    : 'Add Leave Type'
                            }}
                        </h2>

                        <p
                            class="mt-1 text-sm text-gray-500"
                        >
                            Configure how this leave type behaves.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                        @click="closeModal"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit">
                    <div class="space-y-6 px-6 py-6">
                        <!-- Basic Information -->
                        <div>
                            <div
                                class="mb-4 flex items-center gap-3"
                            >
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4z"
                                        />
                                    </svg>
                                </div>

                                <div>
                                    <h3
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        Basic Information
                                    </h3>

                                    <p
                                        class="text-xs text-gray-500"
                                    >
                                        Define the name and identification
                                        code.
                                    </p>
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-1 gap-5 sm:grid-cols-2"
                            >
                                <!-- Name -->
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-gray-700"
                                    >
                                        Name
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        v-model="form.name"
                                        type="text"
                                        placeholder="e.g. Annual Leave"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                                    />

                                    <p
                                        v-if="form.errors.name"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <!-- Code -->
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-gray-700"
                                    >
                                        Code
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        v-model="form.code"
                                        type="text"
                                        placeholder="e.g. ANNUAL"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm uppercase text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                                    />

                                    <p
                                        v-if="form.errors.code"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ form.errors.code }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Description
                            </label>

                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Describe this leave type..."
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                            ></textarea>

                            <p
                                v-if="form.errors.description"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <!-- Settings -->
                        <div
                            class="rounded-xl border border-gray-200 bg-gray-50 p-5"
                        >
                            <div class="mb-4">
                                <h3
                                    class="text-sm font-semibold text-gray-900"
                                >
                                    Leave Settings
                                </h3>

                                <p
                                    class="mt-1 text-xs text-gray-500"
                                >
                                    Configure how employees can use this leave
                                    type.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <!-- Paid -->
                                <label
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-white p-4 transition hover:border-blue-200 hover:bg-blue-50/30"
                                >
                                    <input
                                        v-model="form.is_paid"
                                        type="checkbox"
                                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />

                                    <span>
                                        <span
                                            class="block text-sm font-medium text-gray-800"
                                        >
                                            Paid Leave
                                        </span>

                                        <span
                                            class="mt-0.5 block text-xs text-gray-500"
                                        >
                                            Employee continues receiving
                                            normal pay.
                                        </span>
                                    </span>
                                </label>

                                <!-- Balance -->
                                <label
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-white p-4 transition hover:border-blue-200 hover:bg-blue-50/30"
                                >
                                    <input
                                        v-model="form.requires_balance"
                                        type="checkbox"
                                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />

                                    <span>
                                        <span
                                            class="block text-sm font-medium text-gray-800"
                                        >
                                            Requires Balance
                                        </span>

                                        <span
                                            class="mt-0.5 block text-xs text-gray-500"
                                        >
                                            Requests consume allocated leave
                                            days.
                                        </span>
                                    </span>
                                </label>

                                <!-- Attachment -->
                                <label
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-white p-4 transition hover:border-blue-200 hover:bg-blue-50/30"
                                >
                                    <input
                                        v-model="form.requires_attachment"
                                        type="checkbox"
                                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />

                                    <span>
                                        <span
                                            class="block text-sm font-medium text-gray-800"
                                        >
                                            Requires Attachment
                                        </span>

                                        <span
                                            class="mt-0.5 block text-xs text-gray-500"
                                        >
                                            Supporting documentation is
                                            required.
                                        </span>
                                    </span>
                                </label>

                                <!-- Active -->
                                <label
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-white p-4 transition hover:border-blue-200 hover:bg-blue-50/30"
                                >
                                    <input
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />

                                    <span>
                                        <span
                                            class="block text-sm font-medium text-gray-800"
                                        >
                                            Active
                                        </span>

                                        <span
                                            class="mt-0.5 block text-xs text-gray-500"
                                        >
                                            Allow this leave type to be used.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Display Order -->
                        <div class="sm:w-1/2">
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Display Order
                            </label>

                            <input
                                v-model.number="form.sort_order"
                                type="number"
                                min="0"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                            />

                            <p class="mt-1 text-xs text-gray-500">
                                Lower numbers appear first.
                            </p>

                            <p
                                v-if="form.errors.sort_order"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ form.errors.sort_order }}
                            </p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end"
                    >
                        <button
                            type="button"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="form.processing"
                            @click="closeModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            <span
                                v-if="form.processing"
                                class="inline-flex items-center gap-2"
                            >
                                <svg
                                    class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>

                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                    ></path>
                                </svg>

                                Saving...
                            </span>

                            <span v-else>
                                {{
                                    editing
                                        ? 'Update Leave Type'
                                        : 'Create Leave Type'
                                }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>