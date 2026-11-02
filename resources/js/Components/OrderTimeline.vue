<script setup>
defineProps({
    histories: { type: Array, default: () => [] },
});
</script>

<template>
    <ol class="relative space-y-3.5 border-s-2 border-gold/40 ps-5">
        <li v-for="item in histories" :key="item.id" class="relative">
            <span class="absolute -start-[1.45rem] top-1.5 size-3 rounded-full border-2 border-white bg-gold" />
            <div class="customer-surface space-y-1 p-3.5">
                <p class="text-sm font-bold text-navy">{{ item.to_status?.label || item.to_status }}</p>
                <p class="text-xs leading-5 text-muted">{{ item.created_at }} · {{ item.actor?.name || 'النظام' }}</p>
                <p v-if="item.reason" class="pt-0.5 text-sm leading-6 text-ink">{{ item.reason }}</p>
            </div>
        </li>
    </ol>
</template>
