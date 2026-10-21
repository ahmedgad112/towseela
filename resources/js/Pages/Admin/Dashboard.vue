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
    <section class="overflow-hidden rounded-3xl bg-navy px-5 py-6 text-white sm:px-7">
        <p class="text-xs font-semibold tracking-wide text-gold">{{ workspace.eyebrow }}</p>
        <h2 class="mt-2 text-2xl font-bold sm:text-3xl">مرحباً {{ page.props.auth.user?.name }}</h2>
        <p class="mt-2 max-w-2xl text-sm leading-7 text-white/70">{{ workspace.subtitle }}</p>
    </section>

    <PageHeader title="ملخص التشغيل" :text="role === 'business_manager' ? 'تابع مبيعات متجرك والطلبات المفتوحة.' : 'فلتر الفترة ثم راجع حجم الطلبات والمتأخرات.'">
        <form class="flex flex-wrap items-end gap-2" @submit.prevent="apply">
            <label class="text-xs text-muted">من
                <input name="from" type="date" :value="filters.from" class="app-select mt-1 block min-w-36">
            </label>
            <label class="text-xs text-muted">إلى
                <input name="to" type="date" :value="filters.to" class="app-select mt-1 block min-w-36">
            </label>
            <button class="min-h-12 rounded-2xl bg-navy px-4 text-sm font-semibold text-white">تصفية</button>
        </form>
    </PageHeader>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <SummaryCard label="عدد الطلبات" :value="metrics.orders_count" icon="orders" />
        <SummaryCard label="مبيعات المسلّم" :value="money(metrics.sales_total, currency)" icon="wallet" />
        <SummaryCard label="بانتظار التأكيد" :value="metrics.pending_count" icon="clock" />
        <SummaryCard label="طلبات متأخرة" :value="metrics.overdue_count" icon="alert" />
    </div>

    <section class="app-card overflow-hidden">
        <div class="border-b border-line px-5 py-4">
            <h3 class="font-bold text-navy">المبيعات حسب المتجر</h3>
            <p class="text-sm text-muted">الطلبات والمبيعات المسلّمة خلال الفترة المحددة.</p>
        </div>
        <div v-if="salesByBusiness.length" class="overflow-x-auto">
            <table class="app-table">
                <thead class="bg-canvas text-muted">
                    <tr>
                        <th>المتجر</th>
                        <th>الطلبات</th>
                        <th>المبيعات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in salesByBusiness" :key="row.id" class="border-t border-line hover:bg-canvas/60">
                        <td class="font-semibold text-navy">{{ row.name }}</td>
                        <td>{{ row.orders }}</td>
                        <td class="font-semibold">{{ money(row.sales, currency) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <EmptyState v-else title="لا توجد مبيعات في هذه الفترة" text="جرّب توسيع نطاق التاريخ أو راجع حالة الطلبات المسلّمة." />
    </section>
</template>
