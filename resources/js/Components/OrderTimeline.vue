<script setup>
defineProps({
    histories: { type: Array, default: () => [] },
});
</script>

<template>
    <ol class="relative space-y-4 border-s-2 border-gold/40 ps-5">
        <li v-for="item in histories" :key="item.id" class="relative">
            <span class="absolute -start-[1.45rem] top-1.5 size-3 rounded-full border-2 border-white bg-gold" />
            <div class="app-card p-3">
                <p class="text-sm font-semibold text-navy">{{ item.to_status?.label || item.to_status }}</p>
                <p class="text-xs text-muted">{{ item.created_at }} · {{ item.actor?.name || 'النظام' }}</p>
                <p v-if="item.reason" class="mt-1 text-sm text-ink">{{ item.reason }}</p>
            </div>
        </li>
    </ol>
</template>
