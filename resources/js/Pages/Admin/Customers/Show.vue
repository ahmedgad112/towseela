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
</template>
