<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import FlashMessage from '../Components/FlashMessage.vue';
import AppIcon from '../Components/AppIcon.vue';
import AppLogo from '../Components/AppLogo.vue';
import { initial } from '../workspace';

const page = usePage();
</script>

<template>
    <div class="min-h-screen bg-canvas pb-24">
        <header class="sticky top-0 z-20 bg-navy px-4 py-4 text-white">
            <div class="mx-auto flex max-w-md items-center justify-between">
                <div class="flex items-center gap-3">
                    <AppLogo class="size-10 rounded-2xl ring-1 ring-white/10" />
                    <div>
                        <p class="text-[11px] font-bold tracking-[0.12em] text-gold">بوابة المندوب</p>
                        <h1 class="text-lg font-extrabold leading-none tracking-tight">توسيلا</h1>
                    </div>
                </div>
                <Link href="/profile" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium hover:bg-white/15">
                    <span class="grid size-6 place-items-center rounded-full bg-gold font-bold text-navy">{{ initial(page.props.auth.user?.name) }}</span>
                    {{ page.props.auth.user?.name }}
                </Link>
            </div>
        </header>
        <main class="app-page-enter dashboard-stack mx-auto max-w-md px-3 py-4 sm:px-4 sm:py-5">
            <FlashMessage />
            <slot />
        </main>
        <nav class="fixed inset-x-0 bottom-0 z-20 border-t border-line/80 bg-white/92 backdrop-blur-xl">
            <div class="mx-auto grid max-w-md grid-cols-3">
                <Link href="/driver" class="flex min-h-16 flex-col items-center justify-center gap-1 text-[11px] font-semibold" :class="page.url === '/driver' ? 'text-navy' : 'text-muted'">
                    <span class="grid size-9 place-items-center rounded-2xl transition" :class="page.url === '/driver' ? 'bg-gold text-navy shadow-[0_8px_16px_rgba(214,181,104,0.35)]' : ''">
                        <AppIcon name="truck" size="sm" />
                    </span>
                    الطلبات
                </Link>
                <Link href="/driver/history" class="flex min-h-16 flex-col items-center justify-center gap-1 text-[11px] font-semibold" :class="page.url.startsWith('/driver/history') ? 'text-navy' : 'text-muted'">
                    <span class="grid size-9 place-items-center rounded-2xl transition" :class="page.url.startsWith('/driver/history') ? 'bg-gold text-navy shadow-[0_8px_16px_rgba(214,181,104,0.35)]' : ''">
                        <AppIcon name="history" size="sm" />
                    </span>
                    السجل
                </Link>
                <Link href="/profile" class="flex min-h-16 flex-col items-center justify-center gap-1 text-[11px] font-semibold" :class="page.url.startsWith('/profile') ? 'text-navy' : 'text-muted'">
                    <span class="grid size-9 place-items-center rounded-2xl transition" :class="page.url.startsWith('/profile') ? 'bg-gold text-navy shadow-[0_8px_16px_rgba(214,181,104,0.35)]' : ''">
                        <AppIcon name="user" size="sm" />
                    </span>
                    حسابي
                </Link>
            </div>
        </nav>
    </div>
</template>
