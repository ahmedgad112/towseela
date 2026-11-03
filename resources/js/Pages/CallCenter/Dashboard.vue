<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CallCenterLayout from '../../Layouts/CallCenterLayout.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import UiButton from '../../Components/UiButton.vue';
import EmptyState from '../../Components/EmptyState.vue';
import Pagination from '../../Components/Pagination.vue';
import AppIcon from '../../Components/AppIcon.vue';
import { useOrderLive } from '../../orderLive';
import { money, primaryRole, workspaceFor } from '../../workspace';

defineOptions({ layout: CallCenterLayout });
const props = defineProps({
    orders: { type: Object, required: true },
    drivers: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, default: () => ({}) },
});

const page = usePage();
const q = ref(props.filters.q || '');
const role = computed(() => primaryRole(page.props.auth.roles));
const workspace = computed(() => workspaceFor(page.props.auth.roles));
const canAssign = computed(() => page.props.auth.roles.includes('super_admin') || page.props.auth.permissions.includes('orders.assign_driver'));
const canConfirm = computed(() => page.props.auth.roles.includes('super_admin') || page.props.auth.permissions.includes('orders.confirm'));
const canCreateOrder = computed(() => page.props.auth.roles.includes('super_admin') || page.props.auth.permissions.includes('orders.create'));
const currency = computed(() => page.props.currencyLabel || 'ج.م');
const assignForms = Object.fromEntries((props.orders.data || []).map((order) => [order.id, useForm({ driver_id: '' })]));
const { live } = useOrderLive({
    channel: 'ops',
    only: ['orders', 'drivers', 'counts'],
    soundOn: ['created', 'assigned', 'updated'],
});

const chips = [
    { value: '', label: 'النشطة' },
    { value: 'pending_confirmation', label: 'واردة' },
    { value: 'awaiting_driver', label: 'بانتظار مندوب' },
    { value: 'assigned_to_driver', label: 'معيّنة' },
    { value: 'out_for_delivery', label: 'في الطريق' },
];

const filter = (status = props.filters.status || '') => router.get('/dispatch', { q: q.value, status }, { preserveState: true });
</script>

<template>
    <section class="dashboard-hero">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="dashboard-eyebrow">{{ workspace.eyebrow }}</p>
                <h2 class="dashboard-title mt-1.5 sm:mt-2">{{ workspace.title }}</h2>
            </div>
            <span
                class="shrink-0 rounded-full px-3 py-1 text-[11px] font-bold sm:text-xs"
                :class="live ? 'bg-gold text-navy' : 'bg-white/10 text-white/70'"
            >
                {{ live ? 'مباشر' : '...' }}
            </span>
        </div>
        <p class="dashboard-subtitle">
            {{ role === 'dispatcher' ? 'وزّع الطلبات على المناديب المتاحين وتابع التحميل الحالي.' : 'أكد الطلبات الواردة وتواصل مع العميل عند الحاجة.' }}
        </p>
        <div v-if="canCreateOrder" class="mt-4">
            <UiButton href="/dispatch/orders/create">طلب جديد من الاتصال</UiButton>
        </div>
    </section>

    <div class="grid gap-3 sm:gap-3.5 md:grid-cols-2">
        <button class="rounded-[1.35rem] bg-gold p-4 text-start text-navy sm:rounded-3xl sm:p-5" @click="filter('pending_confirmation')">
            <p class="dashboard-metric-label text-navy/80">طلبات واردة</p>
            <p class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ counts.incoming }}</p>
            <p class="mt-1 text-[11px] leading-5 text-navy/70 sm:text-xs">بانتظار التأكيد من مركز الاتصال</p>
        </button>
        <article class="app-card p-4 sm:p-5">
            <p class="dashboard-metric-label">طلبات متأخرة</p>
            <p class="dashboard-metric-value">{{ counts.overdue }}</p>
            <p class="mt-1 text-[11px] leading-5 text-muted sm:text-xs">تجاوزت مدة المعالجة المتوقعة</p>
        </article>
    </div>

    <form class="app-card flex flex-col gap-2.5 p-2.5 sm:flex-row sm:items-center sm:gap-3 sm:p-3" @submit.prevent="filter()">
        <input v-model="q" class="min-h-11 flex-1 rounded-2xl bg-canvas px-4 text-sm sm:min-h-12" placeholder="رقم الطلب / الاسم / الهاتف">
        <UiButton type="submit">بحث</UiButton>
    </form>

    <div class="no-scrollbar flex gap-2 overflow-x-auto">
        <button
            v-for="chip in chips"
            :key="chip.value || 'all'"
            class="app-chip shrink-0"
            :class="{ 'border-navy bg-navy text-white hover:border-navy': (filters.status || '') === chip.value }"
            @click="filter(chip.value)"
        >
            {{ chip.label }}
        </button>
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_20rem] xl:gap-5">
        <section class="space-y-3">
            <EmptyState v-if="!orders.data?.length" title="لا توجد طلبات في هذا التصفية" text="غيّر الحالة أو ابحث برقم الطلب أو هاتف العميل." />
            <article v-for="order in orders.data" :key="order.id" class="app-card p-3.5 sm:p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <Link :href="`/dispatch/orders/${order.id}`" class="text-base font-extrabold tracking-tight text-navy hover:text-navy-secondary sm:text-lg">{{ order.number }}</Link>
                        <p class="mt-1 text-[13px] text-navy sm:text-sm">{{ order.customer?.name }} · {{ order.customer?.phone }}</p>
                    </div>
                    <StatusBadge :status="order.status" />
                </div>
                <p class="mt-2 text-[13px] leading-6 text-muted sm:text-sm">{{ order.business?.name_ar }} · {{ order.address_snapshot?.line1 }}</p>
                <p class="mt-1 text-[13px] font-bold text-navy sm:text-sm">{{ money(order.grand_total, currency) }}</p>
                <form v-if="canAssign" class="mt-4 flex flex-col gap-2 sm:flex-row" @submit.prevent="assignForms[order.id].post(`/dispatch/orders/${order.id}/assign`)">
                    <select v-model="assignForms[order.id].driver_id" class="app-select flex-1">
                        <option value="">مندوب متاح</option>
                        <option v-for="driver in drivers.filter((item) => item.available)" :key="driver.id" :value="driver.id">
                            {{ driver.name }} ({{ driver.active_assignments_count }})
                        </option>
                    </select>
                    <UiButton type="submit">تعيين</UiButton>
                </form>
                <div v-else-if="canConfirm && order.status === 'pending_confirmation'" class="mt-4">
                    <UiButton :href="`/dispatch/orders/${order.id}`">مراجعة وتأكيد</UiButton>
                </div>
            </article>
            <Pagination :links="orders.links" />
        </section>
        <aside class="space-y-3">
            <div class="app-card flex items-center gap-2 p-4">
                <span class="grid size-9 place-items-center rounded-2xl bg-navy text-gold"><AppIcon name="truck" /></span>
                <div>
                    <h3 class="font-bold text-navy">المناديب</h3>
                    <p class="text-xs text-muted">التوفر محسوب من السجلات الحالية</p>
                </div>
            </div>
            <p v-if="!drivers.length" class="app-card p-4 text-sm text-muted">لا يوجد مناديب مسجّلون.</p>
            <article v-for="driver in drivers" :key="driver.id" class="app-item-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-navy">{{ driver.name }}</p>
                        <p class="mt-1 text-xs text-muted">{{ driver.active_assignments_count }} طلب نشط</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="driver.available ? 'bg-emerald-50 text-emerald-700' : 'bg-canvas text-muted'">
                        {{ driver.available ? 'متاح' : 'مشغول' }}
                    </span>
                </div>
            </article>
        </aside>
    </div>
</template>
