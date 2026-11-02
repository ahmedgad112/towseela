<script setup>
import { useForm } from '@inertiajs/vue3';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';
import UiButton from '../../Components/UiButton.vue';
import UiTextarea from '../../Components/UiTextarea.vue';
import AppIcon from '../../Components/AppIcon.vue';
import CustomerPageHeader from '../../Components/CustomerPageHeader.vue';

defineOptions({ layout: CustomerLayout });
const props = defineProps({
    addresses: { type: Array, default: () => [] },
    summary: { type: Object, required: true },
    checkoutToken: { type: String, required: true },
});

const form = useForm({
    address_id: props.addresses.find((address) => address.is_default)?.id || props.addresses[0]?.id || '',
    checkout_token: props.checkoutToken,
    notes: '',
});
</script>

<template>
    <CustomerPageHeader
        eyebrow="الخطوة الأخيرة"
        title="إتمام الطلب"
        text="الدفع المتاح حالياً: عند الاستلام."
    />

    <form class="grid gap-5 lg:grid-cols-[1.3fr_.9fr]" @submit.prevent="form.post('/checkout')">
        <div class="flex flex-col gap-3">
            <div
                v-if="!addresses.length"
                class="rounded-2xl border border-red-100 bg-red-50 px-4 py-3.5 text-sm leading-6 text-red-700"
            >
                أضف عنوان توصيل أولاً من حسابك.
            </div>
            <p class="text-sm font-extrabold text-navy">عنوان التوصيل</p>
            <label
                v-for="address in addresses"
                :key="address.id"
                class="flex items-start gap-3 rounded-[1.35rem] border bg-white/90 p-4 transition"
                :class="form.address_id === address.id ? 'border-gold shadow-[var(--shadow-card)]' : 'border-line/80'"
            >
                <input v-model="form.address_id" type="radio" :value="address.id" class="mt-1 accent-gold">
                <div class="min-w-0 space-y-1">
                    <p class="font-extrabold tracking-tight text-navy">{{ address.label }}</p>
                    <p class="text-sm leading-6 text-muted">{{ address.line1 }} · {{ address.city }}</p>
                </div>
            </label>
            <UiTextarea
                v-model="form.notes"
                label="ملاحظات التوصيل"
                placeholder="تعليمات الوصول للمندوب"
            />
        </div>

        <aside class="customer-surface space-y-4 p-5 lg:sticky lg:top-24 lg:self-start">
            <div class="flex items-center gap-3 text-navy">
                <span class="grid size-12 place-items-center rounded-2xl bg-navy text-gold">
                    <AppIcon name="bag" size="md" />
                </span>
                <div class="min-w-0 space-y-0.5">
                    <p class="text-[11px] font-bold tracking-[0.14em] text-gold">ملخص الطلب</p>
                    <p class="text-xl font-extrabold tracking-tight">{{ summary.grand_total }}</p>
                </div>
            </div>
            <div v-if="summary.items?.length" class="space-y-2 border-t border-line pt-3">
                <div
                    v-for="item in summary.items"
                    :key="item.id"
                    class="flex items-start justify-between gap-3 text-sm"
                >
                    <span class="min-w-0 text-muted">{{ item.quantity }}× {{ item.name }}</span>
                    <span class="shrink-0 font-semibold text-navy">{{ item.total }}</span>
                </div>
            </div>
            <p v-if="summary.pricing_error" class="text-sm text-red-600">{{ summary.pricing_error }}</p>
            <UiButton type="submit" class="w-full" :disabled="form.processing || !addresses.length">
                تأكيد الطلب
            </UiButton>
        </aside>
    </form>
</template>
