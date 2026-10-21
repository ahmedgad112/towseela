<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';
import EmptyState from '../../Components/EmptyState.vue';
import UiButton from '../../Components/UiButton.vue';
import UiInput from '../../Components/UiInput.vue';

defineOptions({ layout: CustomerLayout });
defineProps({
    summary: { type: Object, required: true },
});

const coupon = useForm({ code: '' });
const update = (item, quantity) => useForm({ quantity }).patch(`/cart/items/${item.id}`, { preserveScroll: true });
</script>

<template>
    <div>
        <h2 class="text-2xl font-bold text-navy">السلة</h2>
        <p class="mt-1 text-sm text-muted">راجع طلبك قبل إتمام الدفع عند الاستلام.</p>
    </div>
    <EmptyState v-if="!summary.items?.length" title="سلتك فارغة" text="تصفح المتاجر وأضف منتجات لبدء الطلب.">
        <Link href="/" class="inline-flex min-h-11 items-center rounded-xl bg-gold px-4 font-semibold text-navy">العودة للرئيسية</Link>
    </EmptyState>
    <div v-else class="grid gap-4 lg:grid-cols-[1.4fr_.9fr]">
        <div class="space-y-3">
            <article v-for="item in summary.items" :key="item.id" class="app-card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-navy">{{ item.name }}</h3>
                        <p class="mt-1 text-xs text-muted">{{ item.options?.join(' · ') }}</p>
                    </div>
                    <p class="font-bold text-navy">{{ item.total }}</p>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <button class="grid size-9 place-items-center rounded-xl border border-line bg-canvas text-lg" @click="update(item, item.quantity - 1)">-</button>
                    <span class="min-w-8 text-center font-semibold">{{ item.quantity }}</span>
                    <button class="grid size-9 place-items-center rounded-xl border border-line bg-canvas text-lg" @click="update(item, item.quantity + 1)">+</button>
                </div>
            </article>
        </div>
        <aside class="space-y-3">
            <form class="app-card flex gap-2 p-3" @submit.prevent="coupon.post('/cart/coupon')">
                <UiInput v-model="coupon.code" placeholder="كود الخصم" class="flex-1" />
                <UiButton type="submit">تطبيق</UiButton>
            </form>
            <p v-if="summary.pricing_error" class="text-sm text-red-600">{{ summary.pricing_error }}</p>
            <div class="app-card space-y-2 p-4 text-sm">
                <p class="flex justify-between"><span class="text-muted">المجموع</span><span>{{ summary.subtotal }}</span></p>
                <p class="flex justify-between"><span class="text-muted">الخصم</span><span>{{ summary.discount }}</span></p>
                <p class="flex justify-between"><span class="text-muted">التوصيل</span><span>{{ summary.delivery_fee ?? 'يُحسب عند الدفع' }}</span></p>
                <p class="mt-2 flex justify-between border-t border-line pt-3 text-base font-bold text-navy">
                    <span>الإجمالي</span><span>{{ summary.grand_total }}</span>
                </p>
            </div>
            <Link href="/checkout" class="flex min-h-12 items-center justify-center rounded-2xl bg-gold font-bold text-navy">إتمام الطلب</Link>
        </aside>
    </div>
</template>
