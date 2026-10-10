<script setup lang="ts">
interface Props {
    logo?: string | null
    name: string
    size?: 'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<Props>(), {
    logo: null,
    size: 'md',
})

const sizes = {
    sm: 'h-8 w-8',
    md: 'h-10 w-10',
    lg: 'h-16 w-16',
}

const textSizes = {
    sm: 'text-xs',
    md: 'text-sm',
    lg: 'text-xl',
}
</script>

<template>
    <div
        :class="[
            sizes[size],
            'flex shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-white'
        ]"
    >
        <img
            v-if="logo"
            :src="logo"
            :alt="`${name} logo`"
            class="h-full w-full object-contain p-1"
        />

        <span
            v-else
            :class="[
                textSizes[size],
                'font-bold text-gray-500'
            ]"
        >
            {{ name.charAt(0).toUpperCase() }}
        </span>
    </div>
</template>