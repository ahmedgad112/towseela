<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '../../Layouts/AuthLayout.vue';
import UiButton from '../../Components/UiButton.vue';
import UiInput from '../../Components/UiInput.vue';

defineOptions({ layout: AuthLayout });

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});
</script>

<template>
    <form class="space-y-4" @submit.prevent="form.post('/register')">
        <UiInput v-model="form.name" label="الاسم" :error="form.errors.name" />
        <UiInput v-model="form.email" type="email" label="البريد الإلكتروني" :error="form.errors.email" />
        <UiInput v-model="form.phone" label="رقم الهاتف" :error="form.errors.phone" />
        <UiInput v-model="form.password" type="password" label="كلمة المرور" :error="form.errors.password" />
        <UiInput v-model="form.password_confirmation" type="password" label="تأكيد كلمة المرور" />
        <UiButton type="submit" class="w-full" :disabled="form.processing">إنشاء الحساب</UiButton>
        <Link href="/login" class="block text-center text-sm font-medium text-navy">لديك حساب؟ سجل الدخول</Link>
    </form>
</template>
