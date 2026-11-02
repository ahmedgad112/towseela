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
    <div v-if="businesses.data?.length" class="app-card-grid">
        <article v-for="business in businesses.data" :key="business.id" class="app-item-card">
            <div class="flex items-start gap-3">
                <img
                    v-if="business.cover_url"
                    :src="business.cover_url"
                    alt=""
                    class="size-14 rounded-2xl object-cover"
                >
                <span v-else class="grid size-14 place-items-center rounded-2xl bg-canvas text-xs text-muted">صورة</span>
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-navy">{{ business.name_ar }}</p>
                    <p class="mt-1 text-sm text-muted">{{ business.category?.name_ar }}</p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="business.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-canvas text-muted'">
                    {{ business.is_active ? 'نشط' : 'موقوف' }}
                </span>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line pt-3 text-sm">
                <span class="text-muted">{{ business.products_count || 0 }} منتج</span>
                <div class="flex flex-wrap items-center gap-3">
                    <Link :href="`/admin/businesses/${business.id}`" class="font-semibold text-gold">عرض</Link>
                    <Link v-if="canDo(page.props.auth, 'businesses.manage')" :href="`/admin/businesses/${business.id}/edit`" class="text-muted hover:text-navy">تعديل</Link>
                </div>
            </div>
        </article>
    </div>
    <EmptyState v-else title="لا توجد متاجر" text="أضف متجراً ليظهر في واجهة العميل." />
    <Pagination :links="businesses.links" />
</template>
