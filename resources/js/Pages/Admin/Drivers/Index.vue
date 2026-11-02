<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Pagination from '../../../Components/Pagination.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import FormSection from '../../../Components/FormSection.vue';
import PasswordRequirements from '../../../Components/PasswordRequirements.vue';
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
                <UiInput v-model="form.password" type="password" label="كلمة المرور" autocomplete="new-password" :error="form.errors.password" />
                <PasswordRequirements :password="form.password" />
                <UiInput v-model="form.vehicle_type" label="نوع المركبة" placeholder="موتوسيكل" :error="form.errors.vehicle_type" />
                <ToggleField v-model="form.is_available" label="متاح للتوزيع الآن" />
                <UiButton type="submit" :disabled="form.processing">إضافة مندوب</UiButton>
            </form>
        </FormSection>

        <div class="space-y-4">
            <div v-if="drivers.data?.length" class="app-card-grid xl:grid-cols-2">
                <article v-for="driver in drivers.data" :key="driver.id" class="app-item-card">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-navy">{{ driver.name }}</p>
                            <p class="mt-1 text-sm text-muted">{{ driver.phone }}</p>
                            <p class="text-sm text-muted">{{ driver.driver_profile?.vehicle_type || 'بدون مركبة' }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="driver.driver_profile?.is_available ? 'bg-emerald-50 text-emerald-700' : 'bg-canvas text-muted'">
                            {{ driver.driver_profile?.is_available ? 'متاح' : 'غير متاح' }}
                        </span>
                    </div>
                    <p class="border-t border-line pt-3 text-sm text-muted">{{ driver.active_assignments_count }} طلب نشط</p>
                </article>
            </div>
            <EmptyState v-else title="لا يوجد مناديب" text="أضف مندوباً ليظهر في غرفة التوزيع." />
            <Pagination :links="drivers.links" />
        </div>
    </div>
</template>
