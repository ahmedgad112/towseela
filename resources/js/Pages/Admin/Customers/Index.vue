<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Pagination from '../../../Components/Pagination.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import UiModal from '../../../Components/UiModal.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({
    customers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
});

const q = ref(props.filters.q || '');
const createOpen = ref(false);

const form = useForm({
    name: '',
    phone: '',
    email: '',
    password: '',
});

const search = () => router.get('/admin/customers', { q: q.value }, { preserveState: true });

const openCreate = () => {
    form.reset();
    form.clearErrors();
    createOpen.value = true;
};

const closeCreate = () => {
    createOpen.value = false;
    form.reset();
    form.clearErrors();
};

const saveCreate = () => {
    form.post('/admin/customers', {
        onSuccess: () => closeCreate(),
    });
};
</script>

<template>
    <PageHeader title="العملاء" text="ابحث بالاسم أو رقم الهاتف، أو أنشئ عميلاً جديداً.">
        <UiButton v-if="can.create" type="button" @click="openCreate">عميل جديد</UiButton>
    </PageHeader>

    <form class="app-card flex flex-col gap-2 p-3 sm:flex-row" @submit.prevent="search">
        <input v-model="q" class="app-select flex-1" placeholder="الاسم أو رقم الهاتف">
        <button class="min-h-12 rounded-2xl bg-navy px-4 text-sm font-semibold text-white">بحث</button>
    </form>

    <div v-if="customers.data?.length" class="app-card-grid">
        <Link
            v-for="customer in customers.data"
            :key="customer.id"
            :href="`/admin/customers/${customer.id}`"
            class="app-item-card"
        >
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-bold text-navy">{{ customer.name }}</p>
                    <p class="mt-1 text-sm text-muted">{{ customer.phone }}</p>
                </div>
                <span
                    v-if="customer.block_status !== 'active'"
                    class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700"
                >
                    {{ customer.block_status === 'temporary' ? 'محظور مؤقت' : 'محظور' }}
                </span>
            </div>
            <p class="border-t border-line pt-3 text-sm text-muted">{{ customer.orders_count }} طلب</p>
        </Link>
    </div>
    <EmptyState v-else title="لا يوجد عملاء" text="جرّب البحث أو أنشئ عميلاً جديداً." />
    <Pagination :links="customers.links" />

    <UiModal :open="createOpen" title="عميل جديد" text="أدخل الاسم ورقم الهاتف. البريد وكلمة المرور اختياريان." @close="closeCreate">
        <form class="space-y-3" @submit.prevent="saveCreate">
            <UiInput v-model="form.name" label="الاسم" :error="form.errors.name" required />
            <UiInput v-model="form.phone" label="رقم الهاتف" :error="form.errors.phone" required />
            <UiInput v-model="form.email" type="email" label="البريد (اختياري)" :error="form.errors.email" />
            <UiInput v-model="form.password" type="password" label="كلمة المرور (اختياري)" :error="form.errors.password" />
            <div class="flex justify-end gap-2 pt-2">
                <UiButton type="button" variant="outline" @click="closeCreate">إلغاء</UiButton>
                <UiButton type="submit" :disabled="form.processing">حفظ</UiButton>
            </div>
        </form>
    </UiModal>
</template>
