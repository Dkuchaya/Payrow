<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: AppLayout,
})

interface Day {
    day_of_week: number
    name: string
    is_working_day: boolean
    start_time: string
    end_time: string
    break_minutes: number
    grace_period_minutes: string
    early_departure_grace_minutes: string
    allow_overtime: boolean | null
}

const days = reactive<Day[]>([
    {
        day_of_week: 1,
        name: 'Monday',
        is_working_day: true,
        start_time: '08:00',
        end_time: '17:00',
        break_minutes: 60,
        grace_period_minutes: '',
        early_departure_grace_minutes: '',
        allow_overtime: null,
    },
    {
        day_of_week: 2,
        name: 'Tuesday',
        is_working_day: true,
        start_time: '08:00',
        end_time: '17:00',
        break_minutes: 60,
        grace_period_minutes: '',
        early_departure_grace_minutes: '',
        allow_overtime: null,
    },
    {
        day_of_week: 3,
        name: 'Wednesday',
        is_working_day: true,
        start_time: '08:00',
        end_time: '17:00',
        break_minutes: 60,
        grace_period_minutes: '',
        early_departure_grace_minutes: '',
        allow_overtime: null,
    },
    {
        day_of_week: 4,
        name: 'Thursday',
        is_working_day: true,
        start_time: '08:00',
        end_time: '17:00',
        break_minutes: 60,
        grace_period_minutes: '',
        early_departure_grace_minutes: '',
        allow_overtime: null,
    },
    {
        day_of_week: 5,
        name: 'Friday',
        is_working_day: true,
        start_time: '08:00',
        end_time: '17:00',
        break_minutes: 60,
        grace_period_minutes: '',
        early_departure_grace_minutes: '',
        allow_overtime: null,
    },
    {
        day_of_week: 6,
        name: 'Saturday',
        is_working_day: false,
        start_time: '08:00',
        end_time: '13:00',
        break_minutes: 0,
        grace_period_minutes: '',
        early_departure_grace_minutes: '',
        allow_overtime: null,
    },
    {
        day_of_week: 7,
        name: 'Sunday',
        is_working_day: false,
        start_time: '08:00',
        end_time: '13:00',
        break_minutes: 0,
        grace_period_minutes: '',
        early_departure_grace_minutes: '',
        allow_overtime: null,
    },
])

const form = reactive({
    name: '',
    code: '',
    description: '',
    default_start_time: '08:00',
    default_end_time: '17:00',
    default_break_minutes: 60,
    grace_period_minutes: 15,
    early_departure_grace_minutes: 0,
    overtime_after_minutes: 0,
    allow_overtime: true,
    is_active: true,
    days,
})

const processing = ref(false)
const errors = ref<Record<string, string>>({})

const workingDaysCount = computed(() =>
    days.filter(day => day.is_working_day).length
)

const submit = () => {
    processing.value = true
    errors.value = {}

    router.post(
        route('work-schedules.store'),
        form,
        {
            preserveScroll: true,

            onSuccess: () => {
                processing.value = false
            },

            onError: (validationErrors) => {
                errors.value = validationErrors as Record<string, string>
                processing.value = false
            },

            onFinish: () => {
                processing.value = false
            },
        }
    )
}

const copyMondayToWeekdays = () => {
    const monday = days[0]

    days.slice(1, 5).forEach(day => {
        day.is_working_day = monday.is_working_day
        day.start_time = monday.start_time
        day.end_time = monday.end_time
        day.break_minutes = monday.break_minutes
        day.grace_period_minutes = monday.grace_period_minutes
        day.early_departure_grace_minutes =
            monday.early_departure_grace_minutes
        day.allow_overtime = monday.allow_overtime
    })
}

const setWeekendOff = () => {
    days[5].is_working_day = false
    days[6].is_working_day = false
}
</script>

<template>
    <div class="p-6 max-w-7xl mx-auto">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    Create Work Schedule
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Configure working hours and attendance rules.
                </p>
            </div>

            <Link
                :href="route('work-schedules.index')"
                class="px-4 py-2 border rounded-lg hover:bg-gray-50"
            >
                Back
            </Link>
        </div>

        <form @submit.prevent="submit">

            <!-- Basic information -->
            <div class="bg-white border rounded-xl p-6 mb-6">

                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Schedule Information
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Schedule Name
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Standard Office Hours"
                            class="w-full rounded-lg border-gray-300"
                        />

                        <p
                            v-if="errors.name"
                            class="text-sm text-red-600 mt-1"
                        >
                            {{ errors.name }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Code
                        </label>

                        <input
                            v-model="form.code"
                            type="text"
                            placeholder="STD-0800"
                            class="w-full rounded-lg border-gray-300"
                        />

                        <p
                            v-if="errors.code"
                            class="text-sm text-red-600 mt-1"
                        >
                            {{ errors.code }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">
                            Description
                        </label>

                        <textarea
                            v-model="form.description"
                            rows="3"
                            placeholder="Standard Monday to Friday office schedule"
                            class="w-full rounded-lg border-gray-300"
                        />
                    </div>

                </div>
            </div>

            <!-- Default rules -->
            <div class="bg-white border rounded-xl p-6 mb-6">

                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold">
                            Default Attendance Rules
                        </h2>

                        <p class="text-sm text-gray-500">
                            These values are used unless a specific day overrides them.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-5">

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Start Time
                        </label>

                        <input
                            v-model="form.default_start_time"
                            type="time"
                            class="w-full rounded-lg border-gray-300"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            End Time
                        </label>

                        <input
                            v-model="form.default_end_time"
                            type="time"
                            class="w-full rounded-lg border-gray-300"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Break Minutes
                        </label>

                        <input
                            v-model.number="form.default_break_minutes"
                            type="number"
                            min="0"
                            class="w-full rounded-lg border-gray-300"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Grace Period
                        </label>

                        <div class="relative">
                            <input
                                v-model.number="form.grace_period_minutes"
                                type="number"
                                min="0"
                                class="w-full rounded-lg border-gray-300 pr-16"
                            />

                            <span
                                class="absolute right-3 top-2.5 text-sm text-gray-400"
                            >
                                minutes
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Early Departure Grace
                        </label>

                        <input
                            v-model.number="
                                form.early_departure_grace_minutes
                            "
                            type="number"
                            min="0"
                            class="w-full rounded-lg border-gray-300"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Overtime After
                        </label>

                        <input
                            v-model.number="form.overtime_after_minutes"
                            type="number"
                            min="0"
                            class="w-full rounded-lg border-gray-300"
                        />
                    </div>

                    <div class="flex items-center gap-3 mt-7">
                        <input
                            id="allow_overtime"
                            v-model="form.allow_overtime"
                            type="checkbox"
                            class="rounded border-gray-300"
                        />

                        <label
                            for="allow_overtime"
                            class="text-sm font-medium"
                        >
                            Allow overtime
                        </label>
                    </div>

                    <div class="flex items-center gap-3 mt-7">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-gray-300"
                        />

                        <label
                            for="is_active"
                            class="text-sm font-medium"
                        >
                            Active schedule
                        </label>
                    </div>

                </div>
            </div>

            <!-- Weekly schedule -->
            <div class="bg-white border rounded-xl overflow-hidden mb-6">

                <div class="p-6 border-b">
                    <div class="flex items-center justify-between">

                        <div>
                            <h2 class="text-lg font-semibold">
                                Weekly Schedule
                            </h2>

                            <p class="text-sm text-gray-500 mt-1">
                                {{ workingDaysCount }} working day(s) per week.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="px-3 py-2 text-sm border rounded-lg hover:bg-gray-50"
                                @click="copyMondayToWeekdays"
                            >
                                Copy Monday → Weekdays
                            </button>

                            <button
                                type="button"
                                class="px-3 py-2 text-sm border rounded-lg hover:bg-gray-50"
                                @click="setWeekendOff"
                            >
                                Set Weekend Off
                            </button>
                        </div>

                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Day
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Working
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Start
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    End
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Break
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Grace
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Early
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    OT
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">

                            <tr
                                v-for="day in days"
                                :key="day.day_of_week"
                                :class="{
                                    'bg-gray-50': !day.is_working_day
                                }"
                            >

                                <td class="px-5 py-4">
                                    <span class="font-medium">
                                        {{ day.name }}
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <input
                                        v-model="day.is_working_day"
                                        type="checkbox"
                                        class="rounded border-gray-300"
                                    />
                                </td>

                                <td class="px-5 py-4">
                                    <input
                                        v-model="day.start_time"
                                        type="time"
                                        :disabled="!day.is_working_day"
                                        class="rounded-lg border-gray-300"
                                    />
                                </td>

                                <td class="px-5 py-4">
                                    <input
                                        v-model="day.end_time"
                                        type="time"
                                        :disabled="!day.is_working_day"
                                        class="rounded-lg border-gray-300"
                                    />
                                </td>

                                <td class="px-5 py-4">
                                    <input
                                        v-model.number="day.break_minutes"
                                        type="number"
                                        min="0"
                                        :disabled="!day.is_working_day"
                                        class="w-24 rounded-lg border-gray-300"
                                    />
                                </td>

                                <td class="px-5 py-4">
                                    <input
                                        v-model.number="
                                            day.grace_period_minutes
                                        "
                                        type="number"
                                        min="0"
                                        placeholder="Default"
                                        :disabled="!day.is_working_day"
                                        class="w-24 rounded-lg border-gray-300"
                                    />
                                </td>

                                <td class="px-5 py-4">
                                    <input
                                        v-model.number="
                                            day.early_departure_grace_minutes
                                        "
                                        type="number"
                                        min="0"
                                        placeholder="Default"
                                        :disabled="!day.is_working_day"
                                        class="w-24 rounded-lg border-gray-300"
                                    />
                                </td>

                                <td class="px-5 py-4">
                                    <select
                                        v-model="day.allow_overtime"
                                        :disabled="!day.is_working_day"
                                        class="rounded-lg border-gray-300"
                                    >
                                        <option :value="null">
                                            Default
                                        </option>

                                        <option :value="true">
                                            Yes
                                        </option>

                                        <option :value="false">
                                            No
                                        </option>
                                    </select>
                                </td>

                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3">

                <Link
                    :href="route('work-schedules.index')"
                    class="px-5 py-2.5 border rounded-lg hover:bg-gray-50"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="processing"
                    class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ processing ? 'Creating...' : 'Create Schedule' }}
                </button>

            </div>

        </form>
    </div>
</template>