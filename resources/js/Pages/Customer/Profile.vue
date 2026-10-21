<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';
import UiButton from '../../Components/UiButton.vue';
import UiInput from '../../Components/UiInput.vue';
import AppIcon from '../../Components/AppIcon.vue';

defineOptions({ layout: CustomerLayout });
const props = defineProps({
    user: { type: Object, required: true },
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone,
});
</script>

<template>
    <section class="flex items-center gap-4 rounded-3xl bg-navy p-5 text-white">
        <span class="grid size-14 place-items-center rounded-2xl bg-gold text-xl font-black text-navy">
            {{ user.name?.charAt(0) }}
        </span>
        <div>
            <h2 class="text-xl font-bold">{{ user.name }}</h2>
            <p class="text-sm text-white/70">{{ user.phone || user.email }}</p>
        </div>
    </section>
    <form class="app-card space-y-3 p-5" @submit.prevent="form.patch('/account')">
        <UiInput v-model="form.name" label="الاسم" :error="form.errors.name" />
        <UiInput v-model="form.email" label="البريد" :error="form.errors.email" />
        <UiInput v-model="form.phone" label="الهاتف" :error="form.errors.phone" />
        <UiButton type="submit" :disabled="form.processing">حفظ التغييرات</UiButton>
    </form>
    <Link href="/addresses" class="app-card flex items-center justify-between p-4">
        <span class="flex items-center gap-2 font-semibold text-navy">
            <AppIcon name="pin" />
            العناوين المحفوظة
        </span>
        <span class="text-sm text-muted">إدارة</span>
    </Link>
    <button class="inline-flex items-center gap-2 text-sm text-muted" @click="useForm({}).post('/logout')">
        <AppIcon name="logout" />
        تسجيل الخروج
    </button>
</template>
