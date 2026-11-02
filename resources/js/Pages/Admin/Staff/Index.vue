<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import UiSelect from '../../../Components/UiSelect.vue';
import UiModal from '../../../Components/UiModal.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Pagination from '../../../Components/Pagination.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import ToggleField from '../../../Components/ToggleField.vue';
import { roleLabel } from '../../../workspace';

defineOptions({ layout: AdminLayout });
const props = defineProps({
    staff: { type: Object, required: true },
    roles: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const createOpen = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    role: props.roles[0]?.name || 'call_center_agent',
});

const editForm = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    role: '',
    is_active: true,
});

const editingUser = computed(() => props.staff.data?.find((user) => user.id === editingId.value) || null);
const roleIsLocked = computed(() => Boolean(editingUser.value?.is_self || editingUser.value?.is_super_admin));
const editOpen = computed(() => Boolean(editingUser.value));

const openCreate = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.role = props.roles[0]?.name || 'call_center_agent';
    createOpen.value = true;
};

const closeCreate = () => {
    createOpen.value = false;
    form.reset();
    form.clearErrors();
};

const startEdit = (user) => {
    createOpen.value = false;
    editingId.value = user.id;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.phone = user.phone;
    editForm.password = '';
    editForm.role = user.role || user.roles?.[0]?.name || '';
    editForm.is_active = Boolean(user.is_active);
    editForm.clearErrors();
};

const closeEdit = () => {
    editingId.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const saveCreate = () => {
    form.post('/admin/staff', {
        onSuccess: () => closeCreate(),
    });
};

const saveEdit = () => {
    if (!editingId.value) {
        return;
    }

    editForm.patch(`/admin/staff/${editingId.value}`, {
        onSuccess: () => closeEdit(),
    });
};

const removeUser = (user) => {
    if (window.confirm(`حذف حساب ${user.name}؟`)) {
        router.delete(`/admin/staff/${user.id}`, {
            onSuccess: () => {
                if (editingId.value === user.id) {
                    closeEdit();
                }
            },
        });
    }
};
</script>

<template>
    <PageHeader title="الموظفون" text="أنشئ حساباً بدور واضح، عدّل بياناته وصلاحياته، أو احذفه عند الحاجة.">
        <UiButton v-if="can.create" type="button" @click="openCreate">موظف جديد</UiButton>
    </PageHeader>

    <div class="space-y-4">
        <div v-if="staff.data?.length" class="app-card-grid">
            <article v-for="user in staff.data" :key="user.id" class="app-item-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-bold text-navy">{{ user.name }}</p>
                        <p class="mt-1 text-sm text-muted">{{ user.email }}</p>
                        <p class="text-sm text-muted">{{ user.phone }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="user.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-canvas text-muted'">
                        {{ user.is_active ? 'نشط' : 'غير نشط' }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line pt-3 text-sm">
                    <span class="text-muted">{{ user.roles?.map((role) => roleLabel(role.name, role.label)).join(' · ') }}</span>
                    <span class="flex flex-wrap items-center gap-3">
                        <button
                            v-if="user.can_update"
                            type="button"
                            class="font-semibold text-navy hover:text-gold"
                            @click="startEdit(user)"
                        >
                            تعديل
                        </button>
                        <button
                            v-if="user.can_delete"
                            type="button"
                            class="font-semibold text-red-700"
                            @click="removeUser(user)"
                        >
                            حذف
                        </button>
                    </span>
                </div>
            </article>
        </div>
        <EmptyState v-else title="لا يوجد موظفون" text="اضغط موظف جديد لإضافة أول حساب." />
        <Pagination :links="staff.links" />
    </div>

    <UiModal
        :open="createOpen"
        title="موظف جديد"
        text="أنشئ حساباً بدور واضح: إدارة، مركز اتصال، توزيع، أو مدير متجر."
        @close="closeCreate"
    >
        <form id="staff-create-form" class="space-y-4" @submit.prevent="saveCreate">
            <UiInput v-model="form.name" label="الاسم" :error="form.errors.name" />
            <UiInput v-model="form.email" type="email" label="البريد" :error="form.errors.email" />
            <UiInput v-model="form.phone" label="الهاتف" :error="form.errors.phone" />
            <UiInput v-model="form.password" type="password" label="كلمة المرور" :error="form.errors.password" />
            <UiSelect v-model="form.role" label="الدور" :error="form.errors.role">
                <option v-for="item in roles" :key="item.id" :value="item.name">{{ roleLabel(item.name, item.label) }}</option>
            </UiSelect>
        </form>
        <template #footer>
            <UiButton variant="outline" type="button" @click="closeCreate">إلغاء</UiButton>
            <UiButton type="submit" form="staff-create-form" :disabled="form.processing">إضافة موظف</UiButton>
        </template>
    </UiModal>

    <UiModal
        :open="editOpen"
        :title="editingUser ? `تعديل ${editingUser.name}` : 'تعديل الموظف'"
        text="غيّر البيانات أو الدور أو أعد تعيين كلمة المرور."
        @close="closeEdit"
    >
        <form v-if="editingUser" id="staff-edit-form" class="space-y-4" @submit.prevent="saveEdit">
            <UiInput v-model="editForm.name" label="الاسم" :error="editForm.errors.name" />
            <UiInput v-model="editForm.email" type="email" label="البريد" :error="editForm.errors.email" />
            <UiInput v-model="editForm.phone" label="الهاتف" :error="editForm.errors.phone" />
            <UiInput v-model="editForm.password" type="password" label="كلمة مرور جديدة (اختياري)" :error="editForm.errors.password" />
            <UiSelect v-if="!roleIsLocked" v-model="editForm.role" label="الدور" :error="editForm.errors.role">
                <option v-for="item in roles" :key="item.id" :value="item.name">{{ roleLabel(item.name, item.label) }}</option>
            </UiSelect>
            <p v-else class="text-sm text-muted">لا يمكن تغيير دور هذا الحساب من هنا.</p>
            <ToggleField v-model="editForm.is_active" label="الحساب نشط" text="الحساب غير النشط لا يستطيع تسجيل الدخول." />
        </form>
        <template #footer>
            <UiButton variant="outline" type="button" @click="closeEdit">إلغاء</UiButton>
            <UiButton type="submit" form="staff-edit-form" :disabled="editForm.processing">حفظ التعديل</UiButton>
        </template>
    </UiModal>
</template>
