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
    <article class="app-card relative overflow-hidden p-5">
        <span class="absolute inset-x-0 top-0 h-1 bg-gold" />
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm text-muted">{{ label }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-navy">{{ value }}</p>
                <p v-if="hint" class="mt-1 text-xs leading-5 text-muted">{{ hint }}</p>
            </div>
            <span v-if="icon" class="grid size-10 place-items-center rounded-2xl bg-navy/5 text-navy">
                <AppIcon :name="icon" />
            </span>
        </div>
    </article>
</template>
