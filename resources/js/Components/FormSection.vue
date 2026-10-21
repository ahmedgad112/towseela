<script setup>
defineProps({
    title: { type: String, required: true },
    text: { type: String, default: '' },
});
</script>

<template>
    <section class="app-card space-y-4 p-5">
        <div>
            <h3 class="text-base font-bold text-navy">{{ title }}</h3>
            <p v-if="text" class="mt-1 text-sm leading-6 text-muted">{{ text }}</p>
        </div>
        <div class="space-y-4">
            <slot />
        </div>
    </section>
</template>
