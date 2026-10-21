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
    <div class="app-card overflow-hidden">
        <div v-if="customers.data?.length" class="overflow-x-auto">
            <table class="app-table">
                <thead class="bg-canvas text-muted">
                    <tr>
                        <th>الاسم</th>
                        <th>الهاتف</th>
                        <th>الطلبات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="customer in customers.data" :key="customer.id" class="border-t border-line hover:bg-canvas/60">
                        <td>
                            <Link :href="`/admin/customers/${customer.id}`" class="font-semibold text-navy">{{ customer.name }}</Link>
                        </td>
                        <td>{{ customer.phone }}</td>
                        <td>{{ customer.orders_count }} طلب</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <EmptyState v-else title="لا يوجد عملاء" text="سيظهر العملاء هنا بعد أول تسجيل." />
    </div>
    <Pagination :links="customers.links" />
</template>
