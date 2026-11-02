<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DriverLayout from '../../Layouts/DriverLayout.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import EmptyState from '../../Components/EmptyState.vue';
import AppIcon from '../../Components/AppIcon.vue';
import { useOrderLive } from '../../orderLive';

defineOptions({ layout: DriverLayout });
defineProps({
    queue: { type: Array, default: () => [] },
    performance: { type: Object, default: null },
    available: { type: Boolean, default: false },
});

const page = usePage();
const greeting = computed(() => `مرحباً ${page.props.auth.user?.name || ''}`);
const { live } = useOrderLive({
    channel: 'driver',
    only: ['queue', 'performance', 'available'],
    soundOn: ['assigned'],
});
</script>

<template>
    <section class="dashboard-hero p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="dashboard-eyebrow">{{ greeting }}</p>
                <h2 class="dashboard-title mt-1.5">طلباتك الحالية</h2>
            </div>
            <div class="flex shrink-0 flex-col items-end gap-1.5">
                <span
                    class="rounded-full px-3 py-1 text-[11px] font-bold sm:text-xs"
                    :class="available ? 'bg-gold text-navy' : 'bg-white/10 text-white/80'"
                >
                    {{ available ? 'متاح للعمل' : 'غير متاح' }}
                </span>
                <span class="text-[10px] font-semibold" :class="live ? 'text-gold' : 'text-white/50'">
                    {{ live ? 'مباشر' : '...' }}
                </span>
            </div>
        </div>
        <div v-if="performance" class="mt-4 grid grid-cols-2 gap-2.5 sm:mt-5 sm:gap-3">
            <div class="rounded-2xl bg-white/10 p-3 sm:p-3.5">
                <p class="text-[11px] text-white/60 sm:text-xs">تم التسليم</p>
                <p class="mt-1 text-lg font-extrabold tracking-tight sm:text-xl">{{ performance.delivered }}</p>
            </div>
            <div class="rounded-2xl bg-white/10 p-3 sm:p-3.5">
                <p class="text-[11px] text-white/60 sm:text-xs">نسبة النجاح</p>
                <p class="mt-1 text-lg font-extrabold tracking-tight sm:text-xl">{{ performance.success_rate ?? '—' }}%</p>
            </div>
        </div>
    </section>

    <EmptyState v-if="!queue.length" title="لا توجد طلبات معيّنة الآن" text="ستظهر هنا الطلبات المعيّنة لك فور توزيعها.">
        <template #icon><AppIcon name="truck" size="md" /></template>
    </EmptyState>

    <div v-else class="flex flex-col gap-3">
        <Link
            v-for="order in queue"
            :key="order.id"
            :href="`/driver/orders/${order.id}`"
            class="app-card block p-3.5 transition hover:-translate-y-0.5 sm:p-4"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[15px] font-extrabold tracking-tight text-navy sm:text-base">{{ order.number }}</p>
                    <p class="mt-1 text-[13px] text-muted sm:text-sm">{{ order.business?.name_ar }}</p>
                </div>
                <StatusBadge :status="order.status" />
            </div>
            <p class="mt-3 inline-flex items-center gap-1.5 text-[13px] font-semibold text-navy sm:text-sm">
                <AppIcon name="pin" size="xs" />
                افتح تفاصيل الاستلام والتسليم
            </p>
        </Link>
    </div>
</template>
