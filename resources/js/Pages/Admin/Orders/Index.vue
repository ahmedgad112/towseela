<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Pagination from '../../../Components/Pagination.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { money } from '../../../workspace';

defineOptions({ layout: AdminLayout });
const props = defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
});
const q = ref(props.filters.q || '');
const status = ref(props.filters.status || '');
const page = usePage();
const currency = computed(() => page.props.currencyLabel || 'ج.م');
const search = () => router.get('/admin/orders', { q: q.value, status: status.value }, { preserveState: true });
</script>

<template>
    <PageHeader title="الطلبات" text="ابحث برقم الطلب أو هاتف العميل، أو صفّ حسب الحالة." />
    <form class="app-card flex flex-col gap-2 p-3 sm:flex-row" @submit.prevent="search">
        <input v-model="q" class="app-select flex-1" placeholder="رقم الطلب أو الهاتف">
        <select v-model="status" class="app-select sm:w-52">
            <option value="">كل الحالات</option>
            <option v-for="item in statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
        </select>
        <button class="min-h-12 rounded-2xl bg-navy px-4 text-sm font-semibold text-white">بحث</button>
    </form>
    <div class="app-card overflow-hidden">
        <div v-if="orders.data?.length" class="overflow-x-auto">
            <table class="app-table">
                <thead class="bg-canvas text-muted">
                    <tr>
                        <th>الطلب</th>
                        <th>العميل</th>
                        <th>المتجر</th>
                        <th>الحالة</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="order in orders.data" :key="order.id" class="border-t border-line hover:bg-canvas/60">
                        <td><Link :href="`/admin/orders/${order.id}`" class="font-semibold text-navy">{{ order.number }}</Link></td>
                        <td>{{ order.customer?.name }}</td>
                        <td>{{ order.business?.name_ar }}</td>
                        <td><StatusBadge :status="order.status" /></td>
                        <td>{{ money(order.grand_total, currency) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <EmptyState v-else title="لا توجد طلبات" text="جرّب تغيير البحث أو فلتر الحالة." />
    </div>
    <Pagination :links="orders.links" />
</template>
