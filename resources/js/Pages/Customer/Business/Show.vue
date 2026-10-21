<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import CustomerLayout from '../../../Layouts/CustomerLayout.vue';
import UiButton from '../../../Components/UiButton.vue';
import AppIcon from '../../../Components/AppIcon.vue';

defineOptions({ layout: CustomerLayout });
defineProps({
    business: { type: Object, required: true },
    isOpen: { type: Boolean, default: true },
});

const selected = ref(null);
const form = useForm({
    product_id: null,
    quantity: 1,
    options: [],
    notes: '',
});

const open = (product) => {
    selected.value = product;
    form.product_id = product.id;
    form.quantity = 1;
    form.options = [];
    form.notes = '';
};

const add = () => form.post('/cart', { preserveScroll: true, onSuccess: () => { selected.value = null; } });
</script>

<template>
    <section class="overflow-hidden rounded-3xl bg-navy text-white">
        <div v-if="business.cover_url" class="h-44">
            <img :src="business.cover_url" alt="" class="size-full object-cover">
        </div>
        <div class="bg-linear-to-br from-navy via-navy-secondary to-navy px-5 py-6 sm:px-7">
            <p class="text-xs font-semibold tracking-wide text-gold">{{ business.category?.name_ar }}</p>
            <h2 class="mt-1 text-3xl font-bold">{{ business.name_ar }}</h2>
            <p class="mt-2 max-w-2xl text-sm leading-7 text-white/75">{{ business.description }}</p>
            <div class="mt-4 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full bg-white/10 px-3 py-1">{{ isOpen ? 'يستقبل الطلبات الآن' : 'خارج ساعات الذروة' }}</span>
                <span class="rounded-full bg-white/10 px-3 py-1">{{ business.delivery_time_min }}-{{ business.delivery_time_max }} دقيقة</span>
                <span class="rounded-full bg-gold px-3 py-1 font-semibold text-navy">حد أدنى {{ Number(business.min_order_amount).toFixed(0) }} ج.م</span>
            </div>
        </div>
    </section>

    <section v-for="category in business.product_categories" :key="category.id" class="space-y-3">
        <h3 class="text-lg font-bold text-navy">{{ category.name_ar }}</h3>
        <button
            v-for="product in category.products"
            :key="product.id"
            class="app-card flex w-full items-center gap-4 p-4 text-right transition hover:border-gold/60"
            @click="open(product)"
        >
            <img v-if="product.image_url" :src="product.image_url" alt="" class="size-14 shrink-0 rounded-2xl object-cover">
            <span v-else class="grid size-14 shrink-0 place-items-center rounded-2xl bg-navy text-lg font-bold text-gold">
                {{ product.name_ar?.charAt(0) }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-navy">{{ product.name_ar }}</p>
                <p class="mt-1 line-clamp-2 text-sm leading-6 text-muted">{{ product.description }}</p>
            </div>
            <div class="text-left">
                <p class="font-bold text-navy">{{ Number(product.price).toFixed(0) }} <span class="text-xs text-muted">ج.م</span></p>
                <span class="mt-2 inline-flex size-8 items-center justify-center rounded-full bg-gold text-navy">
                    <AppIcon name="plus" />
                </span>
            </div>
        </button>
    </section>

    <div v-if="selected" class="fixed inset-0 z-40 flex items-end bg-navy/70 p-4 backdrop-blur-sm" @click.self="selected = null">
        <div class="mx-auto w-full max-w-xl space-y-4 rounded-3xl bg-white p-5 shadow-2xl">
            <img v-if="selected.image_url" :src="selected.image_url" alt="" class="h-40 w-full rounded-2xl object-cover">
            <div>
                <p class="text-xs font-semibold text-gold">إضافة للسلة</p>
                <h3 class="text-xl font-bold text-navy">{{ selected.name_ar }}</h3>
                <p class="mt-1 text-sm text-muted">{{ selected.description }}</p>
            </div>
            <label class="block text-sm font-semibold text-navy">الكمية
                <input v-model.number="form.quantity" type="number" min="1" max="20" class="mt-1 min-h-12 w-full rounded-2xl border border-line px-4">
            </label>
            <div v-for="option in selected.options" :key="option.id" class="space-y-2">
                <p class="text-sm font-semibold text-navy">{{ option.name_ar }}</p>
                <label v-for="value in option.values" :key="value.id" class="flex items-center justify-between rounded-2xl border border-line px-3 py-2.5 text-sm">
                    <span class="flex items-center gap-2">
                        <input v-model="form.options" type="checkbox" :value="value.id" class="accent-gold">
                        {{ value.name_ar }}
                    </span>
                    <span v-if="Number(value.price_delta)" class="text-muted">+{{ Number(value.price_delta).toFixed(0) }} ج.م</span>
                </label>
            </div>
            <textarea v-model="form.notes" class="min-h-24 w-full rounded-2xl border border-line p-3 text-sm" placeholder="ملاحظات للمطبخ أو المتجر"></textarea>
            <div class="flex gap-2">
                <UiButton class="flex-1" :disabled="form.processing" @click="add">أضف للسلة</UiButton>
                <UiButton variant="outline" @click="selected = null">إغلاق</UiButton>
            </div>
        </div>
    </div>
</template>
