<script setup>
defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    text: { type: String, default: '' },
});
</script>

<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between sm:gap-4">
        <div class="min-w-0">
            <p v-if="eyebrow" class="dashboard-eyebrow">{{ eyebrow }}</p>
            <h2 class="dashboard-heading" :class="eyebrow ? 'mt-1' : ''">{{ title }}</h2>
            <p v-if="text" class="dashboard-text max-w-2xl">{{ text }}</p>
        </div>
        <div v-if="$slots.default" class="flex flex-wrap items-center gap-2 sm:gap-2.5">
            <slot />
        </div>
    </div>
</template>
