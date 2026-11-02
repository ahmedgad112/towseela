<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import SummaryCard from '../../Components/SummaryCard.vue';
import PageHeader from '../../Components/PageHeader.vue';
import EmptyState from '../../Components/EmptyState.vue';
import { money, primaryRole, workspaceFor } from '../../workspace';

defineOptions({ layout: AdminLayout });
const props = defineProps({
    metrics: { type: Object, required: true },
    salesByBusiness: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const role = computed(() => primaryRole(page.props.auth.roles));
const workspace = computed(() => workspaceFor(page.props.auth.roles));
const currency = computed(() => page.props.currencyLabel || 'ج.م');

const apply = (event) => {
    const form = event.target;
    router.get('/admin', { from: form.from.value, to: form.to.value }, { preserveState: true });
};
</script>

<template>
    <section class="dashboard-hero">
        <p class="dashboard-eyebrow">{{ workspace.eyebrow }}</p>
        <h2 class="dashboard-title mt-1.5 sm:mt-2">مرحباً {{ page.props.auth.user?.name }}</h2>
        <p class="dashboard-subtitle">{{ workspace.subtitle }}</p>
    </section>

    <PageHeader
        title="ملخص التشغيل"
        :text="role === 'business_manager' ? 'تابع مبيعات متجرك والطلبات المفتوحة.' : 'فلتر الفترة ثم راجع حجم الطلبات والمتأخرات.'"
    >
        <form class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-end" @submit.prevent="apply">
            <label class="min-w-0 flex-1 text-[11px] font-medium text-muted sm:min-w-36 sm:flex-none sm:text-xs">
                من
                <input name="from" type="date" :value="filters.from" class="app-select mt-1 block min-h-11 w-full text-sm sm:min-h-12">
            </label>
            <label class="min-w-0 flex-1 text-[11px] font-medium text-muted sm:min-w-36 sm:flex-none sm:text-xs">
                إلى
                <input name="to" type="date" :value="filters.to" class="app-select mt-1 block min-h-11 w-full text-sm sm:min-h-12">
            </label>
            <button class="min-h-11 rounded-2xl bg-navy px-4 text-[13px] font-semibold text-white transition hover:bg-navy-secondary sm:min-h-12 sm:text-sm">
                تصفية
            </button>
        </form>
    </PageHeader>

    <div class="dashboard-metrics">
        <SummaryCard label="عدد الطلبات" :value="metrics.orders_count" icon="orders" />
        <SummaryCard label="مبيعات المسلّم" :value="money(metrics.sales_total, currency)" icon="wallet" />
        <SummaryCard label="بانتظار التأكيد" :value="metrics.pending_count" icon="clock" />
        <SummaryCard label="طلبات متأخرة" :value="metrics.overdue_count" icon="alert" />
    </div>

    <section class="dashboard-section">
        <div>
            <h3 class="text-base font-extrabold tracking-tight text-navy sm:text-lg">المبيعات حسب المتجر</h3>
            <p class="dashboard-text">الطلبات والمبيعات المسلّمة خلال الفترة المحددة.</p>
        </div>
        <div v-if="salesByBusiness.length" class="app-card-grid">
            <article v-for="row in salesByBusiness" :key="row.id" class="app-item-card gap-2.5 p-3.5 sm:gap-3 sm:p-4">
                <p class="text-[15px] font-extrabold tracking-tight text-navy sm:text-base">{{ row.name }}</p>
                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-line pt-2.5 text-[13px] sm:pt-3 sm:text-sm">
                    <span class="text-muted">{{ row.orders }} طلب</span>
                    <span class="font-bold text-navy">{{ money(row.sales, currency) }}</span>
                </div>
            </article>
        </div>
        <EmptyState v-else title="لا توجد مبيعات في هذه الفترة" text="جرّب توسيع نطاق التاريخ أو راجع حالة الطلبات المسلّمة." />
    </section>
</template>
