<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';
import EmptyState from '../../Components/EmptyState.vue';
import UiButton from '../../Components/UiButton.vue';
import UiInput from '../../Components/UiInput.vue';
import AppIcon from '../../Components/AppIcon.vue';
import CustomerPageHeader from '../../Components/CustomerPageHeader.vue';
import { updateCartItemQuantity } from '../../cartApi';
import { canDo } from '../../workspace';

defineOptions({ layout: CustomerLayout });

defineProps({
    summary: { type: Object, required: true },
});

const page = usePage();
const couponsEnabled = computed(() => canDo(page.props.auth, 'customer.coupons'));
const checkoutEnabled = computed(() => canDo(page.props.auth, 'customer.checkout'));
const coupon = useForm({ code: '' });
const busyItemId = ref(null);

const update = async (item, quantity) => {
    if (busyItemId.value) {
        return;
    }

    busyItemId.value = item.id;

    try {
        const snapshot = await updateCartItemQuantity(item.id, quantity);
        page.props.cart = snapshot;
        router.reload({ only: ['summary'], preserveScroll: true });
    } finally {
        busyItemId.value = null;
    }
};
</script>

<template>
    <CustomerPageHeader
        eyebrow="طلبك"
        title="السلة"
        text="راجع الطلب قبل إتمام الدفع عند الاستلام."
    />

    <EmptyState v-if="!summary.items?.length" title="سلتك فارغة" text="تصفح المتاجر وأضف منتجات لبدء الطلب.">
        <template #icon>
            <AppIcon name="cart" size="md" />
        </template>
        <Link href="/" class="customer-cta" prefetch>
            العودة للرئيسية
        </Link>
    </EmptyState>

    <div v-else class="grid gap-5 lg:grid-cols-[1.4fr_.9fr]">
        <div class="flex flex-col gap-3">
            <article
                v-for="item in summary.items"
                :key="item.id"
                class="customer-surface p-4 sm:p-5"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 space-y-1">
                        <h3 class="font-extrabold tracking-tight text-navy">{{ item.name }}</h3>
                        <p v-if="item.options?.length" class="text-xs leading-5 text-muted">{{ item.options?.join(' · ') }}</p>
                    </div>
                    <p class="shrink-0 text-base font-extrabold text-navy">{{ item.total }}</p>
                </div>
                <div class="mt-4 inline-flex items-center gap-1 rounded-full bg-canvas p-1 ring-1 ring-line">
                    <button
                        class="grid size-9 place-items-center rounded-full text-base font-bold text-navy transition hover:bg-white disabled:opacity-50"
                        :disabled="busyItemId === item.id"
                        @click="update(item, item.quantity - 1)"
                    >
                        −
                    </button>
                    <span class="min-w-8 text-center text-sm font-bold">{{ item.quantity }}</span>
                    <button
                        class="grid size-9 place-items-center rounded-full bg-gold text-navy transition hover:bg-gold-hover disabled:opacity-50"
                        :disabled="busyItemId === item.id"
                        @click="update(item, item.quantity + 1)"
                    >
                        +
                    </button>
                </div>
            </article>
        </div>

        <aside class="flex flex-col gap-3 lg:sticky lg:top-24 lg:self-start">
            <form
                v-if="couponsEnabled"
                class="customer-surface flex gap-2 p-3"
                @submit.prevent="coupon.post('/cart/coupon')"
            >
                <UiInput v-model="coupon.code" placeholder="كود الخصم" class="flex-1" />
                <UiButton type="submit">تطبيق</UiButton>
            </form>
            <p v-if="summary.pricing_error" class="text-sm text-red-600">{{ summary.pricing_error }}</p>
            <div class="customer-surface space-y-3 p-5 text-sm">
                <p class="text-[11px] font-bold tracking-[0.14em] text-gold">الملخص</p>
                <p class="flex justify-between gap-3"><span class="text-muted">المجموع</span><span class="font-semibold">{{ summary.subtotal }}</span></p>
                <p class="flex justify-between gap-3"><span class="text-muted">الخصم</span><span class="font-semibold">{{ summary.discount }}</span></p>
                <p class="flex justify-between gap-3"><span class="text-muted">التوصيل</span><span class="font-semibold">{{ summary.delivery_fee ?? 'يُحسب عند الدفع' }}</span></p>
                <p class="flex justify-between gap-3 border-t border-line pt-3 text-base font-extrabold text-navy">
                    <span>الإجمالي</span><span>{{ summary.grand_total }}</span>
                </p>
            </div>
            <Link
                v-if="checkoutEnabled"
                href="/checkout"
                class="customer-cta w-full"
            >
                إتمام الطلب
            </Link>
            <p v-else class="rounded-2xl border border-line bg-white/70 px-4 py-3 text-center text-sm text-muted">
                إتمام الطلب متوقف مؤقتاً.
            </p>
        </aside>
    </div>
</template>
