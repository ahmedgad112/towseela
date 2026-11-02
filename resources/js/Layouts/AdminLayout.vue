<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FlashMessage from '../Components/FlashMessage.vue';
import AppIcon from '../Components/AppIcon.vue';
import AppLogo from '../Components/AppLogo.vue';
import { initial, primaryRole, roleLabel, workspaceFor } from '../workspace';

const page = usePage();
const logout = useForm({});
const open = ref(false);
const roles = computed(() => page.props.auth.roles || []);
const workspace = computed(() => workspaceFor(roles.value));
const role = computed(() => primaryRole(roles.value));
const can = (permission) => roles.value.includes('super_admin') || page.props.auth.permissions.includes(permission);
const canDispatch = computed(() => roles.value.some((item) => ['super_admin', 'admin', 'call_center_agent', 'dispatcher'].includes(item)));

const sections = computed(() => [
    {
        title: 'نظرة عامة',
        items: [
            { href: '/admin', label: 'لوحة التحكم', icon: 'dashboard', show: can('dashboard.view'), match: (url) => url === '/admin' },
        ],
    },
    {
        title: 'العمليات',
        items: [
            { href: '/admin/orders', label: 'الطلبات', icon: 'orders', show: can('orders.view'), match: (url) => url.startsWith('/admin/orders') },
            { href: '/admin/customers', label: 'العملاء', icon: 'user', show: can('users.view') && role.value !== 'business_manager', match: (url) => url.startsWith('/admin/customers') },
        ],
    },
    {
        title: 'المتاجر',
        items: [
            { href: '/admin/businesses', label: 'المتاجر', icon: 'store', show: can('businesses.view'), match: (url) => url.startsWith('/admin/businesses') },
            { href: '/admin/categories', label: 'التصنيفات', icon: 'tag', show: can('categories.manage'), match: (url) => url.startsWith('/admin/categories') },
            { href: '/admin/coupons', label: 'العروض', icon: 'ticket', show: can('offers.manage'), match: (url) => url.startsWith('/admin/coupons') },
        ],
    },
    {
        title: 'التوصيل',
        items: [
            { href: '/admin/zones', label: 'المناطق', icon: 'map', show: can('delivery_zones.manage'), match: (url) => url.startsWith('/admin/zones') },
            { href: '/admin/drivers', label: 'المناديب', icon: 'truck', show: can('drivers.view'), match: (url) => url.startsWith('/admin/drivers') },
        ],
    },
    {
        title: 'الإدارة',
        items: [
            { href: '/admin/staff', label: 'الموظفون', icon: 'staff', show: can('users.view') && role.value !== 'business_manager', match: (url) => url.startsWith('/admin/staff') },
            { href: '/admin/roles', label: 'الأدوار', icon: 'shield', show: can('roles.view'), match: (url) => url.startsWith('/admin/roles') },
            { href: '/admin/reports', label: 'التقارير', icon: 'chart', show: can('reports.view'), match: (url) => url.startsWith('/admin/reports') },
            { href: '/admin/audit-logs', label: 'سجل التدقيق', icon: 'audit', show: can('audit.view'), match: (url) => url.startsWith('/admin/audit-logs') },
            { href: '/admin/settings', label: 'الإعدادات', icon: 'settings', show: can('settings.manage'), match: (url) => url.startsWith('/admin/settings') },
        ],
    },
].map((section) => ({
    ...section,
    items: section.items.filter((item) => item.show),
})).filter((section) => section.items.length > 0));

const visibleLinks = computed(() => sections.value.flatMap((section) => section.items));
const currentTitle = computed(() => visibleLinks.value.find((item) => item.match(page.url))?.label || workspace.value.title);
</script>

<template>
    <div class="min-h-screen bg-canvas lg:grid lg:grid-cols-[17rem_1fr]">
        <div v-if="open" class="fixed inset-0 z-30 bg-navy/40 lg:hidden" @click="open = false" />
        <aside
            class="fixed inset-y-0 start-0 z-40 flex w-72 flex-col bg-navy text-white transition duration-200 lg:static lg:w-auto lg:translate-x-0"
            :class="open ? 'translate-x-0' : 'max-lg:ltr:-translate-x-full max-lg:rtl:translate-x-full'"
        >
            <div class="flex items-center justify-between px-5 py-5">
                <Link href="/admin" class="flex items-center gap-2" @click="open = false">
                    <AppLogo class="size-10 rounded-2xl" />
                    <span>
                        <span class="block text-lg font-bold leading-none">توسيلا</span>
                        <span class="text-[11px] text-white/60">{{ workspace.title }}</span>
                    </span>
                </Link>
                <button class="grid size-9 place-items-center rounded-xl bg-white/10 lg:hidden" @click="open = false">
                    <AppIcon name="close" />
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto px-3 pb-6">
                <section
                    v-for="(section, index) in sections"
                    :key="section.title"
                    class="space-y-1"
                    :class="index > 0 ? 'mt-4 border-t border-white/10 pt-3' : ''"
                >
                    <p class="px-3 pb-1 text-[11px] font-semibold tracking-wide text-gold/80">
                        {{ section.title }}
                    </p>
                    <Link
                        v-for="link in section.items"
                        :key="link.href"
                        :href="link.href"
                        class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm text-white/75 hover:bg-white/10"
                        :class="{ 'bg-gold text-navy hover:bg-gold': link.match(page.url) }"
                        @click="open = false"
                    >
                        <AppIcon :name="link.icon" />
                        {{ link.label }}
                    </Link>
                </section>
            </nav>
            <div class="border-t border-white/10 p-4">
                <div class="flex items-center gap-3 rounded-2xl bg-white/10 px-3 py-3">
                    <span class="grid size-10 place-items-center rounded-2xl bg-gold font-bold text-navy">{{ initial(page.props.auth.user?.name) }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">{{ page.props.auth.user?.name }}</p>
                        <p class="text-[11px] text-white/60">{{ roleLabel(role) }}</p>
                    </div>
                </div>
            </div>
        </aside>
        <div class="min-w-0">
            <header class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-line bg-white/95 px-4 py-3 backdrop-blur sm:px-6">
                <div class="flex items-center gap-3">
                    <button class="grid size-10 place-items-center rounded-2xl border border-line text-navy lg:hidden" @click="open = true">
                        <AppIcon name="menu" />
                    </button>
                    <div>
                        <p class="text-[11px] font-semibold tracking-wide text-gold">{{ workspace.eyebrow }}</p>
                        <h1 class="text-lg font-bold text-navy">{{ currentTitle }}</h1>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="canDispatch"
                        href="/dispatch"
                        class="hidden rounded-full border border-line px-3 py-1.5 text-sm text-navy hover:border-gold sm:inline-flex"
                    >
                        غرفة التوزيع
                    </Link>
                    <span class="hidden rounded-full bg-canvas px-3 py-1.5 text-sm text-navy md:inline">{{ page.props.auth.user?.name }}</span>
                    <button class="rounded-full px-3 py-1.5 text-sm text-muted hover:text-navy" @click="logout.post('/logout')">خروج</button>
                </div>
            </header>
            <main class="space-y-5 p-4 sm:p-6">
                <FlashMessage />
                <slot />
            </main>
        </div>
    </div>
</template>
