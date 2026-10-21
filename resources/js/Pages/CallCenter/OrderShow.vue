<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CallCenterLayout from '../../Layouts/CallCenterLayout.vue';
import OrderTimeline from '../../Components/OrderTimeline.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import UiButton from '../../Components/UiButton.vue';
import AppIcon from '../../Components/AppIcon.vue';
import { money } from '../../workspace';

defineOptions({ layout: CallCenterLayout });
const props = defineProps({
    order: { type: Object, required: true },
    mapNote: { type: String, default: '' },
});

const page = usePage();
const canConfirm = computed(() => page.props.auth.roles.includes('super_admin') || page.props.auth.permissions.includes('orders.confirm'));
const canCancel = computed(() => page.props.auth.roles.includes('super_admin') || page.props.auth.permissions.includes('orders.cancel'));
const canNote = computed(() => page.props.auth.roles.includes('super_admin') || page.props.auth.permissions.includes('orders.note'));
const currency = computed(() => page.props.currencyLabel || 'ج.م');
const reject = useForm({ reason: '' });
const note = useForm({ internal_notes: props.order.internal_notes || '' });
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <Link href="/dispatch" class="text-sm text-muted hover:text-navy">العودة للطابور</Link>
            <h2 class="mt-1 text-2xl font-bold text-navy">{{ order.number }}</h2>
        </div>
        <StatusBadge :status="order.status" />
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="app-card space-y-3 p-5 text-sm">
            <div class="flex items-center gap-2 font-semibold text-navy">
                <AppIcon name="user" /> بيانات العميل
            </div>
            <p>{{ order.customer?.name }} · {{ order.customer?.phone }}</p>
            <p class="text-muted">{{ order.address_snapshot?.line1 }}</p>
            <p class="text-xs text-muted">{{ mapNote }}</p>
            <article v-for="item in order.items" :key="item.id" class="flex justify-between border-t border-line pt-2">
                <span>{{ item.quantity }}× {{ item.name }}</span>
                <span>{{ money(item.total, currency) }}</span>
            </article>
            <p class="pt-2 text-base font-bold text-navy">الإجمالي {{ money(order.grand_total, currency) }}</p>
        </section>

        <section class="space-y-3">
            <div v-if="canConfirm || canCancel" class="app-card space-y-3 p-5">
                <h3 class="font-bold text-navy">إجراءات مركز الاتصال</h3>
                <UiButton v-if="canConfirm && order.status === 'pending_confirmation'" class="w-full" @click="useForm({}).post(`/dispatch/orders/${order.id}/confirm`)">تأكيد الطلب</UiButton>
                <form v-if="canCancel" class="space-y-2" @submit.prevent="reject.post(`/dispatch/orders/${order.id}/reject`)">
                    <input v-model="reject.reason" class="app-select w-full" placeholder="سبب الرفض">
                    <UiButton variant="danger" type="submit" class="w-full">رفض الطلب</UiButton>
                </form>
            </div>
            <form v-if="canNote" class="app-card space-y-3 p-5" @submit.prevent="note.post(`/dispatch/orders/${order.id}/note`)">
                <h3 class="font-bold text-navy">ملاحظة داخلية</h3>
                <textarea v-model="note.internal_notes" class="min-h-28 w-full rounded-2xl border border-line p-3 text-sm" placeholder="تظهر للفريق فقط"></textarea>
                <UiButton type="submit">حفظ الملاحظة</UiButton>
            </form>
        </section>
    </div>
    <OrderTimeline :histories="order.status_histories" />
</template>
