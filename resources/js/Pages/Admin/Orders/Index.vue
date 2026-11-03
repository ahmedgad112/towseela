<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Pagination from '../../../Components/Pagination.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import UiButton from '../../../Components/UiButton.vue';
import { useOrderLive } from '../../../orderLive';
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
const canCreateOrder = computed(() => page.props.auth.roles.includes('super_admin') || page.props.auth.permissions.includes('orders.create'));
const { live } = useOrderLive({
    channel: 'ops',
    only: ['orders'],
    soundOn: ['created'],
});
const search = () => router.get('/admin/orders', { q: q.value, status: status.value }, { preserveState: true });
</script>

<template>
    <PageHeader title="الطلبات" text="ابحث برقم الطلب أو هاتف العميل، أو صفّ حسب الحالة.">
        <UiButton v-if="canCreateOrder" href="/dispatch/orders/create">طلب جديد</UiButton>
        <span
            class="rounded-full px-3 py-1 text-[11px] font-bold"
            :class="live ? 'bg-gold/20 text-navy' : 'bg-canvas text-muted'"
        >
            {{ live ? 'مباشر' : '...' }}
        </span>
    </PageHeader>
    <form class="app-card flex flex-col gap-2 p-3 sm:flex-row" @submit.prevent="search">
        <input v-model="q" class="app-select flex-1" placeholder="رقم الطلب أو الهاتف">
        <select v-model="status" class="app-select sm:w-52">
            <option value="">كل الحالات</option>
            <option v-for="item in statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
        </select>
        <button class="min-h-12 rounded-2xl bg-navy px-4 text-sm font-semibold text-white">بحث</button>
    </form>
    <div v-if="orders.data?.length" class="app-card-grid">
        <Link
            v-for="order in orders.data"
            :key="order.id"
            :href="`/admin/orders/${order.id}`"
            class="app-item-card"
        >
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-bold text-navy">{{ order.number }}</p>
                    <p class="mt-1 text-sm text-muted">{{ order.customer?.name }}</p>
                </div>
                <StatusBadge :status="order.status" />
            </div>
            <div class="flex flex-wrap items-end justify-between gap-2 border-t border-line pt-3 text-sm">
                <span class="text-muted">{{ order.business?.name_ar }}</span>
                <span class="font-semibold text-navy">{{ money(order.grand_total, currency) }}</span>
            </div>
        </Link>
    </div>
    <EmptyState v-else title="لا توجد طلبات" text="جرّب تغيير البحث أو فلتر الحالة." />
    <Pagination :links="orders.links" />
</template>
