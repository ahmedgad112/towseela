<script setup>
defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    text: { type: String, default: '' },
});
</script>

<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p v-if="eyebrow" class="text-xs font-semibold tracking-wide text-gold">{{ eyebrow }}</p>
            <h2 class="mt-1 text-2xl font-bold text-navy">{{ title }}</h2>
            <p v-if="text" class="mt-1 max-w-2xl text-sm leading-6 text-muted">{{ text }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <slot />
        </div>
    </div>
</template>
