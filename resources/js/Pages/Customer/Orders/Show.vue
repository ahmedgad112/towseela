<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CustomerLayout from '../../../Layouts/CustomerLayout.vue';
import OrderTimeline from '../../../Components/OrderTimeline.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import UiButton from '../../../Components/UiButton.vue';
import AppIcon from '../../../Components/AppIcon.vue';
import CustomerPageHeader from '../../../Components/CustomerPageHeader.vue';
import { useOrderLive } from '../../../orderLive';
import { canDo } from '../../../workspace';

defineOptions({ layout: CustomerLayout });
const props = defineProps({
    order: { type: Object, required: true },
});

const page = usePage();
const canReorder = computed(() => canDo(page.props.auth, 'customer.reorder'));
const canCancel = computed(() => canDo(page.props.auth, 'customer.cancel_order'));
const { live } = useOrderLive({
    channel: 'customer',
    only: ['order'],
    soundOn: ['assigned', 'updated'],
});

const driver = computed(() => props.order.driver || null);
const driverPhoneHref = computed(() => {
    const phone = driver.value?.phone;
    if (!phone) {
        return null;
    }

    return `tel:${String(phone).replace(/[^\d+]/g, '')}`;
});
const showDriverCard = computed(() => Boolean(driver.value?.name) && [
    'assigned_to_driver',
    'picked_up',
    'out_for_delivery',
].includes(props.order.status));

const reorder = (id) => useForm({}).post(`/orders/${id}/reorder`);
const cancel = (id) => useForm({ reason: 'إلغاء من العميل' }).post(`/orders/${id}/cancel`);
</script>

<template>
    <CustomerPageHeader :title="order.number" eyebrow="تفاصيل الطلب">
        <div class="flex items-center gap-2">
            <span class="customer-live" :class="live ? 'is-on' : 'is-off'">
                <span class="customer-live-dot" />
                {{ live ? 'مباشر' : 'غير متصل' }}
            </span>
            <StatusBadge :status="order.status" />
        </div>
    </CustomerPageHeader>

    <section class="overflow-hidden rounded-[1.5rem] bg-navy p-5 text-white sm:p-6">
        <p class="text-lg font-extrabold tracking-tight">{{ order.business?.name_ar }}</p>
        <p class="mt-2 text-sm leading-6 text-white/70">{{ order.address_snapshot?.line1 }} · {{ order.address_snapshot?.city }}</p>
        <p class="mt-4 inline-flex rounded-full bg-gold px-3 py-1.5 text-sm font-bold text-navy">
            الدفع عند الاستلام · {{ order.grand_total }}
        </p>
    </section>

    <section v-if="showDriverCard" class="customer-surface space-y-3 p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-bold tracking-[0.14em] text-gold">المندوب المعيّن</p>
                <p class="mt-1.5 text-lg font-extrabold tracking-tight text-navy">{{ driver.name }}</p>
                <p v-if="driver.phone" class="mt-1 text-sm text-muted">{{ driver.phone }}</p>
            </div>
            <span class="grid size-12 place-items-center rounded-2xl bg-navy text-gold">
                <AppIcon name="truck" />
            </span>
        </div>
        <UiButton
            v-if="driverPhoneHref"
            :href="driverPhoneHref"
            variant="navy"
            class="w-full gap-2"
        >
            <AppIcon name="phone" size="sm" />
            اتصال بالمندوب
        </UiButton>
    </section>

    <section class="customer-section">
        <h3 class="customer-section-title">عناصر الطلب</h3>
        <div class="flex flex-col gap-2.5">
            <article
                v-for="item in order.items"
                :key="item.id"
                class="customer-surface flex items-center justify-between gap-3 p-4 text-sm"
            >
                <span class="min-w-0 text-navy">{{ item.quantity }}× {{ item.name }}</span>
                <span class="shrink-0 font-bold text-navy">{{ item.total }}</span>
            </article>
        </div>
    </section>

    <OrderTimeline :histories="order.status_histories" />

    <div class="flex flex-wrap gap-2.5">
        <UiButton v-if="canReorder" @click="reorder(order.id)">إعادة الطلب</UiButton>
        <UiButton
            v-if="canCancel && ['new', 'pending_confirmation'].includes(order.status)"
            variant="danger"
            @click="cancel(order.id)"
        >
            إلغاء
        </UiButton>
    </div>
</template>
