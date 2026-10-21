<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DriverLayout from '../../Layouts/DriverLayout.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import EmptyState from '../../Components/EmptyState.vue';
import Pagination from '../../Components/Pagination.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { money } from '../../workspace';

defineOptions({ layout: DriverLayout });
defineProps({ orders: { type: Object, required: true } });

const page = usePage();
const currency = computed(() => page.props.currencyLabel || 'ج.م');
</script>

<template>
    <PageHeader title="سجل التوصيل" text="كل الطلبات التي أنهيتها أو تعذّر تسليمها." />
    <EmptyState v-if="!orders.data?.length" title="لا يوجد سجل بعد" text="بعد أول عملية تسليم سيظهر الطلب هنا." />
    <article v-for="order in orders.data" :key="order.id" class="app-card p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="font-semibold text-navy">{{ order.number }}</p>
                <p class="mt-1 text-sm text-muted">{{ order.business?.name_ar }} · {{ money(order.grand_total, currency) }}</p>
            </div>
            <StatusBadge :status="order.status" />
        </div>
    </article>
    <Pagination :links="orders.links" />
</template>
