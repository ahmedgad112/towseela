<script setup>
import { useForm } from '@inertiajs/vue3';
import CustomerLayout from '../../../Layouts/CustomerLayout.vue';
import OrderTimeline from '../../../Components/OrderTimeline.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import UiButton from '../../../Components/UiButton.vue';

defineOptions({ layout: CustomerLayout });
defineProps({
    order: { type: Object, required: true },
});

const reorder = (id) => useForm({}).post(`/orders/${id}/reorder`);
const cancel = (id) => useForm({ reason: 'إلغاء من العميل' }).post(`/orders/${id}/cancel`);
</script>

<template>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-gold">تفاصيل الطلب</p>
            <h2 class="text-2xl font-bold text-navy">{{ order.number }}</h2>
        </div>
        <StatusBadge :status="order.status" />
    </div>
    <section class="app-card space-y-1 p-5 text-sm">
        <p class="text-lg font-bold text-navy">{{ order.business?.name_ar }}</p>
        <p class="text-muted">{{ order.address_snapshot?.line1 }} · {{ order.address_snapshot?.city }}</p>
        <p class="pt-2 font-semibold">الدفع عند الاستلام · {{ order.grand_total }}</p>
    </section>
    <article v-for="item in order.items" :key="item.id" class="app-card flex justify-between p-4 text-sm">
        <span>{{ item.quantity }}× {{ item.name }}</span>
        <span class="font-semibold">{{ item.total }}</span>
    </article>
    <OrderTimeline :histories="order.status_histories" />
    <div class="flex flex-wrap gap-2">
        <UiButton @click="reorder(order.id)">إعادة الطلب</UiButton>
        <UiButton v-if="['new', 'pending_confirmation'].includes(order.status)" variant="danger" @click="cancel(order.id)">إلغاء</UiButton>
    </div>
</template>
