<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '../Components/AppLogo.vue';
import FlashMessage from '../Components/FlashMessage.vue';

const page = usePage();
const brand = computed(() => page.props.platform?.brand || {});
const brandName = computed(() => brand.value.name_ar || page.props.appName || 'توسيلا');
</script>

<template>
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="app-page-enter customer-surface w-full max-w-md p-6 sm:p-8" style="box-shadow: var(--shadow-card)">
            <div class="mb-6">
                <Link href="/" class="mb-5 inline-flex items-center gap-2.5">
                    <AppLogo class="size-11 rounded-[1.15rem]" />
                    <span class="text-xl font-extrabold tracking-tight text-navy">{{ brandName }}</span>
                </Link>
                <p class="text-[11px] font-bold tracking-[0.14em] text-gold">{{ brand.name_en || 'Towseela' }}</p>
                <h2 class="mt-1.5 text-2xl font-extrabold tracking-tight text-navy">مرحباً بك</h2>
            </div>
            <FlashMessage />
            <slot />
        </div>
    </div>
</template>
