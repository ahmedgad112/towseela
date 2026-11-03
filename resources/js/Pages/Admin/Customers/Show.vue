<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import UiSelect from '../../../Components/UiSelect.vue';
import UiModal from '../../../Components/UiModal.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    customer: { type: Object, required: true },
    can: { type: Object, default: () => ({}) },
});

const blockOpen = ref(false);

const blockForm = useForm({
    duration: '7_days',
    blocked_until: '',
    reason: '',
});

const isBlocked = computed(() => !props.customer.is_active);

const blockLabel = computed(() => {
    if (props.customer.block_status === 'temporary') {
        return 'محظور مؤقتاً';
    }

    if (props.customer.block_status === 'permanent') {
        return 'محظور دائماً';
    }

    return 'نشط';
});

const blockTone = computed(() => (isBlocked.value ? 'text-red-700 bg-red-50' : 'text-emerald-700 bg-emerald-50'));

const formatDate = (value) => {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString('ar-EG', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
};

const openBlock = () => {
    blockForm.reset();
    blockForm.duration = '7_days';
    blockForm.clearErrors();
    blockOpen.value = true;
};

const closeBlock = () => {
    blockOpen.value = false;
    blockForm.reset();
    blockForm.clearErrors();
};

const saveBlock = () => {
    blockForm.post(`/admin/customers/${props.customer.id}/block`, {
        onSuccess: () => closeBlock(),
    });
};

const unblock = () => {
    if (!window.confirm(`فك حظر ${props.customer.name}؟`)) {
        return;
    }

    router.post(`/admin/customers/${props.customer.id}/unblock`);
};

const removeCustomer = () => {
    if (!window.confirm(`حذف حساب ${props.customer.name} نهائياً؟ لا يمكن التراجع عن ذلك.`)) {
        return;
    }

    router.delete(`/admin/customers/${props.customer.id}`);
};
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <Link href="/admin/customers" class="text-sm text-muted hover:text-navy">كل العملاء</Link>
            <h2 class="mt-1 text-2xl font-bold text-navy">{{ customer.name }}</h2>
            <p class="mt-1 text-sm text-muted">{{ customer.email }} · {{ customer.phone }}</p>
            <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="blockTone">
                {{ blockLabel }}
            </span>
            <p v-if="customer.block_status === 'temporary' && customer.blocked_until" class="mt-2 text-sm text-muted">
                ينتهي الحظر: {{ formatDate(customer.blocked_until) }}
            </p>
            <p v-if="customer.block_reason" class="mt-1 text-sm text-muted">السبب: {{ customer.block_reason }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <UiButton v-if="can.update && !isBlocked" type="button" variant="outline" @click="openBlock">
                حظر العميل
            </UiButton>
            <UiButton v-if="can.update && isBlocked" type="button" @click="unblock">
                فك الحظر
            </UiButton>
            <UiButton v-if="can.delete" type="button" variant="danger" @click="removeCustomer">
                حذف
            </UiButton>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="space-y-3">
            <h3 class="font-bold text-navy">العناوين</h3>
            <div v-if="customer.addresses?.length" class="grid gap-3">
                <article v-for="address in customer.addresses" :key="address.id" class="app-item-card">
                    <p class="text-sm leading-6 text-navy">{{ address.line1 }}</p>
                </article>
            </div>
            <EmptyState v-else title="لا توجد عناوين" />
        </section>
        <section class="space-y-3">
            <h3 class="font-bold text-navy">الطلبات</h3>
            <div v-if="customer.orders?.length" class="grid gap-3">
                <Link
                    v-for="order in customer.orders"
                    :key="order.id"
                    :href="`/admin/orders/${order.id}`"
                    class="app-item-card"
                >
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-semibold text-navy">{{ order.number }}</span>
                        <StatusBadge :status="order.status" />
                    </div>
                </Link>
            </div>
            <EmptyState v-else title="لا توجد طلبات" />
        </section>
    </div>

    <UiModal
        :open="blockOpen"
        title="حظر العميل"
        text="اختر مدة الحظر. الحظر يمنع تسجيل الدخول فوراً ويمكن فكه في أي وقت."
        @close="closeBlock"
    >
        <form class="space-y-3" @submit.prevent="saveBlock">
            <UiSelect v-model="blockForm.duration" label="مدة الحظر" :error="blockForm.errors.duration">
                <option value="1_day">يوم واحد</option>
                <option value="7_days">7 أيام</option>
                <option value="30_days">30 يوماً</option>
                <option value="custom">تاريخ مخصص</option>
                <option value="permanent">دائم</option>
            </UiSelect>

            <UiInput
                v-if="blockForm.duration === 'custom'"
                v-model="blockForm.blocked_until"
                type="datetime-local"
                label="ينتهي الحظر في"
                :error="blockForm.errors.blocked_until"
                required
            />

            <UiInput
                v-model="blockForm.reason"
                label="السبب (اختياري)"
                :error="blockForm.errors.reason"
            />

            <div class="flex justify-end gap-2 pt-2">
                <UiButton type="button" variant="outline" @click="closeBlock">إلغاء</UiButton>
                <UiButton type="submit" :disabled="blockForm.processing">تأكيد الحظر</UiButton>
            </div>
        </form>
    </UiModal>
</template>
