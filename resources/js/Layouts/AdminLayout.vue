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
const currentTitle = computed(() => {
    if (page.url.startsWith('/profile')) {
        return 'حسابي';
    }

    return visibleLinks.value.find((item) => item.match(page.url))?.label || workspace.value.title;
});
</script>

<template>
    <div class="min-h-screen bg-canvas lg:grid lg:grid-cols-[17.5rem_1fr]">
        <div v-if="open" class="fixed inset-0 z-30 bg-navy/45 backdrop-blur-[2px] transition lg:hidden" @click="open = false" />
        <aside
            class="app-sidebar fixed inset-y-0 start-0 z-40 flex w-[17.5rem] flex-col text-white transition duration-300 ease-out lg:static lg:w-auto lg:translate-x-0"
            :class="open ? 'translate-x-0' : 'max-lg:ltr:-translate-x-full max-lg:rtl:translate-x-full'"
        >
            <div class="relative flex items-center justify-between px-4 py-5">
                <Link href="/admin" class="group flex min-w-0 items-center gap-3" @click="open = false">
                    <AppLogo class="size-11 rounded-2xl shadow-[0_8px_20px_rgba(0,0,0,0.25)] ring-1 ring-white/10 transition group-hover:scale-[1.03]" />
                    <span class="min-w-0">
                        <span class="block truncate text-[1.05rem] font-extrabold leading-none tracking-tight">{{ page.props.platform?.brand?.name_ar || page.props.appName || 'توسيلا' }}</span>
                        <span class="mt-1 block text-[11px] font-medium text-white/55">{{ workspace.title }}</span>
                    </span>
                </Link>
                <button class="grid size-9 place-items-center rounded-xl bg-white/10 transition hover:bg-white/15 lg:hidden" @click="open = false">
                    <AppIcon name="close" size="sm" />
                </button>
            </div>

            <nav class="app-sidebar-scroll flex-1 overflow-y-auto px-3 pb-5">
                <section
                    v-for="(section, index) in sections"
                    :key="section.title"
                    class="space-y-1"
                    :class="index > 0 ? 'mt-5 border-t border-white/8 pt-4' : ''"
                >
                    <p class="px-3 pb-1.5 text-[10px] font-bold tracking-[0.14em] text-gold/75 uppercase">
                        {{ section.title }}
                    </p>
                    <Link
                        v-for="link in section.items"
                        :key="link.href"
                        :href="link.href"
                        class="app-sidebar-link group"
                        :class="{ 'is-active': link.match(page.url) }"
                        @click="open = false"
                    >
                        <span class="app-sidebar-icon">
                            <AppIcon :name="link.icon" size="sm" />
                        </span>
                        <span class="truncate font-medium">{{ link.label }}</span>
                    </Link>
                </section>
            </nav>

            <div class="border-t border-white/10 p-3">
                <Link href="/profile" class="flex items-center gap-3 rounded-2xl bg-white/8 px-3 py-3 transition hover:bg-white/12" @click="open = false">
                    <span class="grid size-10 place-items-center rounded-2xl bg-gradient-to-br from-gold to-gold-hover font-bold text-navy shadow-[0_6px_16px_rgba(214,181,104,0.35)]">{{ initial(page.props.auth.user?.name) }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">{{ page.props.auth.user?.name }}</p>
                        <p class="text-[11px] text-white/55">{{ roleLabel(role) }} · حسابي</p>
                    </div>
                </Link>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="sticky top-0 z-20 flex items-center justify-between gap-2.5 border-b border-line/80 bg-white/90 px-3 py-2.5 backdrop-blur-xl sm:gap-3 sm:px-5 sm:py-3 lg:px-6">
                <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
                    <button class="grid size-9 place-items-center rounded-xl border border-line bg-white text-navy transition hover:border-gold/60 hover:bg-canvas sm:size-10 sm:rounded-2xl lg:hidden" @click="open = true">
                        <AppIcon name="menu" size="sm" />
                    </button>
                    <div class="min-w-0">
                        <p class="dashboard-eyebrow">{{ workspace.eyebrow }}</p>
                        <h1 class="truncate text-base font-extrabold tracking-tight text-navy sm:text-lg">{{ currentTitle }}</h1>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                    <Link
                        v-if="canDispatch"
                        href="/dispatch"
                        class="hidden items-center gap-1.5 rounded-full border border-line px-3 py-1.5 text-[13px] font-medium text-navy transition hover:border-gold hover:bg-canvas sm:inline-flex sm:text-sm"
                    >
                        <AppIcon name="dispatch" size="sm" />
                        غرفة التوزيع
                    </Link>
                    <Link href="/profile" class="hidden max-w-36 truncate rounded-full bg-canvas px-3 py-1.5 text-[13px] font-medium text-navy transition hover:bg-gold/15 md:inline md:text-sm">{{ page.props.auth.user?.name }}</Link>
                    <button class="inline-flex items-center gap-1 rounded-full px-2.5 py-1.5 text-[13px] font-medium text-muted transition hover:bg-canvas hover:text-navy sm:px-3 sm:text-sm" @click="logout.post('/logout')">
                        <AppIcon name="logout" size="sm" />
                        خروج
                    </button>
                </div>
            </header>
            <main class="app-page-enter dashboard-stack p-3 sm:p-5 lg:p-6">
                <FlashMessage />
                <slot />
            </main>
        </div>
    </div>
</template>
