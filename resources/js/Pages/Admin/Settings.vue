<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import UiButton from '../../Components/UiButton.vue';
import UiInput from '../../Components/UiInput.vue';
import PageHeader from '../../Components/PageHeader.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ settings: { type: Object, required: true } });
const form = useForm({ support_phone: props.settings.support_phone });
</script>

<template>
    <PageHeader title="إعدادات المنصة" text="بيانات الدعم الظاهرة للفريق والعملاء." />
    <form class="app-card max-w-lg space-y-4 p-5" @submit.prevent="form.patch('/admin/settings')">
        <UiInput v-model="form.support_phone" label="هاتف الدعم" />
        <p class="text-sm leading-6 text-muted">إثبات التسليم الإلزامي يُضبط عبر TOWSEELA_REQUIRE_POD_NOTE في البيئة.</p>
        <UiButton type="submit">حفظ الإعدادات</UiButton>
    </form>
</template>
