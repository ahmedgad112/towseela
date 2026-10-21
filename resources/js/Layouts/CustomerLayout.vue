<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import FlashMessage from '../Components/FlashMessage.vue';
import AppIcon from '../Components/AppIcon.vue';
import GpsPrompt from '../Components/GpsPrompt.vue';
import {
    hasPromptedGps,
    markGpsPrompted,
    readStoredLocation,
    requestBrowserLocation,
    reverseGeocode,
    writeStoredLocation,
} from '../customerLocation';

const page = usePage();
const cartCount = computed(() => page.props.cart?.count || 0);
const items = [
    { href: '/', label: 'الرئيسية', icon: 'home', match: (url) => url === '/' },
    { href: '/search', label: 'البحث', icon: 'search', match: (url) => url.startsWith('/search') },
    { href: '/orders', label: 'طلباتي', icon: 'bag', match: (url) => url.startsWith('/orders') },
    { href: '/account', label: 'حسابي', icon: 'user', match: (url) => url.startsWith('/account') || url.startsWith('/addresses') },
];

const promptOpen = ref(false);
const locating = ref(false);
const promptError = ref('');
const detectedLocation = ref(readStoredLocation());

const defaultAddress = computed(() => page.props.customerLocation?.defaultAddress || null);
const hasAddress = computed(() => Boolean(page.props.customerLocation?.hasAddress));
const isCustomer = computed(() => Boolean(page.props.auth.user) && (page.props.auth.roles || []).includes('customer'));
const locationLabel = computed(() => defaultAddress.value?.line1 || detectedLocation.value?.line1 || 'تحديد الموقع');

const enableGps = async () => {
    locating.value = true;
    promptError.value = '';

    try {
        const coords = await requestBrowserLocation();
        const place = await reverseGeocode(coords.lat, coords.lng);
        writeStoredLocation(place);
        detectedLocation.value = place;
    } catch (exception) {
        promptError.value = exception.message;
        markGpsPrompted();
    } finally {
        locating.value = false;
    }
};

const dismissPrompt = () => {
    markGpsPrompted();
    promptOpen.value = false;
};

onMounted(() => {
    if (hasAddress.value || detectedLocation.value || hasPromptedGps()) {
        return;
    }

    promptOpen.value = true;
});
</script>

<template>
    <div class="min-h-screen bg-canvas pb-24">
        <header class="sticky top-0 z-20 border-b border-white/10 bg-navy/95 text-white backdrop-blur">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
                <Link href="/" class="flex items-center gap-2">
                    <span class="grid size-9 place-items-center rounded-xl bg-gold text-sm font-black text-navy">ت</span>
                    <span>
                        <span class="block text-base font-bold leading-none">توسيلا</span>
                        <span class="text-[11px] text-white/60">توصيل احترافي</span>
                    </span>
                </Link>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="isCustomer"
                        href="/addresses"
                        class="flex max-w-36 items-center gap-1 truncate rounded-full bg-white/10 px-3 py-1.5 text-xs text-gold sm:max-w-52"
                    >
                        <AppIcon name="pin" />
                        <span class="truncate">{{ locationLabel }}</span>
                    </Link>
                    <button
                        v-else
                        type="button"
                        class="flex max-w-36 items-center gap-1 truncate rounded-full bg-white/10 px-3 py-1.5 text-xs text-gold sm:max-w-52"
                        @click="promptOpen = true"
                    >
                        <AppIcon name="pin" />
                        <span class="truncate">{{ locationLabel }}</span>
                    </button>
                    <Link
                        v-if="!page.props.auth.user"
                        href="/login"
                        class="rounded-full border border-white/15 px-3 py-1.5 text-sm text-gold"
                    >
                        دخول
                    </Link>
                    <Link
                        v-else
                        href="/account"
                        class="hidden max-w-32 truncate rounded-full bg-white/10 px-3 py-1.5 text-sm sm:block"
                    >
                        {{ page.props.auth.user.name }}
                    </Link>
                    <Link
                        href="/cart"
                        class="relative grid size-10 place-items-center rounded-full bg-gold text-navy"
                    >
                        <AppIcon name="cart" />
                        <span
                            v-if="cartCount"
                            class="absolute -top-1 -start-1 grid min-w-5 place-items-center rounded-full bg-navy px-1 text-[10px] font-bold text-gold"
                        >
                            {{ cartCount }}
                        </span>
                    </Link>
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-5xl space-y-5 px-4 py-5">
            <FlashMessage />
            <slot />
        </main>
        <nav class="fixed inset-x-0 bottom-0 z-20 border-t border-line bg-white/95 backdrop-blur">
            <div class="mx-auto grid max-w-5xl grid-cols-4">
                <Link
                    v-for="item in items"
                    :key="item.href"
                    :href="item.href"
                    class="flex min-h-16 flex-col items-center justify-center gap-1 text-[11px]"
                    :class="item.match(page.url) ? 'text-navy' : 'text-muted'"
                >
                    <span
                        class="grid size-8 place-items-center rounded-full"
                        :class="item.match(page.url) ? 'bg-gold text-navy' : 'bg-transparent'"
                    >
                        <AppIcon :name="item.icon" />
                    </span>
                    {{ item.label }}
                </Link>
            </div>
        </nav>
        <GpsPrompt
            :open="promptOpen"
            :locating="locating"
            :error="promptError"
            :location="detectedLocation"
            :is-customer="isCustomer"
            :has-address="hasAddress"
            @enable="enableGps"
            @later="dismissPrompt"
            @close="promptOpen = false"
        />
    </div>
</template>
