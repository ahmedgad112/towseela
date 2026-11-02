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
    <section class="space-y-3">
        <h3 class="font-bold text-navy">أداء المناديب</h3>
        <div v-if="driverPerformance.length" class="app-card-grid">
            <article v-for="driver in driverPerformance" :key="driver.id" class="app-item-card">
                <p class="font-bold text-navy">{{ driver.name }}</p>
                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-line pt-3 text-sm text-muted">
                    <span>{{ driver.delivered }}/{{ driver.assigned }} طلب</span>
                    <span class="font-semibold text-navy">{{ driver.success_rate ?? '—' }}%</span>
                </div>
            </article>
        </div>
        <EmptyState v-else title="لا يوجد أداء للعرض" />
    </section>
</template>
