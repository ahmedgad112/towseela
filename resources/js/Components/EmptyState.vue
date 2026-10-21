<script setup>
defineProps({
    title: { type: String, required: true },
    text: { type: String, default: '' },
});
</script>

<template>
    <div class="rounded-3xl border border-dashed border-line bg-white px-6 py-14 text-center">
        <span class="mx-auto mb-4 grid size-12 place-items-center rounded-2xl bg-navy/5 text-navy">
            <slot name="icon">○</slot>
        </span>
        <h3 class="text-lg font-bold text-navy">{{ title }}</h3>
        <p v-if="text" class="mx-auto mt-2 max-w-sm text-sm leading-6 text-muted">{{ text }}</p>
        <div class="mt-4"><slot /></div>
    </div>
</template>
