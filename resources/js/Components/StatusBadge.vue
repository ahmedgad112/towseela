<script setup>
const props = defineProps({
    status: { type: [String, Object], required: true },
});

const labels = {
    new: 'جديد',
    pending_confirmation: 'بانتظار التأكيد',
    confirmed: 'مؤكد',
    preparing: 'قيد التحضير',
    awaiting_driver: 'بانتظار مندوب',
    assigned_to_driver: 'تم تعيين مندوب',
    picked_up: 'تم الاستلام',
    out_for_delivery: 'في الطريق',
    delivered: 'تم التسليم',
    cancelled: 'ملغي',
    delivery_failed: 'فشل التسليم',
    returned: 'مرتجع',
};

const value = () => (typeof props.status === 'object' ? props.status.value || props.status : props.status);
const label = () => (typeof props.status === 'object' && props.status.label ? props.status.label : labels[value()] || value());

const colors = {
    new: 'bg-gold/20 text-navy',
    pending_confirmation: 'bg-gold/20 text-navy',
    confirmed: 'bg-navy/10 text-navy',
    preparing: 'bg-navy/10 text-navy',
    awaiting_driver: 'bg-amber-100 text-amber-800',
    assigned_to_driver: 'bg-blue-50 text-blue-800',
    picked_up: 'bg-blue-50 text-blue-800',
    out_for_delivery: 'bg-indigo-50 text-indigo-800',
    delivered: 'bg-green-50 text-green-700',
    cancelled: 'bg-red-50 text-red-700',
    delivery_failed: 'bg-red-50 text-red-700',
    returned: 'bg-slate-100 text-slate-700',
};
</script>

<template>
    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="colors[value()] || 'bg-line text-muted'">
        {{ label() }}
    </span>
</template>
