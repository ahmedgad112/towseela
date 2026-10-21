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
            :class="link.active ? 'bg-navy text-white' : 'border border-line bg-white text-muted hover:border-gold'"
            class="inline-flex min-h-9 items-center rounded-xl px-3 text-sm"
            preserve-scroll
            v-html="link.label"
        />
    </nav>
</template>
