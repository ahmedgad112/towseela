<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import UiSelect from '../../../Components/UiSelect.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Pagination from '../../../Components/Pagination.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import FormSection from '../../../Components/FormSection.vue';
import { canDo, roleLabel } from '../../../workspace';

defineOptions({ layout: AdminLayout });
defineProps({
    staff: { type: Object, required: true },
    roles: { type: Array, default: () => [] },
});
const page = usePage();
const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    role: 'call_center_agent',
});
</script>

<template>
    <PageHeader title="الموظفون" text="أنشئ حساباً بدور واضح: إدارة، مركز اتصال، توزيع، أو مدير متجر." />

    <div class="grid gap-4 xl:grid-cols-[minmax(0,22rem)_1fr]">
        <FormSection title="موظف جديد">
            <form v-if="canDo(page.props.auth, 'users.create')" class="space-y-4" @submit.prevent="form.post('/admin/staff', { onSuccess: () => form.reset() })">
                <UiInput v-model="form.name" label="الاسم" :error="form.errors.name" />
                <UiInput v-model="form.email" type="email" label="البريد" :error="form.errors.email" />
                <UiInput v-model="form.phone" label="الهاتف" :error="form.errors.phone" />
                <UiInput v-model="form.password" type="password" label="كلمة المرور" :error="form.errors.password" />
                <UiSelect v-model="form.role" label="الدور" :error="form.errors.role">
                    <option v-for="item in roles" :key="item.id" :value="item.name">{{ roleLabel(item.name, item.label) }}</option>
                </UiSelect>
                <UiButton type="submit" :disabled="form.processing">إضافة موظف</UiButton>
            </form>
        </FormSection>

        <div class="space-y-4">
            <div class="app-card overflow-hidden">
                <div v-if="staff.data?.length">
                    <p v-for="user in staff.data" :key="user.id" class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-5 py-3 text-sm last:border-0">
                        <span>
                            <span class="block font-semibold text-navy">{{ user.name }}</span>
                            <span class="text-muted">{{ user.email }}</span>
                        </span>
                        <span class="text-muted">{{ user.roles?.map((role) => roleLabel(role.name)).join(' · ') }}</span>
                    </p>
                </div>
                <EmptyState v-else title="لا يوجد موظفون" />
            </div>
            <Pagination :links="staff.links" />
        </div>
    </div>
</template>
