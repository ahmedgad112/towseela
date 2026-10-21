<script setup>
import { Link } from '@inertiajs/vue3';
import CustomerLayout from '../../../Layouts/CustomerLayout.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';

defineOptions({ layout: CustomerLayout });
defineProps({
    orders: { type: Object, required: true },
});
</script>

<template>
    <div>
        <h2 class="text-2xl font-bold text-navy">طلباتي</h2>
        <p class="mt-1 text-sm text-muted">تابع حالة كل طلب من لحظة التأكيد حتى التسليم.</p>
    </div>
    <EmptyState v-if="!orders.data?.length" title="لا توجد طلبات بعد" text="ابدأ من المتاجر المتاحة في الصفحة الرئيسية." />
    <Link
        v-for="order in orders.data"
        :key="order.id"
        :href="`/orders/${order.id}`"
        class="app-card block p-4 transition hover:border-gold/50"
    >
        <div class="flex items-center justify-between gap-3">
            <p class="font-bold text-navy">{{ order.number }}</p>
            <StatusBadge :status="order.status" />
        </div>
        <p class="mt-2 text-sm text-muted">{{ order.business?.name_ar }}</p>
        <p class="mt-1 text-sm font-semibold text-navy">{{ order.grand_total }}</p>
    </Link>
</template>
