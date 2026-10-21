<script setup>
import { Link, usePage } from '@inertiajs/vue3';
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
</script>

<template>
    <section class="overflow-hidden rounded-3xl bg-navy px-5 py-6 text-white sm:px-8 sm:py-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-xl">
                <p class="text-xs font-semibold tracking-wide text-gold">توسيلا · توصيل متعدد الخدمات</p>
                <h2 class="mt-2 text-3xl font-bold leading-tight sm:text-4xl">اطلب من مطاعمك ومتاجرك المفضلة</h2>
                <p class="mt-3 text-sm leading-7 text-white/70">تجربة عربية أنيقة، تتبع واضح للطلب، ودفع عند الاستلام حتى يتم ربط بوابة الدفع.</p>
            </div>
            <Link
                href="/search"
                class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-gold px-5 text-sm font-bold text-navy"
            >
                <AppIcon name="search" />
                ابحث الآن
            </Link>
        </div>
    </section>

    <section class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-navy">التصنيفات</h3>
            <Link href="/search" class="text-sm text-muted">عرض الكل</Link>
        </div>
        <div class="no-scrollbar flex gap-3 overflow-x-auto pb-1">
            <Link
                v-for="category in categories"
                :key="category.id"
                :href="`/search?category=${category.id}`"
                class="app-card flex min-w-28 shrink-0 flex-col items-center gap-2 px-4 py-4 text-sm font-medium text-navy"
            >
                <span class="grid size-11 place-items-center rounded-2xl bg-navy text-gold">
                    <AppIcon :name="category.icon || 'utensils'" />
                </span>
                {{ category.name_ar }}
            </Link>
        </div>
    </section>

    <section class="space-y-3">
        <div>
            <h3 class="text-lg font-bold text-navy">المتاجر المتاحة</h3>
            <p class="text-sm text-muted">{{ page.props.auth.user ? `مرحباً ${page.props.auth.user.name}` : 'اختر متجراً وابدأ طلبك' }}</p>
        </div>
        <div v-if="businesses.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <BusinessCard v-for="business in businesses" :key="business.id" :business="business" />
        </div>
        <EmptyState v-else title="لا توجد متاجر متاحة" text="أضف تصنيفات ومتاجر من لوحة الإدارة." />
    </section>
</template>
