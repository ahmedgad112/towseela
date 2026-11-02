<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Pagination from '../../../Components/Pagination.vue';
import PageHeader from '../../../Components/PageHeader.vue';

defineOptions({ layout: AdminLayout });
defineProps({
    customers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});
</script>

<template>
    <PageHeader title="العملاء" text="ملفات العملاء وعدد طلباتهم." />
    <div v-if="customers.data?.length" class="app-card-grid">
        <Link
            v-for="customer in customers.data"
            :key="customer.id"
            :href="`/admin/customers/${customer.id}`"
            class="app-item-card"
        >
            <div>
                <p class="font-bold text-navy">{{ customer.name }}</p>
                <p class="mt-1 text-sm text-muted">{{ customer.phone }}</p>
            </div>
            <p class="border-t border-line pt-3 text-sm text-muted">{{ customer.orders_count }} طلب</p>
        </Link>
    </div>
    <EmptyState v-else title="لا يوجد عملاء" text="سيظهر العملاء هنا بعد أول تسجيل." />
    <Pagination :links="customers.links" />
</template>
