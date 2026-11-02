<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    business: { type: Object, required: true },
});

const page = usePage();
const currency = computed(() => page.props.platform?.operations?.currency_label || 'ج.م');
const icon = computed(() => props.business.category?.icon || 'utensils');
</script>

<template>
    <Link
        :href="`/businesses/${business.slug}`"
        prefetch
        class="group relative flex flex-col overflow-hidden rounded-[1.25rem] bg-navy text-white transition duration-300 hover:-translate-y-1 sm:rounded-[1.5rem]"
        style="box-shadow: var(--shadow-card)"
    >
        <div class="relative aspect-[16/10] overflow-hidden sm:aspect-[16/9]">
            <img
                v-if="business.cover_url"
                :src="business.cover_url"
                alt=""
                width="640"
                height="360"
                loading="lazy"
                decoding="async"
                class="absolute inset-0 size-full object-cover transition duration-700 group-hover:scale-105"
            >
            <div
                v-else
                class="absolute inset-0 bg-linear-to-br from-navy via-navy-secondary to-[#2a4060]"
            >
                <div
                    class="absolute inset-0 opacity-30"
                    style="background-image: radial-gradient(circle at 20% 20%, #d6b568 0, transparent 32%), radial-gradient(circle at 90% 70%, #ffffff 0, transparent 26%)"
                />
            </div>
            <div class="absolute inset-0 bg-linear-to-t from-navy via-navy/40 to-transparent" />
            <span class="absolute top-2.5 start-2.5 rounded-full bg-gold px-2 py-0.5 text-[10px] font-bold text-navy sm:top-3 sm:start-3 sm:px-2.5 sm:py-1 sm:text-[11px]">
                {{ business.category?.name_ar }}
            </span>
            <span class="absolute top-2.5 end-2.5 grid size-8 place-items-center rounded-xl bg-white/15 text-gold backdrop-blur-md sm:top-3 sm:end-3 sm:size-9">
                <AppIcon :name="icon" size="sm" />
            </span>
        </div>

        <div class="flex flex-1 flex-col gap-2 px-3.5 pt-3 pb-3.5 sm:gap-2.5 sm:px-4 sm:pt-3.5 sm:pb-4">
            <h3 class="text-[0.98rem] font-extrabold tracking-tight sm:text-[1.05rem]">
                {{ business.name_ar }}
            </h3>
            <p class="line-clamp-2 flex-1 text-[12px] leading-5 text-white/65 sm:text-[13px] sm:leading-6">
                {{ business.description }}
            </p>
            <div class="flex items-center justify-between gap-2 border-t border-white/10 pt-2.5 text-[11px] font-semibold text-white/75 sm:gap-3 sm:pt-3 sm:text-[12px]">
                <span class="inline-flex items-center gap-1.5">
                    <AppIcon name="clock" size="xs" />
                    {{ business.delivery_time_min }}–{{ business.delivery_time_max }} د
                </span>
                <span class="text-gold">
                    من {{ Number(business.min_order_amount).toFixed(0) }} {{ currency }}
                </span>
            </div>
        </div>
    </Link>
</template>
