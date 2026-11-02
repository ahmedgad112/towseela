<script setup>
defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    text: { type: String, default: '' },
});
</script>

<template>
    <header class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p
                v-if="eyebrow"
                class="mb-1.5 text-[11px] font-bold tracking-[0.14em] text-gold"
            >
                {{ eyebrow }}
            </p>
            <h2 class="customer-page-title">{{ title }}</h2>
            <p v-if="text" class="customer-page-text">{{ text }}</p>
        </div>
        <div v-if="$slots.default" class="shrink-0 pt-1">
            <slot />
        </div>
    </header>
</template>
