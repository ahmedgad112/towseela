<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import OrderTimeline from '../../../Components/OrderTimeline.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import UiButton from '../../../Components/UiButton.vue';
import { canDo, money } from '../../../workspace';

defineOptions({ layout: AdminLayout });
const props = defineProps({
    order: { type: Object, required: true },
    drivers: { type: Array, default: () => [] },
    transitions: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const page = usePage();
const currency = computed(() => page.props.currencyLabel || 'ج.م');
const auth = computed(() => page.props.auth);
const assign = useForm({ driver_id: '' });
const transition = useForm({ status: '', reason: '' });
const cancel = useForm({ reason: '' });

const canAssign = computed(() => props.can.assignDriver ?? canDo(auth.value, 'orders.assign_driver'));
const canUpdateStatus = computed(() => props.can.updateStatus
    ?? (canDo(auth.value, 'orders.update') || canDo(auth.value, 'orders.confirm') || canDo(auth.value, 'orders.cancel')));
const canCancel = computed(() => props.can.cancel ?? canDo(auth.value, 'orders.cancel'));
const canDelete = computed(() => props.can.delete ?? canDo(auth.value, 'orders.delete'));
const needsReason = computed(() => transition.status === 'cancelled');
const statusOptions = computed(() => props.transitions.map((item) => {
    if (typeof item === 'string') {
        return { value: item, label: item };
    }

    return {
        value: item?.value ?? '',
        label: item?.label ?? item?.value ?? '',
    };
}).filter((item) => item.value));

const submitTransition = () => {
    transition.post(`/admin/orders/${props.order.id}/transition`);
};

const submitCancel = () => {
    cancel.post(`/admin/orders/${props.order.id}/cancel`);
};

const destroyOrder = () => {
    if (!window.confirm(`حذف الطلب ${props.order.number} نهائياً؟`)) {
        return;
    }

    router.delete(`/admin/orders/${props.order.id}`);
};
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <Link href="/admin/orders" class="text-sm text-muted hover:text-navy">كل الطلبات</Link>
            <h2 class="mt-1 text-2xl font-bold text-navy">{{ order.number }}</h2>
        </div>
        <StatusBadge :status="order.status" />
    </div>
    <div class="grid gap-4 lg:grid-cols-2">
        <section class="app-card space-y-2 p-5 text-sm">
            <p class="font-semibold text-navy">{{ order.customer?.name }} · {{ order.customer?.phone }}</p>
            <p class="text-muted">{{ order.address_snapshot?.line1 }}</p>
            <article v-for="item in order.items" :key="item.id" class="flex justify-between border-t border-line pt-2">
                <span>{{ item.quantity }}× {{ item.name }}</span>
                <span>{{ money(item.total, currency) }}</span>
            </article>
            <p class="pt-2 text-base font-bold text-navy">الإجمالي {{ money(order.grand_total, currency) }}</p>
            <p v-if="order.cancel_reason" class="rounded-2xl bg-red-50 p-3 text-sm text-red-700">سبب الإلغاء: {{ order.cancel_reason }}</p>
        </section>
        <section class="app-card space-y-4 p-5">
            <form v-if="canAssign" class="space-y-2" @submit.prevent="assign.post(`/admin/orders/${order.id}/assign`)">
                <p class="text-sm font-semibold text-navy">تعيين مندوب</p>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <select v-model="assign.driver_id" class="app-select flex-1">
                        <option value="">اختر مندوباً</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="driver.id">{{ driver.name }}</option>
                    </select>
                    <UiButton type="submit">تعيين</UiButton>
                </div>
            </form>
            <form
                v-if="canUpdateStatus && statusOptions.length"
                class="space-y-2 border-t border-line pt-4"
                @submit.prevent="submitTransition"
            >
                <p class="text-sm font-semibold text-navy">تغيير الحالة</p>
                <select v-model="transition.status" class="app-select w-full" required>
                    <option disabled value="">الحالة التالية</option>
                    <option v-for="item in statusOptions" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
                <input
                    v-model="transition.reason"
                    class="app-select w-full"
                    :placeholder="needsReason ? 'سبب الإلغاء (مطلوب)' : 'سبب اختياري'"
                    :required="needsReason"
                >
                <p v-if="transition.errors.status" class="text-sm text-red-700">{{ transition.errors.status }}</p>
                <p v-if="transition.errors.reason" class="text-sm text-red-700">{{ transition.errors.reason }}</p>
                <UiButton type="submit" :disabled="transition.processing">تحديث الحالة</UiButton>
            </form>
            <form
                v-if="canCancel"
                class="space-y-2 border-t border-line pt-4"
                @submit.prevent="submitCancel"
            >
                <p class="text-sm font-semibold text-navy">إلغاء الطلب</p>
                <input v-model="cancel.reason" class="app-select w-full" placeholder="سبب الإلغاء" required>
                <p v-if="cancel.errors.reason" class="text-sm text-red-700">{{ cancel.errors.reason }}</p>
                <UiButton variant="danger" type="submit" :disabled="cancel.processing">إلغاء الطلب</UiButton>
            </form>
            <div v-if="canDelete" class="border-t border-line pt-4">
                <UiButton variant="danger" :disabled="false" @click="destroyOrder">حذف الطلب نهائياً</UiButton>
            </div>
        </section>
    </div>
    <OrderTimeline :histories="order.status_histories" />
</template>
