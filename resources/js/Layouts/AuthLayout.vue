<script setup>
import { Link } from '@inertiajs/vue3';
import FlashMessage from '../Components/FlashMessage.vue';
</script>

<template>
    <div class="min-h-screen bg-navy lg:grid lg:grid-cols-2">
        <section class="relative hidden overflow-hidden px-10 py-12 text-white lg:flex lg:flex-col lg:justify-center">
            <div class="pointer-events-none absolute inset-0 opacity-25" style="background-image: radial-gradient(circle at 18% 20%, #d6b568 0, transparent 26%), radial-gradient(circle at 86% 78%, #ffffff 0, transparent 20%)" />
            <div class="relative mx-auto w-full max-w-lg">
                <Link href="/" class="inline-flex items-center gap-2">
                    <span class="grid size-11 place-items-center rounded-2xl bg-gold text-lg font-black text-navy">ت</span>
                    <span class="text-2xl font-bold">توسيلا</span>
                </Link>
                <h1 class="mt-10 text-4xl font-bold leading-snug">منصة توصيل عربية بهوية واضحة وتشغيل احترافي</h1>
                <p class="mt-4 text-sm leading-7 text-white/70">اطلب من المطاعم والمتاجر، وتابع حالة الطلب بدقة، مع دفع عند الاستلام حتى يتم ربط بوابة الدفع.</p>
            </div>
        </section>
        <section class="flex min-h-screen items-center justify-center bg-canvas px-4 py-10">
            <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-[var(--shadow-card)] sm:p-8">
                <div class="mb-6">
                    <Link href="/" class="mb-4 inline-flex items-center gap-2 lg:hidden">
                        <span class="grid size-10 place-items-center rounded-2xl bg-gold font-black text-navy">ت</span>
                        <span class="text-xl font-bold text-navy">توسيلا</span>
                    </Link>
                    <p class="text-xs font-semibold tracking-wide text-gold">Towseela</p>
                    <h2 class="mt-1 text-2xl font-bold text-navy">مرحباً بك</h2>
                </div>
                <FlashMessage />
                <slot />
            </div>
        </section>
    </div>
</template>
