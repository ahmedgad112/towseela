<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';
import BusinessCard from '../../Components/BusinessCard.vue';
import EmptyState from '../../Components/EmptyState.vue';
import AppIcon from '../../Components/AppIcon.vue';

defineOptions({ layout: CustomerLayout });
defineProps({
    categories: { type: Array, default: () => [] },
    businesses: { type: Array, default: () => [] },
});

const page = usePage();
const coverSrc = '/image/cover.webp';
const brand = computed(() => page.props.platform?.brand || {});
const brandName = computed(() => brand.value.name_ar || page.props.appName || 'توسيلا');
const greeting = computed(() => (
    page.props.auth.user
        ? `مرحباً ${page.props.auth.user.name}`
        : 'اختر متجراً وابدأ طلبك'
));
</script>

<template>
    <section class="customer-hero customer-hero--home -mx-3 -mt-4 sm:-mx-5 sm:-mt-6 sm:aspect-[16/10] sm:max-h-[26rem] lg:-mx-6 lg:-mt-8 lg:aspect-[21/9] lg:max-h-[22rem] xl:max-h-[24rem]">
        <div class="customer-hero-media hidden sm:block">
            <img
                :src="coverSrc"
                alt=""
                width="1600"
                height="900"
                fetchpriority="high"
                decoding="async"
            >
        </div>
        <div class="customer-hero-veil hidden sm:block" />
        <div class="customer-hero-copy relative mx-auto flex max-w-6xl flex-col justify-end gap-3 px-4 py-6 sm:absolute sm:inset-0 sm:gap-4 sm:px-6 sm:py-0 sm:pb-7 lg:px-8 lg:pb-8">
            <div class="max-w-lg space-y-2 sm:space-y-3">
                <p class="text-[1.85rem] font-extrabold leading-none tracking-tight text-white drop-shadow-[0_4px_24px_rgba(0,0,0,0.45)] sm:text-4xl lg:text-5xl">
                    {{ brandName }}
                </p>
                <h2 class="max-w-md text-base font-bold leading-snug text-white/95 sm:text-lg lg:text-xl">
                    اطلب من مطاعمك ومتاجرك المفضلة
                </h2>
                <p class="max-w-sm text-sm leading-7 text-white/75">
                    {{ brand.tagline || 'توصيل احترافي بتتبع واضح ودفع عند الاستلام.' }}
                </p>
            </div>
            <div>
                <Link href="/search" class="customer-cta min-h-11 px-5 text-[13px] sm:min-h-12 sm:px-6 sm:text-sm">
                    <AppIcon name="search" size="sm" />
                    ابدأ الطلب
                </Link>
            </div>
        </div>
    </section>

    <section class="customer-section">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h3 class="customer-section-title">التصنيفات</h3>
                <p class="customer-section-text">تصفّح حسب نوع المتجر</p>
            </div>
            <Link href="/search" class="text-[13px] font-bold text-navy/70 transition hover:text-navy">
                الكل
            </Link>
        </div>
        <div class="no-scrollbar -mx-1 flex gap-2.5 overflow-x-auto px-1 pb-1 sm:gap-3 md:flex-wrap md:overflow-visible">
            <Link
                v-for="category in categories"
                :key="category.id"
                :href="`/search?category=${category.id}`"
                class="group flex min-w-[4.75rem] shrink-0 flex-col items-center gap-2 sm:min-w-[5.75rem] sm:gap-2.5"
            >
                <span class="grid size-12 place-items-center rounded-[1.1rem] bg-navy text-gold shadow-[var(--shadow-soft)] transition duration-300 group-hover:-translate-y-0.5 group-hover:bg-navy-secondary sm:size-14 sm:rounded-[1.25rem]">
                    <AppIcon :name="category.icon || 'utensils'" size="md" />
                </span>
                <span class="max-w-[4.75rem] text-center text-[12px] font-bold leading-snug text-navy sm:max-w-[5.75rem] sm:text-[13px]">
                    {{ category.name_ar }}
                </span>
            </Link>
        </div>
    </section>

    <section class="customer-section">
        <div class="space-y-1">
            <h3 class="customer-section-title">المتاجر القريبة</h3>
            <p class="customer-section-text">{{ greeting }}</p>
        </div>
        <div v-if="businesses.length" class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3">
            <BusinessCard v-for="business in businesses" :key="business.id" :business="business" />
        </div>
        <EmptyState v-else title="لا توجد متاجر متاحة" text="أضف تصنيفات ومتاجر من لوحة الإدارة.">
            <template #icon>
                <AppIcon name="store" size="md" />
            </template>
        </EmptyState>
    </section>
</template>
