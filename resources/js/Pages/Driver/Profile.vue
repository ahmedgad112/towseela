<script setup>
import { useForm } from '@inertiajs/vue3';
import DriverLayout from '../../Layouts/DriverLayout.vue';
import AccountProfileForm from '../../Components/AccountProfileForm.vue';
import AppIcon from '../../Components/AppIcon.vue';

defineOptions({ layout: DriverLayout });
defineProps({
    user: { type: Object, required: true },
});

const logout = useForm({});
</script>

<template>
    <section class="rounded-3xl bg-navy p-5 text-white">
        <p class="text-[11px] font-semibold text-gold">بوابة المندوب</p>
        <h2 class="mt-1 text-xl font-bold">{{ user.name }}</h2>
        <p class="text-sm text-white/70">{{ user.phone || user.email }}</p>
    </section>
    <AccountProfileForm :user="user" profile-url="/profile" password-url="/profile/password" />
    <button class="inline-flex items-center gap-2 text-sm text-muted" @click="logout.post('/logout')">
        <AppIcon name="logout" />
        تسجيل الخروج
    </button>
</template>
