<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FlashMessage from '../Components/FlashMessage.vue';
import AppIcon from '../Components/AppIcon.vue';
import AppLogo from '../Components/AppLogo.vue';
import { initial, primaryRole, roleLabel, workspaceFor } from '../workspace';

const page = usePage();
const logout = useForm({});
const roles = computed(() => page.props.auth.roles || []);
const workspace = computed(() => workspaceFor(roles.value));
const role = computed(() => primaryRole(roles.value));
const canAdmin = computed(() => roles.value.some((item) => ['super_admin', 'admin', 'business_manager'].includes(item)));
const canAssign = computed(() => page.props.auth.permissions.includes('orders.assign_driver'));
</script>

<template>
    <div class="min-h-screen bg-canvas">
        <header class="sticky top-0 z-20 bg-navy text-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6">
                <Link href="/dispatch" class="flex items-center gap-3">
                    <AppLogo class="size-11 rounded-2xl ring-1 ring-white/10" />
                    <span>
                        <span class="block text-lg font-extrabold leading-none tracking-tight">{{ workspace.title }}</span>
                        <span class="text-[11px] font-medium text-white/60">توسيلا · {{ workspace.subtitle }}</span>
                    </span>
                </Link>
                <div class="flex items-center gap-2 text-sm">
                    <Link href="/profile" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 hover:bg-white/15">
                        <span class="grid size-6 place-items-center rounded-full bg-gold text-[11px] font-bold text-navy">{{ initial(page.props.auth.user?.name) }}</span>
                        <span class="max-w-28 truncate">{{ page.props.auth.user?.name }}</span>
                    </Link>
                    <span class="hidden rounded-full bg-gold/15 px-3 py-1.5 text-gold md:inline">{{ roleLabel(role) }}</span>
                    <Link v-if="canAdmin" href="/admin" class="rounded-full bg-white/10 px-3 py-1.5 text-white/85 hover:bg-white/15">الإدارة</Link>
                    <button class="text-gold" @click="logout.post('/logout')">خروج</button>
                </div>
            </div>
            <div class="border-t border-white/10">
                <div class="mx-auto flex max-w-7xl gap-1 px-4 py-2 sm:px-6">
                    <Link
                        href="/dispatch"
                        class="inline-flex min-h-10 items-center gap-2 rounded-2xl px-3 text-sm"
                        :class="page.url === '/dispatch' || page.url.startsWith('/dispatch?') ? 'bg-gold text-navy' : 'text-white/75 hover:bg-white/10'"
                    >
                        <AppIcon :name="canAssign ? 'dispatch' : 'headset'" />
                        {{ canAssign ? 'طابور التوزيع' : 'الطلبات الواردة' }}
                    </Link>
                </div>
            </div>
        </header>
        <main class="app-page-enter dashboard-stack mx-auto max-w-7xl px-3 py-4 sm:px-5 sm:py-5 lg:px-6 lg:py-6">
            <FlashMessage />
            <slot />
        </main>
    </div>
</template>
