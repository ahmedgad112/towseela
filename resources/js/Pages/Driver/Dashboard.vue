<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DriverLayout from '../../Layouts/DriverLayout.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import EmptyState from '../../Components/EmptyState.vue';
import AppIcon from '../../Components/AppIcon.vue';

defineOptions({ layout: DriverLayout });
defineProps({
    queue: { type: Array, default: () => [] },
    performance: { type: Object, default: null },
    available: { type: Boolean, default: false },
});

const page = usePage();
const greeting = computed(() => `مرحباً ${page.props.auth.user?.name || ''}`);
</script>

<template>
    <section class="overflow-hidden rounded-3xl bg-navy p-5 text-white">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold text-gold">{{ greeting }}</p>
                <h2 class="mt-1 text-xl font-bold">طلباتك الحالية</h2>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="available ? 'bg-gold text-navy' : 'bg-white/10 text-white/80'">
                {{ available ? 'متاح للعمل' : 'غير متاح' }}
            </span>
        </div>
        <div v-if="performance" class="mt-5 grid grid-cols-2 gap-3">
            <div class="rounded-2xl bg-white/10 p-3">
                <p class="text-xs text-white/60">تم التسليم</p>
                <p class="mt-1 text-xl font-bold">{{ performance.delivered }}</p>
            </div>
            <div class="rounded-2xl bg-white/10 p-3">
                <p class="text-xs text-white/60">نسبة النجاح</p>
                <p class="mt-1 text-xl font-bold">{{ performance.success_rate ?? '—' }}%</p>
            </div>
        </div>
    </section>

    <EmptyState v-if="!queue.length" title="لا توجد طلبات معيّنة الآن" text="ستظهر هنا الطلبات المعيّنة لك فور توزيعها.">
        <template #icon><AppIcon name="truck" /></template>
    </EmptyState>
    <Link
        v-for="order in queue"
        :key="order.id"
        :href="`/driver/orders/${order.id}`"
        class="app-card block p-4 transition hover:-translate-y-0.5"
    >
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="font-bold text-navy">{{ order.number }}</p>
                <p class="mt-1 text-sm text-muted">{{ order.business?.name_ar }}</p>
            </div>
            <StatusBadge :status="order.status" />
        </div>
        <p class="mt-3 inline-flex items-center gap-1 text-sm text-navy">
            <AppIcon name="pin" />
            افتح تفاصيل الاستلام والتسليم
        </p>
    </Link>
</template>
