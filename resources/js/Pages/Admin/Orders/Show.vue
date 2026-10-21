<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
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
});

const page = usePage();
const currency = computed(() => page.props.currencyLabel || 'ج.م');
const auth = computed(() => page.props.auth);
const assign = useForm({ driver_id: '' });
const transition = useForm({ status: '', reason: '' });
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
        </section>
        <section class="app-card space-y-4 p-5">
            <form v-if="canDo(auth, 'orders.assign_driver')" class="space-y-2" @submit.prevent="assign.post(`/admin/orders/${order.id}/assign`)">
                <p class="text-sm font-semibold text-navy">تعيين مندوب</p>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <select v-model="assign.driver_id" class="app-select flex-1">
                        <option value="">اختر مندوباً</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="driver.id">{{ driver.name }}</option>
                    </select>
                    <UiButton type="submit">تعيين</UiButton>
                </div>
            </form>
            <form v-if="canDo(auth, 'orders.update') || canDo(auth, 'orders.confirm') || canDo(auth, 'orders.cancel')" class="space-y-2 border-t border-line pt-4" @submit.prevent="transition.post(`/admin/orders/${order.id}/transition`)">
                <p class="text-sm font-semibold text-navy">تغيير الحالة</p>
                <select v-model="transition.status" class="app-select w-full">
                    <option value="">الحالة التالية</option>
                    <option v-for="item in transitions" :key="item" :value="item">{{ item }}</option>
                </select>
                <input v-model="transition.reason" class="app-select w-full" placeholder="سبب اختياري">
                <UiButton type="submit">تحديث الحالة</UiButton>
            </form>
        </section>
    </div>
    <OrderTimeline :histories="order.status_histories" />
</template>
