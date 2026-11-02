<script setup>
import AppIcon from './AppIcon.vue';

defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    hint: { type: String, default: '' },
    icon: { type: String, default: '' },
});
</script>

<template>
    <article class="app-card relative overflow-hidden p-4 sm:p-5">
        <span class="absolute inset-x-0 top-0 h-1 bg-gold" />
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="dashboard-metric-label">{{ label }}</p>
                <p class="dashboard-metric-value truncate">{{ value }}</p>
                <p v-if="hint" class="mt-1 text-[11px] leading-5 text-muted sm:text-xs">{{ hint }}</p>
            </div>
            <span
                v-if="icon"
                class="grid size-9 shrink-0 place-items-center rounded-xl bg-navy/5 text-navy sm:size-10 sm:rounded-2xl"
            >
                <AppIcon :name="icon" size="sm" />
            </span>
        </div>
    </article>
</template>
