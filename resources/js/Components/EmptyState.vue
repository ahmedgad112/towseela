<script setup>
defineProps({
    title: { type: String, required: true },
    text: { type: String, default: '' },
});
</script>

<template>
    <div class="relative overflow-hidden rounded-[1.5rem] border border-dashed border-line/90 bg-white/70 px-5 py-14 text-center backdrop-blur-sm sm:px-6 sm:py-16">
        <div
            class="pointer-events-none absolute inset-0 opacity-60"
            style="background-image: radial-gradient(circle at 50% 0%, rgba(214,181,104,0.14), transparent 42%)"
        />
        <span class="relative mx-auto mb-4 grid size-14 place-items-center rounded-[1.15rem] bg-navy text-gold shadow-[var(--shadow-soft)]">
            <slot name="icon">○</slot>
        </span>
        <h3 class="relative text-lg font-extrabold tracking-tight text-navy">{{ title }}</h3>
        <p v-if="text" class="relative mx-auto mt-2 max-w-sm text-sm leading-7 text-muted">{{ text }}</p>
        <div v-if="$slots.default" class="relative mt-6">
            <slot />
        </div>
    </div>
</template>
