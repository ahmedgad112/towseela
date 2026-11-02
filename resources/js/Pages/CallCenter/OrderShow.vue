<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CallCenterLayout from '../../Layouts/CallCenterLayout.vue';
import OrderDetailsCard from '../../Components/OrderDetailsCard.vue';
import OrderTimeline from '../../Components/OrderTimeline.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import UiButton from '../../Components/UiButton.vue';

defineOptions({ layout: CallCenterLayout });
const props = defineProps({
    order: { type: Object, required: true },
    mapNote: { type: String, default: '' },
    transitions: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const page = usePage();
const hasPermission = (permission) => page.props.auth.roles.includes('super_admin')
    || page.props.auth.permissions.includes(permission);
const canConfirm = computed(() => props.can.confirm ?? hasPermission('orders.confirm'));
const canCancel = computed(() => props.can.cancel ?? hasPermission('orders.cancel'));
const canNote = computed(() => props.can.note ?? hasPermission('orders.note'));
const canUpdateStatus = computed(() => props.can.updateStatus
    ?? (hasPermission('orders.update') || hasPermission('orders.confirm') || hasPermission('orders.cancel')));
const reject = useForm({ reason: '' });
const transition = useForm({ status: '', reason: '' });
const note = useForm({ internal_notes: props.order.internal_notes || '' });
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
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-3 no-print">
        <div>
            <Link href="/dispatch" class="text-sm text-muted hover:text-navy">العودة للطابور</Link>
            <h2 class="mt-1 text-2xl font-bold text-navy">{{ order.number }}</h2>
        </div>
        <StatusBadge :status="order.status" />
    </div>

    <OrderDetailsCard :order="order" :map-note="mapNote" />

    <div class="grid gap-4 lg:grid-cols-2 no-print">
        <section v-if="canConfirm || canCancel || (canUpdateStatus && statusOptions.length)" class="app-card space-y-3 p-5">
            <h3 class="font-bold text-navy">إجراءات مركز الاتصال</h3>
            <UiButton
                v-if="canConfirm && order.status === 'pending_confirmation'"
                class="w-full"
                @click="useForm({}).post(`/dispatch/orders/${order.id}/confirm`)"
            >
                تأكيد الطلب
            </UiButton>
            <form
                v-if="canUpdateStatus && statusOptions.length"
                class="space-y-2"
                @submit.prevent="transition.post(`/dispatch/orders/${order.id}/transition`)"
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
                <UiButton type="submit" class="w-full" :disabled="transition.processing">تحديث الحالة</UiButton>
            </form>
            <form v-if="canCancel" class="space-y-2" @submit.prevent="reject.post(`/dispatch/orders/${order.id}/reject`)">
                <input v-model="reject.reason" class="app-select w-full" placeholder="سبب الرفض" required>
                <p v-if="reject.errors.reason" class="text-sm text-red-700">{{ reject.errors.reason }}</p>
                <UiButton variant="danger" type="submit" class="w-full" :disabled="reject.processing">رفض / إلغاء الطلب</UiButton>
            </form>
        </section>
        <form v-if="canNote" class="app-card space-y-3 p-5" @submit.prevent="note.post(`/dispatch/orders/${order.id}/note`)">
            <h3 class="font-bold text-navy">ملاحظة داخلية</h3>
            <textarea v-model="note.internal_notes" class="min-h-28 w-full rounded-2xl border border-line p-3 text-sm" placeholder="تظهر للفريق فقط"></textarea>
            <UiButton type="submit">حفظ الملاحظة</UiButton>
        </form>
    </div>

    <div class="no-print">
        <OrderTimeline :histories="order.status_histories" />
    </div>
</template>
