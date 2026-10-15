<script setup lang="ts">
import { reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

interface ScheduleDay {
    id: number
    day_of_week: number
    is_working_day: boolean
    start_time: string | null
    end_time: string | null
    break_minutes: number
    grace_period_minutes: number | null
    early_departure_grace_minutes: number | null
    allow_overtime: boolean | null
}

interface Schedule {
    id: number
    name: string
    code: string
    description: string | null
    default_start_time: string | null
    default_end_time: string | null
    default_break_minutes: number
    grace_period_minutes: number
    early_departure_grace_minutes: number
    overtime_after_minutes: number
    allow_overtime: boolean
    is_active: boolean
    days: ScheduleDay[]
}

interface Company {
    id: number
    name: string
}

const props = defineProps<{
    company: Company
    schedule: Schedule
}>()

interface FormDay {
    id: number | null
    day_of_week: number
    is_working_day: boolean
    start_time: string
    end_time: string
    break_minutes: number
    grace_period_minutes: number | null
    early_departure_grace_minutes: number | null
    allow_overtime: boolean | null
}

const dayNames = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday',
]

const createDefaultDay = (dayOfWeek: number): FormDay => ({
    id: null,
    day_of_week: dayOfWeek,
    is_working_day: dayOfWeek <= 5,
    start_time: '08:00',
    end_time: '17:00',
    break_minutes: 60,
    grace_period_minutes: null,
    early_departure_grace_minutes: null,
    allow_overtime: null,
})

const existingDays = [...props.schedule.days].sort(
    (a, b) => a.day_of_week - b.day_of_week
)

const form = reactive({
    name: props.schedule.name,
    code: props.schedule.code,
    description: props.schedule.description ?? '',

    default_start_time:
        props.schedule.default_start_time?.substring(0, 5) ?? '08:00',

    default_end_time:
        props.schedule.default_end_time?.substring(0, 5) ?? '17:00',

    default_break_minutes:
        props.schedule.default_break_minutes ?? 60,

    grace_period_minutes:
        props.schedule.grace_period_minutes ?? 0,

    early_departure_grace_minutes:
        props.schedule.early_departure_grace_minutes ?? 0,

    overtime_after_minutes:
        props.schedule.overtime_after_minutes ?? 0,

    allow_overtime:
        Boolean(props.schedule.allow_overtime),

    is_active:
        Boolean(props.schedule.is_active),

    days: Array.from({ length: 7 }, (_, index) => {
        const dayOfWeek = index + 1

        const existing = existingDays.find(
            day => day.day_of_week === dayOfWeek
        )

        if (!existing) {
            return createDefaultDay(dayOfWeek)
        }

        return {
            id: existing.id,
            day_of_week: existing.day_of_week,
            is_working_day: Boolean(existing.is_working_day),
            start_time: existing.start_time?.substring(0, 5) ?? '',
            end_time: existing.end_time?.substring(0, 5) ?? '',
            break_minutes: existing.break_minutes ?? 0,
            grace_period_minutes:
                existing.grace_period_minutes ?? null,
            early_departure_grace_minutes:
                existing.early_departure_grace_minutes ?? null,
            allow_overtime:
                existing.allow_overtime ?? null,
        }
    }) as FormDay[],
})

const processing = ref(false)

const errors = ref<Record<string, string>>({})

const submit = () => {
    processing.value = true
    errors.value = {}

    router.put(
        route('work-schedules.update', props.schedule.id),
        form,
        {
            preserveScroll: true,

            onError: validationErrors => {
                errors.value =
                    validationErrors as Record<string, string>
            },

            onFinish: () => {
                processing.value = false
            },
        }
    )
}

const errorFor = (field: string) => {
    return errors.value[field]
}

const toggleDay = (day: FormDay) => {
    if (!day.is_working_day) {
        day.start_time = ''
        day.end_time = ''
    } else {
        if (!day.start_time) {
            day.start_time = form.default_start_time
        }

        if (!day.end_time) {
            day.end_time = form.default_end_time
        }
    }
}
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">

                <!-- Header -->
                <div class="mb-6">
                    <Link
                        :href="route('work-schedules.show', schedule.id)"
                        class="mb-2 inline-block text-sm text-gray-500 hover:text-gray-700"
                    >
                        ← Back to Work Schedule
                    </Link>

                    <h1 class="text-2xl font-bold text-gray-900">
                        Edit Work Schedule
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ schedule.name }}
                        ·
                        {{ company.name }}
                    </p>
                </div>

                <!-- General Information -->
                <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="font-semibold text-gray-900">
                            General Information
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">

                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Schedule Name
                            </label>

                            <input
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <p
                                v-if="errorFor('name')"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ errorFor('name') }}
                            </p>
                        </div>

                        <!-- Code -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Schedule Code
                            </label>

                            <input
                                v-model="form.code"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <p
                                v-if="errorFor('code')"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ errorFor('code') }}
                            </p>
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Description
                            </label>

                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>

                            <p
                                v-if="errorFor('description')"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ errorFor('description') }}
                            </p>
                        </div>

                    </div>
                </div>

                <!-- Default Settings -->
                <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="font-semibold text-gray-900">
                            Default Settings
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Default Start
                            </label>

                            <input
                                v-model="form.default_start_time"
                                type="time"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Default End
                            </label>

                            <input
                                v-model="form.default_end_time"
                                type="time"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Break Minutes
                            </label>

                            <input
                                v-model.number="form.default_break_minutes"
                                type="number"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Grace Period
                            </label>

                            <input
                                v-model.number="form.grace_period_minutes"
                                type="number"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Early Departure Grace
                            </label>

                            <input
                                v-model.number="form.early_departure_grace_minutes"
                                type="number"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Overtime After
                            </label>

                            <input
                                v-model.number="form.overtime_after_minutes"
                                type="number"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div class="flex items-center gap-3 pt-6">
                            <input
                                id="allow_overtime"
                                v-model="form.allow_overtime"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />

                            <label
                                for="allow_overtime"
                                class="text-sm font-medium text-gray-700"
                            >
                                Allow Overtime
                            </label>
                        </div>

                        <div class="flex items-center gap-3 pt-6">
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />

                            <label
                                for="is_active"
                                class="text-sm font-medium text-gray-700"
                            >
                                Active
                            </label>
                        </div>

                    </div>
                </div>

                <!-- Weekly Schedule -->
                <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="font-semibold text-gray-900">
                            Weekly Schedule
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Configure working days, hours and breaks.
                        </p>
                    </div>

                    <div class="divide-y divide-gray-200">
                        <div
                            v-for="day in form.days"
                            :key="day.day_of_week"
                            class="p-6"
                        >
                            <div class="grid grid-cols-1 gap-5 lg:grid-cols-6 lg:items-end">

                                <!-- Day -->
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ dayNames[day.day_of_week - 1] }}
                                    </p>
                                </div>

                                <!-- Working Day -->
                                <div class="flex items-center gap-3">
                                    <input
                                        :id="`working-day-${day.day_of_week}`"
                                        v-model="day.is_working_day"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        @change="toggleDay(day)"
                                    />

                                    <label
                                        :for="`working-day-${day.day_of_week}`"
                                        class="text-sm text-gray-700"
                                    >
                                        Working Day
                                    </label>
                                </div>

                                <!-- Start -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">
                                        Start
                                    </label>

                                    <input
                                        v-model="day.start_time"
                                        type="time"
                                        :disabled="!day.is_working_day"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 disabled:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>

                                <!-- End -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">
                                        End
                                    </label>

                                    <input
                                        v-model="day.end_time"
                                        type="time"
                                        :disabled="!day.is_working_day"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 disabled:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>

                                <!-- Break -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">
                                        Break
                                    </label>

                                    <input
                                        v-model.number="day.break_minutes"
                                        type="number"
                                        min="0"
                                        :disabled="!day.is_working_day"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 disabled:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>

                                <!-- Overtime -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">
                                        Overtime
                                    </label>

                                    <select
                                        v-model="day.allow_overtime"
                                        :disabled="!day.is_working_day"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 disabled:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option :value="null">
                                            Use Default
                                        </option>

                                        <option :value="true">
                                            Allowed
                                        </option>

                                        <option :value="false">
                                            Not Allowed
                                        </option>
                                    </select>
                                </div>

                            </div>

                            <!-- Day-level settings -->
                            <div
                                v-if="day.is_working_day"
                                class="mt-4 grid grid-cols-1 gap-4 border-t border-gray-100 pt-4 sm:grid-cols-2"
                            >
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">
                                        Day Grace Period
                                    </label>

                                    <input
                                        v-model.number="day.grace_period_minutes"
                                        type="number"
                                        min="0"
                                        placeholder="Use default"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-500">
                                        Early Departure Grace
                                    </label>

                                    <input
                                        v-model.number="day.early_departure_grace_minutes"
                                        type="number"
                                        min="0"
                                        placeholder="Use default"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="route('work-schedules.show', schedule.id)"
                        class="rounded-md border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </Link>

                    <button
                        type="button"
                        :disabled="processing"
                        class="rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="submit"
                    >
                        <span v-if="processing">
                            Saving...
                        </span>

                        <span v-else>
                            Save Changes
                        </span>
                    </button>
                </div>

            </div>
        </div>
    </AppLayout>
</template>