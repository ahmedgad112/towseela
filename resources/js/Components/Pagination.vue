<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: { type: Array, default: () => [] },
});
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap justify-center gap-1">
        <Link
            v-for="(link, index) in links"
            :key="index"
            :href="link.url || '#'"
            :class="link.active ? 'bg-navy text-white shadow-[0_8px_16px_rgba(12,24,48,0.18)]' : 'border border-line bg-white/90 text-muted hover:border-gold'"
            class="inline-flex min-h-9 items-center rounded-2xl px-3 text-sm"
            preserve-scroll
            v-html="link.label"
        />
    </nav>
</template>
