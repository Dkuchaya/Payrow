<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const visible = ref(false)
const message = ref('')
const type = ref<'success' | 'error' | 'warning' | 'info'>('success')

let timer: ReturnType<typeof setTimeout> | null = null

const flash = computed(() => {
    return page.props.flash as {
        success?: string
        error?: string
        warning?: string
        info?: string
    } | undefined
})

const showMessage = (
    newMessage: string,
    newType: 'success' | 'error' | 'warning' | 'info'
) => {
    if (timer) {
        clearTimeout(timer)
    }

    message.value = newMessage
    type.value = newType
    visible.value = true

    timer = setTimeout(() => {
        visible.value = false
    }, 4000)
}

watch(
    flash,
    (newFlash) => {
        if (!newFlash) {
            return
        }

        if (newFlash.success) {
            showMessage(newFlash.success, 'success')
        } else if (newFlash.error) {
            showMessage(newFlash.error, 'error')
        } else if (newFlash.warning) {
            showMessage(newFlash.warning, 'warning')
        } else if (newFlash.info) {
            showMessage(newFlash.info, 'info')
        }
    },
    {
        immediate: true,
        deep: true,
    }
)

const close = () => {
    visible.value = false

    if (timer) {
        clearTimeout(timer)
    }
}

const containerClass = computed(() => {
    switch (type.value) {
        case 'success':
            return 'border-green-200 bg-green-50 text-green-800'

        case 'error':
            return 'border-red-200 bg-red-50 text-red-800'

        case 'warning':
            return 'border-yellow-200 bg-yellow-50 text-yellow-800'

        case 'info':
            return 'border-blue-200 bg-blue-50 text-blue-800'

        default:
            return 'border-gray-200 bg-gray-50 text-gray-800'
    }
})

const iconClass = computed(() => {
    switch (type.value) {
        case 'success':
            return '✓'

        case 'error':
            return '×'

        case 'warning':
            return '!'

        case 'info':
            return 'i'

        default:
            return 'i'
    }
})
</script>

<template>
    <Transition
        enter-active-class="transform transition duration-300 ease-out"
        enter-from-class="translate-y-[-20px] opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transform transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-[-20px] opacity-0"
    >
        <div
            v-if="visible"
            class="fixed right-4 top-4 z-[9999] w-full max-w-md"
        >
            <div
                class="flex items-start gap-3 rounded-xl border px-4 py-4 shadow-lg"
                :class="containerClass"
            >
                <!-- Icon -->
                <div
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white/70 text-sm font-bold"
                >
                    {{ iconClass }}
                </div>

                <!-- Message -->
                <div class="min-w-0 flex-1 pt-0.5">
                    <p class="text-sm font-medium">
                        {{ message }}
                    </p>
                </div>

                <!-- Close -->
                <button
                    type="button"
                    class="shrink-0 rounded-lg p-1 text-current opacity-60 transition hover:bg-black/5 hover:opacity-100"
                    @click="close"
                >
                    ✕
                </button>
            </div>
        </div>
    </Transition>
</template>