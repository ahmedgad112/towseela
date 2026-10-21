<script setup>
import { useForm } from '@inertiajs/vue3';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';
import UiButton from '../../Components/UiButton.vue';
import AppIcon from '../../Components/AppIcon.vue';

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
    <div>
        <h2 class="text-2xl font-bold text-navy">إتمام الطلب</h2>
        <p class="mt-1 text-sm text-muted">الدفع المتاح حالياً: عند الاستلام. لا توجد بوابة إلكترونية مفعّلة بعد.</p>
    </div>
    <form class="grid gap-4 lg:grid-cols-[1.3fr_.9fr]" @submit.prevent="form.post('/checkout')">
        <div class="space-y-3">
            <div v-if="!addresses.length" class="rounded-2xl border border-red-100 bg-red-50 p-4 text-sm text-red-700">أضف عنوان توصيل أولاً من حسابك.</div>
            <label
                v-for="address in addresses"
                :key="address.id"
                class="flex items-start gap-3 rounded-3xl border bg-white p-4 transition"
                :class="form.address_id === address.id ? 'border-gold shadow-[var(--shadow-card)]' : 'border-line'"
            >
                <input v-model="form.address_id" type="radio" :value="address.id" class="mt-1 accent-gold">
                <div>
                    <p class="font-bold text-navy">{{ address.label }}</p>
                    <p class="mt-1 text-sm leading-6 text-muted">{{ address.line1 }} · {{ address.city }}</p>
                </div>
            </label>
            <textarea v-model="form.notes" class="min-h-24 w-full rounded-2xl border border-line bg-white p-3" placeholder="ملاحظات التوصيل"></textarea>
        </div>
        <aside class="app-card space-y-4 p-5">
            <div class="flex items-center gap-2 text-navy">
                <span class="grid size-10 place-items-center rounded-2xl bg-gold"><AppIcon name="bag" /></span>
                <div>
                    <p class="text-sm text-muted">ملخص الطلب</p>
                    <p class="text-xl font-bold">{{ summary.grand_total }}</p>
                </div>
            </div>
            <p v-if="summary.pricing_error" class="text-sm text-red-600">{{ summary.pricing_error }}</p>
            <UiButton type="submit" class="w-full" :disabled="form.processing || !addresses.length">تأكيد الطلب</UiButton>
        </aside>
    </form>
</template>
