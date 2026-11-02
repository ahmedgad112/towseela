<script setup>
import { Link } from '@inertiajs/vue3';
import CustomerLayout from '../../../Layouts/CustomerLayout.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppIcon from '../../../Components/AppIcon.vue';
import CustomerPageHeader from '../../../Components/CustomerPageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import { useOrderLive } from '../../../orderLive';

defineOptions({ layout: CustomerLayout });
defineProps({
    orders: { type: Object, required: true },
});

const { live } = useOrderLive({
    channel: 'customer',
    only: ['orders'],
    soundOn: ['assigned', 'updated', 'created'],
});
</script>

<template>
    <CustomerPageHeader title="طلباتي" text="تابع حالة كل طلب من التأكيد حتى التسليم.">
        <span class="customer-live" :class="live ? 'is-on' : 'is-off'">
            <span class="customer-live-dot" />
            {{ live ? 'مباشر' : 'غير متصل' }}
        </span>
    </CustomerPageHeader>

    <EmptyState v-if="!orders.data?.length" title="لا توجد طلبات بعد" text="ابدأ من المتاجر المتاحة في الصفحة الرئيسية.">
        <template #icon>
            <AppIcon name="bag" size="md" />
        </template>
        <Link href="/" class="customer-cta">استكشف المتاجر</Link>
    </EmptyState>

    <div v-else class="flex flex-col gap-3">
        <Link
            v-for="order in orders.data"
            :key="order.id"
            :href="`/orders/${order.id}`"
            class="customer-surface block p-4 transition hover:-translate-y-0.5 hover:border-gold/40 sm:p-5"
        >
            <div class="flex items-center justify-between gap-3">
                <p class="font-extrabold tracking-tight text-navy">{{ order.number }}</p>
                <StatusBadge :status="order.status" />
            </div>
            <p class="mt-2.5 text-sm text-muted">{{ order.business?.name_ar }}</p>
            <div class="mt-3 flex items-center justify-between gap-3 border-t border-line/80 pt-3">
                <p class="text-sm font-extrabold text-navy">{{ order.grand_total }}</p>
                <span class="text-[12px] font-semibold text-muted">التفاصيل</span>
            </div>
        </Link>
    </div>

    <Pagination :links="orders.links || []" />
</template>
