<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '../../Layouts/AuthLayout.vue';
import UiButton from '../../Components/UiButton.vue';
import UiInput from '../../Components/UiInput.vue';

defineOptions({ layout: AuthLayout });

const form = useForm({
    login: '',
    password: '',
    remember: true,
});
</script>

<template>
    <form class="space-y-4" @submit.prevent="form.post('/login')">
        <UiInput v-model="form.login" label="البريد أو رقم الهاتف" :error="form.errors.login" autocomplete="username" />
        <UiInput v-model="form.password" type="password" label="كلمة المرور" :error="form.errors.password" autocomplete="current-password" />
        <label class="flex items-center gap-2 text-sm text-muted">
            <input v-model="form.remember" type="checkbox" class="accent-gold">
            تذكرني
        </label>
        <UiButton type="submit" class="w-full" :disabled="form.processing">دخول</UiButton>
        <Link href="/register" class="block text-center text-sm font-medium text-navy">إنشاء حساب جديد</Link>
    </form>
</template>
