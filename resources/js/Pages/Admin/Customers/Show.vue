<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import EmptyState from '../../../Components/EmptyState.vue';

defineOptions({ layout: AdminLayout });
defineProps({ customer: { type: Object, required: true } });
</script>

<template>
    <div>
        <Link href="/admin/customers" class="text-sm text-muted hover:text-navy">كل العملاء</Link>
        <h2 class="mt-1 text-2xl font-bold text-navy">{{ customer.name }}</h2>
        <p class="mt-1 text-sm text-muted">{{ customer.email }} · {{ customer.phone }}</p>
    </div>
    <div class="grid gap-4 lg:grid-cols-2">
        <section class="app-card p-5">
            <h3 class="mb-3 font-bold text-navy">العناوين</h3>
            <p v-for="address in customer.addresses" :key="address.id" class="border-b border-line py-2 text-sm last:border-0">{{ address.line1 }}</p>
            <EmptyState v-if="!customer.addresses?.length" title="لا توجد عناوين" />
        </section>
        <section class="app-card p-5">
            <h3 class="mb-3 font-bold text-navy">الطلبات</h3>
            <p v-for="order in customer.orders" :key="order.id" class="flex items-center justify-between gap-3 border-b border-line py-2 text-sm last:border-0">
                <Link :href="`/admin/orders/${order.id}`" class="font-semibold text-navy">{{ order.number }}</Link>
                <StatusBadge :status="order.status" />
            </p>
            <EmptyState v-if="!customer.orders?.length" title="لا توجد طلبات" />
        </section>
    </div>
</template>
