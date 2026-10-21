<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Pagination from '../../../Components/Pagination.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import FormSection from '../../../Components/FormSection.vue';
import ToggleField from '../../../Components/ToggleField.vue';
import { canDo } from '../../../workspace';

defineOptions({ layout: AdminLayout });
defineProps({ drivers: { type: Object, required: true } });
const page = usePage();
const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    vehicle_type: 'موتوسيكل',
    is_available: true,
});
</script>

<template>
    <PageHeader title="المناديب" text="أضف مندوب التوصيل مع نوع المركبة وحالة التوفر من البداية." />

    <div class="grid gap-4 xl:grid-cols-[minmax(0,22rem)_1fr]">
        <FormSection title="مندوب جديد">
            <form v-if="canDo(page.props.auth, 'drivers.manage')" class="space-y-4" @submit.prevent="form.post('/admin/drivers', { onSuccess: () => form.reset() })">
                <UiInput v-model="form.name" label="الاسم" :error="form.errors.name" />
                <UiInput v-model="form.email" type="email" label="البريد" :error="form.errors.email" />
                <UiInput v-model="form.phone" label="الهاتف" :error="form.errors.phone" />
                <UiInput v-model="form.password" type="password" label="كلمة المرور" :error="form.errors.password" />
                <UiInput v-model="form.vehicle_type" label="نوع المركبة" placeholder="موتوسيكل" :error="form.errors.vehicle_type" />
                <ToggleField v-model="form.is_available" label="متاح للتوزيع الآن" />
                <UiButton type="submit" :disabled="form.processing">إضافة مندوب</UiButton>
            </form>
        </FormSection>

        <div class="space-y-4">
            <div class="app-card overflow-hidden">
                <div v-if="drivers.data?.length">
                    <p v-for="driver in drivers.data" :key="driver.id" class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-5 py-3 text-sm last:border-0">
                        <span>
                            <span class="block font-semibold text-navy">{{ driver.name }}</span>
                            <span class="text-muted">{{ driver.phone }}</span>
                        </span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="driver.driver_profile?.is_available ? 'bg-emerald-50 text-emerald-700' : 'bg-canvas text-muted'">
                            {{ driver.driver_profile?.is_available ? 'متاح' : 'غير متاح' }} · {{ driver.active_assignments_count }} نشط
                        </span>
                    </p>
                </div>
                <EmptyState v-else title="لا يوجد مناديب" text="أضف مندوباً ليظهر في غرفة التوزيع." />
            </div>
            <Pagination :links="drivers.links" />
        </div>
    </div>
</template>
