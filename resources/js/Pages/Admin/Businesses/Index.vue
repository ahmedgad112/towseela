<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UiButton from '../../../Components/UiButton.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Pagination from '../../../Components/Pagination.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { canDo } from '../../../workspace';

const page = usePage();

defineOptions({ layout: AdminLayout });
defineProps({ businesses: { type: Object, required: true } });
</script>

<template>
    <PageHeader title="المتاجر" text="إدارة المتاجر الظاهرة للعملاء.">
        <UiButton v-if="canDo(page.props.auth, 'businesses.manage')" href="/admin/businesses/create">إضافة متجر</UiButton>
    </PageHeader>
    <div class="app-card overflow-hidden">
        <div v-if="businesses.data?.length" class="overflow-x-auto">
            <table class="app-table">
                <thead class="bg-canvas text-muted">
                    <tr>
                        <th>المتجر</th>
                        <th>التصنيف</th>
                        <th>المنتجات</th>
                        <th>الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="business in businesses.data" :key="business.id" class="border-t border-line hover:bg-canvas/60">
                        <td>
                            <div class="flex items-center gap-3">
                                <img
                                    v-if="business.cover_url"
                                    :src="business.cover_url"
                                    alt=""
                                    class="size-12 rounded-2xl object-cover"
                                >
                                <span v-else class="grid size-12 place-items-center rounded-2xl bg-canvas text-xs text-muted">صورة</span>
                                <span class="font-semibold text-navy">{{ business.name_ar }}</span>
                            </div>
                        </td>
                        <td>{{ business.category?.name_ar }}</td>
                        <td>{{ business.products_count || 0 }}</td>
                        <td>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="business.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-canvas text-muted'">
                                {{ business.is_active ? 'نشط' : 'موقوف' }}
                            </span>
                        </td>
                        <td>
                            <div class="flex flex-wrap items-center gap-3">
                                <Link :href="`/admin/businesses/${business.id}`" class="text-sm font-semibold text-gold">عرض</Link>
                                <Link v-if="canDo(page.props.auth, 'businesses.manage')" :href="`/admin/businesses/${business.id}/edit`" class="text-sm text-muted hover:text-navy">تعديل</Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <EmptyState v-else title="لا توجد متاجر" text="أضف متجراً ليظهر في واجهة العميل." />
    </div>
    <Pagination :links="businesses.links" />
</template>
