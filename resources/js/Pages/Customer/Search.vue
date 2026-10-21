<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';
import BusinessCard from '../../Components/BusinessCard.vue';
import EmptyState from '../../Components/EmptyState.vue';
import AppIcon from '../../Components/AppIcon.vue';

defineOptions({ layout: CustomerLayout });
const props = defineProps({
    filters: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    businesses: { type: Object, required: true },
    products: { type: Array, default: () => [] },
});

const q = ref(props.filters.q || '');
const search = () => router.get('/search', { q: q.value, category: props.filters.category }, { preserveState: true });
</script>

<template>
    <form class="app-card flex items-center gap-2 p-2" @submit.prevent="search">
        <span class="ps-3 text-muted"><AppIcon name="search" /></span>
        <input v-model="q" class="min-h-11 flex-1 bg-transparent px-2 text-sm" placeholder="ابحث عن متجر أو منتج">
        <button class="rounded-xl bg-gold px-4 py-2.5 font-bold text-navy">بحث</button>
    </form>
    <div class="no-scrollbar flex gap-2 overflow-x-auto">
        <Link href="/search" class="rounded-full px-4 py-2 text-sm" :class="!filters.category ? 'bg-navy text-white' : 'bg-white text-muted'">الكل</Link>
        <Link
            v-for="category in categories"
            :key="category.id"
            :href="`/search?category=${category.id}`"
            class="rounded-full px-4 py-2 text-sm"
            :class="String(filters.category) === String(category.id) ? 'bg-navy text-white' : 'bg-white text-muted'"
        >
            {{ category.name_ar }}
        </Link>
    </div>
    <div v-if="businesses.data?.length" class="grid gap-4 sm:grid-cols-2">
        <BusinessCard v-for="business in businesses.data" :key="business.id" :business="business" />
    </div>
    <EmptyState v-else title="لا نتائج للمتاجر" text="جرّب كلمة أخرى أو صنفاً مختلفاً." />
    <section v-if="products.length" class="space-y-2">
        <h2 class="font-bold text-navy">منتجات مطابقة</h2>
        <Link
            v-for="product in products"
            :key="product.id"
            :href="`/businesses/${product.business?.slug}`"
            class="app-card flex items-center justify-between gap-3 p-4"
        >
            <img v-if="product.image_url" :src="product.image_url" alt="" class="size-14 rounded-2xl object-cover">
            <div class="min-w-0 flex-1">
                <p class="font-bold text-navy">{{ product.name_ar }}</p>
                <p class="text-xs text-muted">{{ product.business?.name_ar }}</p>
            </div>
            <p class="text-sm font-bold text-navy">{{ Number(product.price).toFixed(0) }} ج.م</p>
        </Link>
    </section>
</template>
