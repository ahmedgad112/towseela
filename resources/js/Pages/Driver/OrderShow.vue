<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DriverLayout from '../../Layouts/DriverLayout.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import UiButton from '../../Components/UiButton.vue';
import AppIcon from '../../Components/AppIcon.vue';
import { money } from '../../workspace';

defineOptions({ layout: DriverLayout });
const props = defineProps({
    order: { type: Object, required: true },
    mapNote: { type: String, default: '' },
});

const page = usePage();
const currency = computed(() => page.props.currencyLabel || 'ج.م');
const fail = useForm({ reason: '' });
const deliver = useForm({ proof_of_delivery: '' });
</script>

<template>
    <div class="flex items-start justify-between gap-3">
        <div>
            <Link href="/driver" class="text-sm text-muted">العودة للطلبات</Link>
            <h2 class="mt-1 text-xl font-bold text-navy">{{ order.number }}</h2>
        </div>
        <StatusBadge :status="order.status" />
    </div>

    <section class="app-card space-y-3 p-4 text-sm">
        <p class="font-semibold text-navy">{{ order.business?.name_ar }}</p>
        <p class="inline-flex items-center gap-1"><AppIcon name="user" /> {{ order.customer?.name }} · {{ order.customer?.phone }}</p>
        <p class="inline-flex items-start gap-1 text-muted"><AppIcon name="pin" /> {{ order.address_snapshot?.line1 }}</p>
        <p class="text-xs text-muted">{{ mapNote }}</p>
        <a
            v-if="order.address_snapshot?.lat"
            class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-gold px-4 text-sm font-semibold text-navy"
            :href="`https://maps.google.com/?q=${order.address_snapshot.lat},${order.address_snapshot.lng}`"
            target="_blank"
            rel="noreferrer"
        >
            فتح الخريطة
        </a>
        <p class="font-bold text-navy">الإجمالي {{ money(order.grand_total, currency) }}</p>
    </section>

    <div class="grid gap-3">
        <UiButton v-if="order.status === 'assigned_to_driver'" class="w-full" @click="useForm({}).post(`/driver/orders/${order.id}/pickup`)">تأكيد الاستلام من المتجر</UiButton>
        <UiButton v-if="order.status === 'picked_up'" class="w-full" @click="useForm({}).post(`/driver/orders/${order.id}/out`)">خرجت للتسليم</UiButton>
        <form v-if="order.status === 'out_for_delivery'" class="app-card space-y-2 p-4" @submit.prevent="deliver.post(`/driver/orders/${order.id}/deliver`)">
            <input v-model="deliver.proof_of_delivery" class="app-select w-full" placeholder="ملاحظة إثبات التسليم">
            <UiButton type="submit" class="w-full">تأكيد التسليم</UiButton>
        </form>
        <form v-if="order.status === 'out_for_delivery'" class="app-card space-y-2 p-4" @submit.prevent="fail.post(`/driver/orders/${order.id}/fail`)">
            <input v-model="fail.reason" class="app-select w-full" placeholder="سبب فشل التسليم">
            <UiButton variant="danger" type="submit" class="w-full">فشل التسليم</UiButton>
        </form>
    </div>
</template>
