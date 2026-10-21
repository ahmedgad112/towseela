<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import FlashMessage from '../Components/FlashMessage.vue';
import AppIcon from '../Components/AppIcon.vue';
import { initial } from '../workspace';

const page = usePage();
const logout = useForm({});
</script>

<template>
    <div class="min-h-screen bg-canvas pb-24">
        <header class="sticky top-0 z-20 bg-navy px-4 py-4 text-white">
            <div class="mx-auto flex max-w-md items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-2xl bg-gold text-sm font-black text-navy">ت</span>
                    <div>
                        <p class="text-[11px] font-semibold text-gold">بوابة المندوب</p>
                        <h1 class="text-lg font-bold leading-none">توسيلا</h1>
                    </div>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs">
                    <span class="grid size-6 place-items-center rounded-full bg-gold font-bold text-navy">{{ initial(page.props.auth.user?.name) }}</span>
                    {{ page.props.auth.user?.name }}
                </span>
            </div>
        </header>
        <main class="mx-auto max-w-md space-y-4 px-4 py-5">
            <FlashMessage />
            <slot />
        </main>
        <nav class="fixed inset-x-0 bottom-0 z-20 border-t border-line bg-white/95 backdrop-blur">
            <div class="mx-auto grid max-w-md grid-cols-3">
                <Link href="/driver" class="flex min-h-16 flex-col items-center justify-center gap-1 text-[11px]" :class="page.url === '/driver' ? 'text-navy' : 'text-muted'">
                    <span class="grid size-8 place-items-center rounded-full" :class="page.url === '/driver' ? 'bg-gold text-navy' : ''">
                        <AppIcon name="truck" />
                    </span>
                    الطلبات
                </Link>
                <Link href="/driver/history" class="flex min-h-16 flex-col items-center justify-center gap-1 text-[11px]" :class="page.url.startsWith('/driver/history') ? 'text-navy' : 'text-muted'">
                    <span class="grid size-8 place-items-center rounded-full" :class="page.url.startsWith('/driver/history') ? 'bg-gold text-navy' : ''">
                        <AppIcon name="history" />
                    </span>
                    السجل
                </Link>
                <button class="flex min-h-16 flex-col items-center justify-center gap-1 text-[11px] text-muted" @click="logout.post('/logout')">
                    <span class="grid size-8 place-items-center rounded-full">
                        <AppIcon name="logout" />
                    </span>
                    خروج
                </button>
            </div>
        </nav>
    </div>
</template>
