<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthLayout from '../../Layouts/AuthLayout.vue';
import UiButton from '../../Components/UiButton.vue';
import UiInput from '../../Components/UiInput.vue';

defineOptions({ layout: AuthLayout });

const page = usePage();
const registrationEnabled = computed(() => page.props.platform?.features?.customer_registration_enabled !== false);
const googleAuthEnabled = computed(() => page.props.platform?.google_auth_enabled === true);

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

        <template v-if="googleAuthEnabled">
            <div class="relative py-1 text-center">
                <span class="relative z-10 bg-white px-3 text-xs font-medium text-muted">أو</span>
                <span class="absolute inset-x-0 top-1/2 h-px -translate-y-1/2 bg-line" />
            </div>
            <UiButton href="/auth/google/redirect" variant="outline" class="w-full gap-2">
                <svg class="size-4 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#EA4335" d="M12 10.2v3.6h5.1c-.2 1.2-.9 2.3-1.9 3l3.1 2.4c1.8-1.7 2.9-4.1 2.9-7 0-.7-.1-1.3-.2-1.9H12z" />
                    <path fill="#34A853" d="M6.6 14.3 5.8 14.9l-2.7 2.1C4.8 20.1 8.1 22 12 22c2.7 0 5-.9 6.7-2.4l-3.1-2.4c-.9.6-2 1-3.6 1-2.8 0-5.1-1.9-5.9-4.4z" />
                    <path fill="#4A90E2" d="M3.1 7.1C2.4 8.5 2 10.2 2 12s.4 3.5 1.1 4.9l3.5-2.7C6.2 13.4 6 12.7 6 12s.2-1.4.6-2.1L3.1 7.1z" />
                    <path fill="#FBBC05" d="M12 5.8c1.5 0 2.8.5 3.8 1.5l2.8-2.8C16.9 2.9 14.7 2 12 2 8.1 2 4.8 3.9 3.1 7.1l3.5 2.7C7 7.7 9.2 5.8 12 5.8z" />
                </svg>
                المتابعة عبر Google
            </UiButton>
        </template>

        <Link v-if="registrationEnabled" href="/register" class="block text-center text-sm font-medium text-navy">إنشاء حساب جديد</Link>
    </form>
</template>
