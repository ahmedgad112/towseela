<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';
import BusinessCard from '../../Components/BusinessCard.vue';
import EmptyState from '../../Components/EmptyState.vue';
import AppIcon from '../../Components/AppIcon.vue';
import CustomerPageHeader from '../../Components/CustomerPageHeader.vue';
import Pagination from '../../Components/Pagination.vue';

defineOptions({ layout: CustomerLayout });
const props = defineProps({
    filters: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    businesses: { type: Object, required: true },
    products: { type: Array, default: () => [] },
});

const page = usePage();
const currency = computed(() => page.props.platform?.operations?.currency_label || 'ج.م');
const q = ref(props.filters.q || '');
const search = () => router.get('/search', { q: q.value, category: props.filters.category }, { preserveState: true });
</script>

<template>
    <CustomerPageHeader
        eyebrow="اكتشف"
        title="البحث"
        text="ابحث عن متجر أو منتج، أو صفِّ حسب التصنيف."
    />

    <form
        class="customer-surface flex items-center gap-2 p-2 pe-2.5"
        @submit.prevent="search"
    >
        <span class="grid size-11 place-items-center text-muted">
            <AppIcon name="search" size="sm" />
        </span>
        <input
            v-model="q"
            class="min-h-11 flex-1 bg-transparent pe-2 text-sm outline-none placeholder:text-muted/70"
            placeholder="ابحث عن متجر أو منتج"
        >
        <button type="submit" class="customer-cta min-h-11 px-4 shadow-none">
            بحث
        </button>
    </form>

    <div class="no-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1">
        <Link
            href="/search"
            class="customer-chip"
            :class="!filters.category ? 'bg-navy text-white shadow-[0_8px_18px_rgba(12,24,48,0.18)]' : 'bg-white/90 text-muted ring-1 ring-line'"
        >
            الكل
        </Link>
        <Link
            v-for="category in categories"
            :key="category.id"
            :href="`/search?category=${category.id}`"
            class="customer-chip"
            :class="String(filters.category) === String(category.id)
                ? 'bg-navy text-white shadow-[0_8px_18px_rgba(12,24,48,0.18)]'
                : 'bg-white/90 text-muted ring-1 ring-line'"
        >
            {{ category.name_ar }}
        </Link>
    </div>

    <div v-if="businesses.data?.length" class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3">
        <BusinessCard v-for="business in businesses.data" :key="business.id" :business="business" />
    </div>
    <EmptyState v-else title="لا نتائج للمتاجر" text="جرّب كلمة أخرى أو صنفاً مختلفاً.">
        <template #icon>
            <AppIcon name="search" size="md" />
        </template>
    </EmptyState>

    <Pagination :links="businesses.links || []" />

    <section v-if="products.length" class="customer-section">
        <h2 class="customer-section-title">منتجات مطابقة</h2>
        <div class="flex flex-col gap-2.5">
            <Link
                v-for="product in products"
                :key="product.id"
                :href="`/businesses/${product.business?.slug}`"
                class="customer-surface flex items-center gap-3.5 p-3.5 transition hover:border-gold/50"
            >
                <img
                    v-if="product.image_url"
                    :src="product.image_url"
                    alt=""
                    class="size-14 shrink-0 rounded-2xl object-cover"
                >
                <span
                    v-else
                    class="grid size-14 shrink-0 place-items-center rounded-2xl bg-navy text-sm font-bold text-gold"
                >
                    {{ product.name_ar?.charAt(0) }}
                </span>
                <div class="min-w-0 flex-1 space-y-0.5">
                    <p class="truncate font-bold text-navy">{{ product.name_ar }}</p>
                    <p class="truncate text-xs text-muted">{{ product.business?.name_ar }}</p>
                </div>
                <p class="shrink-0 text-sm font-extrabold text-navy">
                    {{ Number(product.price).toFixed(0) }}
                    <span class="text-[11px] font-semibold text-muted">{{ currency }}</span>
                </p>
            </Link>
        </div>
    </section>
</template>
