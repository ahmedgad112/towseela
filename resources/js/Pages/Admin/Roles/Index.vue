<script setup>
import { computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { canDo } from '../../../workspace';

defineOptions({ layout: AdminLayout });
const props = defineProps({
    roles: { type: Array, default: () => [] },
    permissions: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const page = usePage();
const auth = computed(() => page.props.auth);
const groups = computed(() => {
    const grouped = {};
    props.permissions.forEach((permission) => {
        grouped[permission.group] ??= [];
        grouped[permission.group].push(permission);
    });
    return grouped;
});

const createRole = useForm({ name: '', label: '', permissions: [] });
const createPermission = useForm({ name: '', label: '', group: 'أخرى' });
const forms = Object.fromEntries(props.roles.map((role) => [
    role.id,
    useForm({ label: role.label, permissions: [...role.permissions] }),
]));

const toggleGroup = (roleId, names) => {
    const selected = forms[roleId].permissions;
    const allOn = names.every((name) => selected.includes(name));
    forms[roleId].permissions = allOn
        ? selected.filter((name) => !names.includes(name))
        : [...new Set([...selected, ...names])];
};

const removeRole = (role) => {
    if (window.confirm(`حذف دور ${role.label}؟`)) {
        router.delete(`/admin/roles/${role.id}`);
    }
};

const removePermission = (permission) => {
    if (window.confirm(`حذف صلاحية ${permission.label}؟`)) {
        router.delete(`/admin/permissions/${permission.id}`);
    }
};
</script>

<template>
    <PageHeader title="الأدوار والصلاحيات" text="أنشئ أدواراً مخصصة، حدّد كل زر يظهر في المنصة، واحذف الأدوار غير المستخدمة." />

    <div class="grid gap-4 xl:grid-cols-2">
        <form v-if="can.createRole || canDo(auth, 'roles.create')" class="app-card space-y-3 p-5" @submit.prevent="createRole.post('/admin/roles')">
            <h3 class="font-bold text-navy">دور جديد</h3>
            <UiInput v-model="createRole.name" label="المعرف الإنجليزي" placeholder="shift_supervisor" :error="createRole.errors.name" />
            <UiInput v-model="createRole.label" label="الاسم الظاهر" placeholder="مشرف وردية" :error="createRole.errors.label" />
            <UiButton type="submit" :disabled="createRole.processing">إنشاء الدور</UiButton>
        </form>
        <form v-if="can.createPermission || canDo(auth, 'permissions.create')" class="app-card space-y-3 p-5" @submit.prevent="createPermission.post('/admin/permissions')">
            <h3 class="font-bold text-navy">صلاحية جديدة</h3>
            <UiInput v-model="createPermission.name" label="المعرف" placeholder="orders.print" :error="createPermission.errors.name" />
            <UiInput v-model="createPermission.label" label="الاسم الظاهر" placeholder="طباعة الطلب" :error="createPermission.errors.label" />
            <UiInput v-model="createPermission.group" label="المجموعة" placeholder="الطلبات" :error="createPermission.errors.group" />
            <UiButton type="submit" :disabled="createPermission.processing">إنشاء الصلاحية</UiButton>
        </form>
    </div>

    <section v-if="permissions.some((permission) => !permission.is_system)" class="app-card p-5">
        <h3 class="mb-3 font-bold text-navy">صلاحيات مخصصة</h3>
        <p v-for="permission in permissions.filter((item) => !item.is_system)" :key="permission.id" class="flex items-center justify-between gap-3 border-b border-line py-2 text-sm last:border-0">
            <span>
                <span class="font-semibold text-navy">{{ permission.label }}</span>
                <span class="text-muted"> · {{ permission.name }} · {{ permission.group }}</span>
            </span>
            <button
                v-if="can.deletePermission || canDo(auth, 'permissions.delete')"
                class="text-sm text-red-700"
                @click="removePermission(permission)"
            >
                حذف
            </button>
        </p>
    </section>

    <section v-for="role in roles" :key="role.id" class="app-card p-5">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-navy">{{ role.label }}</h3>
                <p class="text-sm text-muted">{{ role.name }} · {{ role.users_count }} مستخدم</p>
            </div>
            <span v-if="role.is_system" class="rounded-full bg-navy/5 px-3 py-1 text-xs font-semibold text-navy">دور نظام</span>
            <UiButton
                v-else-if="can.deleteRole || canDo(auth, 'roles.delete')"
                variant="danger"
                @click="removeRole(role)"
            >
                حذف الدور
            </UiButton>
        </div>

        <p v-if="role.name === 'super_admin'" class="text-sm leading-6 text-muted">مدير المنصة يملك كل الأزرار تلقائياً ولا يمكن تعديل هذا الدور أو حذفه.</p>
        <form v-else class="space-y-4" @submit.prevent="forms[role.id].patch(`/admin/roles/${role.id}`)">
            <UiInput v-if="!role.is_system" v-model="forms[role.id].label" label="الاسم الظاهر" />
            <div v-for="(items, group) in groups" :key="group" class="rounded-2xl bg-canvas p-4">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h4 class="font-semibold text-navy">{{ group }}</h4>
                    <button type="button" class="text-xs font-semibold text-gold" @click="toggleGroup(role.id, items.map((item) => item.name))">تحديد الكل</button>
                </div>
                <div class="grid gap-2 md:grid-cols-2">
                    <label v-for="permission in items" :key="permission.id" class="flex items-center gap-2 text-sm">
                        <input v-model="forms[role.id].permissions" type="checkbox" :value="permission.name" class="accent-gold">
                        <span>
                            <span class="block text-navy">{{ permission.label }}</span>
                            <span class="text-[11px] text-muted">{{ permission.name }}</span>
                        </span>
                    </label>
                </div>
            </div>
            <UiButton v-if="can.updateRole || canDo(auth, 'roles.update')" type="submit" :disabled="forms[role.id].processing">حفظ صلاحيات الدور</UiButton>
        </form>
    </section>
</template>
