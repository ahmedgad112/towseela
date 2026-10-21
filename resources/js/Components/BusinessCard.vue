<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    business: { type: Object, required: true },
});

const icon = computed(() => props.business.category?.icon || 'utensils');
const tone = computed(() => ({
    restaurants: 'from-[#101D35] to-[#243653]',
    cafes: 'from-[#243653] to-[#3a4d6b]',
    supermarkets: 'from-[#172033] to-[#2d3f5a]',
    pharmacies: 'from-[#1a2744] to-[#31415c]',
}[props.business.category?.slug] || 'from-navy to-navy-secondary'));
</script>

<template>
    <Link
        :href="`/businesses/${business.slug}`"
        class="group overflow-hidden rounded-3xl border border-line bg-white transition hover:-translate-y-0.5"
        style="box-shadow: var(--shadow-card)"
    >
        <div class="relative flex h-32 items-end overflow-hidden bg-linear-to-br p-4" :class="tone">
            <img v-if="business.cover_url" :src="business.cover_url" alt="" class="absolute inset-0 size-full object-cover">
            <div v-else class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, #d6b568 0, transparent 28%), radial-gradient(circle at 90% 70%, #ffffff 0, transparent 24%)" />
            <div v-if="business.cover_url" class="absolute inset-0 bg-navy/35" />
            <span class="relative rounded-full bg-gold px-2.5 py-1 text-xs font-semibold text-navy">
                {{ business.category?.name_ar }}
            </span>
        </div>
        <div class="relative space-y-2 px-4 pb-4 pt-5">
            <span class="absolute -top-5 end-4 grid size-10 place-items-center rounded-2xl border border-line bg-white text-navy">
                <AppIcon :name="icon" />
            </span>
            <h3 class="text-lg font-bold text-navy group-hover:text-navy-secondary">{{ business.name_ar }}</h3>
            <p class="line-clamp-2 text-sm leading-6 text-muted">{{ business.description }}</p>
            <div class="flex items-center justify-between text-xs text-muted">
                <span class="inline-flex items-center gap-1">
                    <AppIcon name="clock" class="size-3.5" />
                    {{ business.delivery_time_min }}-{{ business.delivery_time_max }} د
                </span>
                <span>الحد الأدنى {{ Number(business.min_order_amount).toFixed(0) }} ج.م</span>
            </div>
        </div>
    </Link>
</template>
