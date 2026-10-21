<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import SummaryCard from '../../../Components/SummaryCard.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import { canDo, money } from '../../../workspace';

defineOptions({ layout: AdminLayout });
defineProps({
    metrics: { type: Object, required: true },
    salesByBusiness: { type: Array, default: () => [] },
    driverPerformance: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const currency = computed(() => page.props.currencyLabel || 'ج.م');
</script>

<template>
    <PageHeader title="التقارير" text="معدل نجاح المندوب = المسلّم ÷ (المسلّم + الفاشل + المرتجع + الملغي بعد التعيين).">
        <a v-if="canDo(page.props.auth, 'reports.export')" href="/admin/reports/export" class="inline-flex min-h-11 items-center rounded-xl bg-navy px-4 text-sm font-semibold text-white">تصدير المبيعات</a>
    </PageHeader>
    <div class="grid gap-4 md:grid-cols-3">
        <SummaryCard label="المبيعات المسلّمة" :value="money(metrics.sales_total, currency)" icon="wallet" />
        <SummaryCard label="نسبة الإلغاء" :value="metrics.cancellation_rate ?? '—'" icon="alert" />
        <SummaryCard label="متوسط التسليم بالدقائق" :value="metrics.average_delivery_minutes ?? '—'" icon="clock" />
    </div>
    <section class="app-card p-5">
        <h3 class="mb-3 font-bold text-navy">أداء المناديب</h3>
        <EmptyState v-if="!driverPerformance.length" title="لا يوجد أداء للعرض" />
        <p v-for="driver in driverPerformance" :key="driver.id" class="flex items-center justify-between border-b border-line py-2 text-sm last:border-0">
            <span class="font-medium text-navy">{{ driver.name }}</span>
            <span class="text-muted">{{ driver.delivered }}/{{ driver.assigned }} · {{ driver.success_rate ?? '—' }}%</span>
        </p>
    </section>
</template>
