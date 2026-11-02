<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import FlashMessage from '../Components/FlashMessage.vue';
import AppIcon from '../Components/AppIcon.vue';
import AppLogo from '../Components/AppLogo.vue';
import GpsPrompt from '../Components/GpsPrompt.vue';
import { canDo } from '../workspace';
import {
    hasPromptedGps,
    markGpsPrompted,
    readStoredLocation,
    requestBrowserLocation,
    reverseGeocode,
    writeStoredLocation,
} from '../customerLocation';

const page = usePage();
const brand = computed(() => page.props.platform?.brand || {});
const features = computed(() => page.props.platform?.features || {});
const cartCount = computed(() => page.props.cart?.count || 0);
const auth = computed(() => page.props.auth);
const isGuest = computed(() => !auth.value?.user);

const navItems = computed(() => [
    { href: '/', label: 'الرئيسية', icon: 'home', match: (url) => url === '/', enabled: true },
    {
        href: '/search',
        label: 'البحث',
        icon: 'search',
        match: (url) => url.startsWith('/search'),
        enabled: isGuest.value || canDo(auth.value, 'customer.search'),
    },
    {
        href: '/orders',
        label: 'طلباتي',
        icon: 'bag',
        match: (url) => url.startsWith('/orders'),
        enabled: canDo(auth.value, 'customer.orders'),
    },
    {
        href: '/account',
        label: 'حسابي',
        icon: 'user',
        match: (url) => url.startsWith('/account') || url.startsWith('/addresses'),
        enabled: Boolean(auth.value?.user),
    },
].filter((item) => item.enabled));

const promptOpen = ref(false);
const locating = ref(false);
const promptError = ref('');
const detectedLocation = ref(readStoredLocation());

const defaultAddress = computed(() => page.props.customerLocation?.defaultAddress || null);
const hasAddress = computed(() => Boolean(page.props.customerLocation?.hasAddress));
const isCustomer = computed(() => Boolean(auth.value?.user) && (auth.value?.roles || []).includes('customer'));
const locationLabel = computed(() => defaultAddress.value?.line1 || detectedLocation.value?.line1 || 'تحديد الموقع');
const showCart = computed(() => isGuest.value || canDo(auth.value, 'customer.cart'));
const showAddresses = computed(() => canDo(auth.value, 'customer.addresses'));
const brandName = computed(() => brand.value.name_ar || page.props.appName || 'توسيلا');

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
    <div class="min-h-screen pb-[5.5rem] md:pb-0">
        <header class="sticky top-0 z-30 border-b border-white/8 bg-navy/90 text-white shadow-[0_10px_40px_rgba(8,16,32,0.28)] backdrop-blur-2xl">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-2 px-3 py-2.5 sm:gap-3 sm:px-5 sm:py-3 lg:px-6">
                <Link href="/" class="group flex min-w-0 items-center gap-2 sm:gap-3">
                    <AppLogo class="size-9 shrink-0 rounded-[0.95rem] ring-1 ring-white/15 transition duration-300 group-hover:ring-gold/50 sm:size-11 sm:rounded-[1.15rem]" />
                    <span class="min-w-0">
                        <span class="block truncate text-[0.95rem] font-extrabold leading-none tracking-tight sm:text-lg">
                            {{ brandName }}
                        </span>
                        <span class="mt-1 hidden truncate text-[11px] font-medium leading-none text-gold/80 sm:mt-1.5 sm:block">
                            {{ brand.tagline || 'توصيل احترافي' }}
                        </span>
                    </span>
                </Link>

                <nav class="hidden items-center gap-1 md:flex">
                    <Link
                        v-for="item in navItems"
                        :key="`desk-${item.href}`"
                        :href="item.href"
                        class="rounded-full px-3.5 py-2 text-[13px] font-semibold transition"
                        :class="item.match(page.url)
                            ? 'bg-white/12 text-gold'
                            : 'text-white/70 hover:bg-white/8 hover:text-white'"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                    <Link
                        v-if="isCustomer && showAddresses"
                        href="/addresses"
                        class="flex max-w-[7.5rem] items-center gap-1.5 truncate rounded-full border border-white/10 bg-white/8 px-2.5 py-1.5 text-[10px] font-semibold text-gold transition hover:bg-white/12 sm:max-w-52 sm:px-3 sm:py-2 sm:text-xs"
                    >
                        <AppIcon name="pin" size="xs" />
                        <span class="truncate">{{ locationLabel }}</span>
                    </Link>
                    <button
                        v-else-if="!isCustomer"
                        type="button"
                        class="flex max-w-[7.5rem] items-center gap-1.5 truncate rounded-full border border-white/10 bg-white/8 px-2.5 py-1.5 text-[10px] font-semibold text-gold transition hover:bg-white/12 sm:max-w-52 sm:px-3 sm:py-2 sm:text-xs"
                        @click="promptOpen = true"
                    >
                        <AppIcon name="pin" size="xs" />
                        <span class="truncate">{{ locationLabel }}</span>
                    </button>

                    <Link
                        v-if="isGuest"
                        href="/login"
                        class="rounded-full border border-gold/40 bg-gold/10 px-3 py-1.5 text-[12px] font-bold text-gold transition hover:bg-gold/20 sm:px-3.5 sm:py-2 sm:text-[13px]"
                    >
                        دخول
                    </Link>
                    <Link
                        v-else
                        href="/account"
                        class="hidden max-w-28 truncate rounded-full border border-white/10 bg-white/8 px-3.5 py-2 text-[13px] font-medium transition hover:bg-white/12 lg:block"
                    >
                        {{ auth.user.name }}
                    </Link>

                    <Link
                        v-if="showCart"
                        href="/cart"
                        class="relative grid size-9 place-items-center rounded-[0.95rem] bg-gold text-navy shadow-[0_10px_24px_rgba(214,181,104,0.4)] transition hover:bg-gold-hover sm:size-11 sm:rounded-[1.15rem]"
                    >
                        <AppIcon name="cart" size="sm" />
                        <span
                            v-if="cartCount"
                            class="absolute -top-1.5 -start-1.5 grid min-w-5 place-items-center rounded-full bg-white px-1 text-[10px] font-black leading-none text-navy ring-2 ring-navy"
                        >
                            {{ cartCount }}
                        </span>
                    </Link>
                </div>
            </div>
        </header>

        <main class="app-page-enter customer-stack mx-auto max-w-6xl px-3 py-4 sm:px-5 sm:py-6 lg:px-6 lg:py-8">
            <div
                v-if="features.maintenance_mode"
                class="rounded-2xl border border-amber-200/80 bg-amber-50/95 px-4 py-3.5 text-sm font-medium leading-6 text-amber-950"
            >
                {{ features.maintenance_message || 'المنصة تحت الصيانة مؤقتاً.' }}
            </div>
            <FlashMessage />
            <slot />
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-line/60 bg-white/94 pb-[env(safe-area-inset-bottom)] shadow-[0_-12px_40px_rgba(12,24,48,0.08)] backdrop-blur-2xl md:hidden">
            <div
                class="mx-auto grid max-w-6xl px-1"
                :style="{ gridTemplateColumns: `repeat(${navItems.length}, minmax(0, 1fr))` }"
            >
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="customer-nav-item"
                    :class="item.match(page.url) ? 'text-navy' : 'text-muted'"
                >
                    <span
                        class="customer-nav-icon"
                        :class="item.match(page.url)
                            ? 'bg-navy text-gold shadow-[0_10px_22px_rgba(12,24,48,0.28)]'
                            : 'bg-transparent text-current'"
                    >
                        <AppIcon :name="item.icon" size="md" />
                    </span>
                    <span class="leading-none tracking-tight">{{ item.label }}</span>
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
