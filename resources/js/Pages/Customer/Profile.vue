<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';
import AccountProfileForm from '../../Components/AccountProfileForm.vue';
import AppIcon from '../../Components/AppIcon.vue';
import CustomerPageHeader from '../../Components/CustomerPageHeader.vue';
import { canDo } from '../../workspace';

defineOptions({ layout: CustomerLayout });
defineProps({
    user: { type: Object, required: true },
});

const page = usePage();
const showAddresses = computed(() => canDo(page.props.auth, 'customer.addresses'));
const logout = useForm({});
</script>

<template>
    <CustomerPageHeader eyebrow="الحساب" title="حسابي" text="بياناتك الشخصية وإعدادات الوصول." />

    <section class="relative overflow-hidden rounded-[1.5rem] bg-navy p-5 text-white sm:p-6">
        <div
            class="pointer-events-none absolute inset-0 opacity-40"
            style="background-image: radial-gradient(circle at 90% 10%, rgba(214,181,104,0.35), transparent 35%)"
        />
        <div class="relative flex items-center gap-4 sm:gap-5">
            <span class="grid size-16 shrink-0 place-items-center rounded-[1.25rem] bg-gold text-2xl font-black text-navy shadow-[0_10px_24px_rgba(214,181,104,0.4)]">
                {{ user.name?.charAt(0) }}
            </span>
            <div class="min-w-0 space-y-1">
                <h2 class="truncate text-xl font-extrabold tracking-tight sm:text-2xl">{{ user.name }}</h2>
                <p class="truncate text-sm text-white/70">{{ user.phone || user.email }}</p>
            </div>
        </div>
    </section>

    <AccountProfileForm :user="user" profile-url="/account" password-url="/account/password" />

    <Link
        v-if="showAddresses"
        href="/addresses"
        class="customer-surface flex items-center justify-between gap-3 p-4 transition hover:border-gold/50 sm:p-5"
    >
        <span class="flex items-center gap-3 font-bold text-navy">
            <span class="grid size-11 place-items-center rounded-2xl bg-navy text-gold">
                <AppIcon name="pin" size="sm" />
            </span>
            العناوين المحفوظة
        </span>
        <span class="text-[13px] font-semibold text-muted">إدارة</span>
    </Link>

    <button
        class="inline-flex items-center gap-2 self-start rounded-full px-1 py-1 text-sm font-semibold text-muted transition hover:text-navy"
        @click="logout.post('/logout')"
    >
        <AppIcon name="logout" size="sm" />
        تسجيل الخروج
    </button>
</template>
