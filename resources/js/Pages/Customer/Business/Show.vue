<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CustomerLayout from '../../../Layouts/CustomerLayout.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import UiTextarea from '../../../Components/UiTextarea.vue';
import AppIcon from '../../../Components/AppIcon.vue';
import { addCartItem, updateCartItemQuantity } from '../../../cartApi';
import { canDo } from '../../../workspace';

defineOptions({ layout: CustomerLayout });
defineProps({
    business: { type: Object, required: true },
    isOpen: { type: Boolean, default: true },
});

const page = usePage();
const currency = computed(() => page.props.platform?.operations?.currency_label || 'ج.م');
const cartEnabled = computed(() => canDo(page.props.auth, 'customer.cart'));
const cartItems = computed(() => page.props.cart?.items || []);
const cartCount = computed(() => page.props.cart?.count || 0);

const selected = ref(null);
const loadingOptions = ref(false);
const optionsError = ref('');
const busyProductId = ref(null);
const modal = ref({
    product_id: null,
    quantity: 1,
    options: [],
    notes: '',
    processing: false,
});

const hasOptions = (product) => Boolean(product.has_options);

const plainCartItem = (product) => cartItems.value.find((item) => (
    item.product_id === product.id
    && (!item.options || item.options.length === 0)
    && !item.notes
));

const plainQuantity = (product) => plainCartItem(product)?.quantity || 0;

const syncCart = (snapshot) => {
    page.props.cart = snapshot;
};

const requireCart = () => {
    if (cartEnabled.value) {
        return true;
    }

    router.visit('/login');

    return false;
};

const open = async (product) => {
    selected.value = { ...product, options: [] };
    optionsError.value = '';
    modal.value = {
        product_id: product.id,
        quantity: 1,
        options: [],
        notes: '',
        processing: false,
    };

    if (! product.has_options) {
        return;
    }

    loadingOptions.value = true;

    try {
        const response = await fetch(`/products/${product.id}/options`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await response.json();

        if (! response.ok) {
            throw new Error(data.message || 'تعذر تحميل الإضافات.');
        }

        selected.value = { ...product, options: data.options || [] };
    } catch (error) {
        optionsError.value = error.message;
    } finally {
        loadingOptions.value = false;
    }
};

const addFromModal = async () => {
    if (! requireCart() || modal.value.processing) {
        return;
    }

    modal.value.processing = true;

    try {
        const snapshot = await addCartItem({
            productId: modal.value.product_id,
            quantity: modal.value.quantity,
            options: modal.value.options,
            notes: modal.value.notes,
        });
        syncCart(snapshot);
        selected.value = null;
    } catch (error) {
        optionsError.value = error.message;
    } finally {
        modal.value.processing = false;
    }
};

const quickAdd = async (product) => {
    if (! requireCart() || busyProductId.value) {
        return;
    }

    const previous = page.props.cart
        ? {
            count: page.props.cart.count,
            items: (page.props.cart.items || []).map((item) => ({ ...item })),
        }
        : { count: 0, items: [] };

    const existing = plainCartItem(product);

    if (existing) {
        syncCart({
            count: previous.count + 1,
            items: previous.items.map((item) => (
                item.id === existing.id
                    ? { ...item, quantity: item.quantity + 1 }
                    : item
            )),
        });
    } else {
        syncCart({
            count: previous.count + 1,
            items: [
                ...previous.items,
                {
                    id: `temp-${product.id}`,
                    product_id: product.id,
                    quantity: 1,
                    options: [],
                    notes: null,
                },
            ],
        });
    }

    busyProductId.value = product.id;

    try {
        if (existing && typeof existing.id === 'number') {
            syncCart(await updateCartItemQuantity(existing.id, existing.quantity + 1));
        } else {
            syncCart(await addCartItem({ productId: product.id, quantity: 1 }));
        }
    } catch {
        syncCart(previous);
    } finally {
        busyProductId.value = null;
    }
};

const setQuantity = async (item, quantity) => {
    if (! requireCart() || busyProductId.value || typeof item.id !== 'number') {
        return;
    }

    const previous = {
        count: page.props.cart?.count || 0,
        items: (page.props.cart?.items || []).map((line) => ({ ...line })),
    };
    const nextItems = quantity < 1
        ? previous.items.filter((line) => line.id !== item.id)
        : previous.items.map((line) => (
            line.id === item.id ? { ...line, quantity } : line
        ));

    syncCart({
        count: nextItems.reduce((sum, line) => sum + line.quantity, 0),
        items: nextItems,
    });

    busyProductId.value = item.product_id;

    try {
        syncCart(await updateCartItemQuantity(item.id, quantity));
    } catch {
        syncCart(previous);
    } finally {
        busyProductId.value = null;
    }
};

const onProductActivate = (product) => {
    if (hasOptions(product)) {
        open(product);

        return;
    }

    const item = plainCartItem(product);

    if (item) {
        setQuantity(item, item.quantity + 1);

        return;
    }

    quickAdd(product);
};

const onPlus = (product) => {
    if (hasOptions(product)) {
        open(product);

        return;
    }

    const item = plainCartItem(product);

    if (item) {
        setQuantity(item, item.quantity + 1);

        return;
    }

    quickAdd(product);
};

const onMinus = (product) => {
    const item = plainCartItem(product);

    if (! item) {
        return;
    }

    setQuantity(item, item.quantity - 1);
};
</script>

<template>
    <section class="customer-hero -mx-3 -mt-4 overflow-hidden sm:-mx-5 sm:-mt-6 sm:rounded-[1.5rem] lg:-mx-6 lg:-mt-8">
        <div class="customer-hero-media min-h-40 sm:min-h-48 lg:min-h-56">
            <img
                v-if="business.cover_url"
                :src="business.cover_url"
                alt=""
                class="size-full object-cover"
                fetchpriority="high"
                decoding="async"
            >
            <div
                v-else
                class="size-full bg-linear-to-br from-navy via-navy-secondary to-[#2a4060]"
            />
        </div>
        <div class="customer-hero-veil" />
        <div class="customer-hero-copy relative mx-auto max-w-6xl px-4 pb-6 pt-24 sm:px-6 sm:pb-8 sm:pt-28 lg:px-8">
            <p class="text-[11px] font-bold tracking-[0.14em] text-gold">{{ business.category?.name_ar }}</p>
            <h2 class="mt-2 text-[1.55rem] font-extrabold tracking-tight sm:text-[1.85rem] lg:text-3xl">{{ business.name_ar }}</h2>
            <p class="mt-2.5 max-w-2xl text-[13px] leading-6 text-white/75 sm:text-sm sm:leading-7">{{ business.description }}</p>
            <div class="mt-4 flex flex-wrap gap-2 text-[11px] sm:mt-5 sm:text-[12px]">
                <span
                    class="rounded-full px-3 py-1.5 font-semibold"
                    :class="isOpen ? 'bg-emerald-400/20 text-emerald-100' : 'bg-white/10 text-white/80'"
                >
                    {{ isOpen ? 'يستقبل الطلبات الآن' : 'خارج ساعات الذروة' }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 font-medium">
                    <AppIcon name="clock" size="xs" />
                    {{ business.delivery_time_min }}–{{ business.delivery_time_max }} دقيقة
                </span>
                <span class="rounded-full bg-gold px-3 py-1.5 font-bold text-navy">
                    حد أدنى {{ Number(business.min_order_amount).toFixed(0) }} {{ currency }}
                </span>
            </div>
        </div>
    </section>

    <section v-for="category in business.product_categories" :key="category.id" class="customer-section">
        <h3 class="customer-section-title">{{ category.name_ar }}</h3>
        <div class="flex flex-col gap-2.5">
            <article
                v-for="product in category.products"
                :key="product.id"
                class="customer-surface flex w-full items-center gap-3.5 p-3.5 text-right sm:gap-4 sm:p-4"
            >
                <button
                    type="button"
                    class="flex min-w-0 flex-1 items-center gap-3.5 text-right transition sm:gap-4"
                    @click="onProductActivate(product)"
                >
                    <img
                        v-if="product.image_url"
                        :src="product.image_url"
                        alt=""
                        width="128"
                        height="128"
                        loading="lazy"
                        decoding="async"
                        class="size-16 shrink-0 rounded-2xl object-cover sm:size-[4.5rem]"
                    >
                    <span
                        v-else
                        class="grid size-16 shrink-0 place-items-center rounded-2xl bg-navy text-base font-bold text-gold sm:size-[4.5rem]"
                    >
                        {{ product.name_ar?.charAt(0) }}
                    </span>
                    <span class="min-w-0 flex-1 space-y-1">
                        <span class="block font-extrabold tracking-tight text-navy">{{ product.name_ar }}</span>
                        <span class="block line-clamp-2 text-[13px] leading-6 text-muted">{{ product.description }}</span>
                    </span>
                </button>

                <div class="flex shrink-0 flex-col items-end gap-2.5">
                    <p class="font-extrabold text-navy">
                        {{ Number(product.price).toFixed(0) }}
                        <span class="text-[11px] font-semibold text-muted">{{ currency }}</span>
                    </p>

                    <div
                        v-if="!hasOptions(product) && plainQuantity(product) > 0"
                        class="flex items-center gap-1.5 rounded-full bg-canvas p-1 ring-1 ring-line"
                    >
                        <button
                            type="button"
                            class="grid size-8 place-items-center rounded-full text-sm font-bold text-navy transition hover:bg-white disabled:opacity-50"
                            :disabled="busyProductId === product.id"
                            aria-label="تقليل الكمية"
                            @click="onMinus(product)"
                        >
                            −
                        </button>
                        <span class="min-w-6 text-center text-sm font-bold text-navy">
                            {{ plainQuantity(product) }}
                        </span>
                        <button
                            type="button"
                            class="grid size-8 place-items-center rounded-full bg-gold text-navy transition hover:bg-gold-hover disabled:opacity-50"
                            :disabled="busyProductId === product.id"
                            aria-label="زيادة الكمية"
                            @click="onPlus(product)"
                        >
                            <AppIcon name="plus" size="xs" />
                        </button>
                    </div>

                    <button
                        v-else
                        type="button"
                        class="grid size-9 place-items-center rounded-full bg-gold text-navy shadow-[0_8px_16px_rgba(214,181,104,0.3)] transition hover:bg-gold-hover disabled:opacity-50"
                        :disabled="busyProductId === product.id"
                        :aria-label="hasOptions(product) ? 'اختيار الإضافات' : 'إضافة للسلة'"
                        @click="onPlus(product)"
                    >
                        <AppIcon name="plus" size="xs" />
                    </button>
                </div>
            </article>
        </div>
    </section>

    <div
        v-if="cartEnabled && cartCount"
        class="sticky bottom-[5.5rem] z-20 md:bottom-4"
    >
        <Link
            href="/cart"
            class="customer-cta flex w-full justify-between px-5 shadow-[0_14px_36px_rgba(12,24,48,0.22)]"
        >
            <span>عرض السلة</span>
            <span>{{ cartCount }} عنصر</span>
        </Link>
    </div>

    <div
        v-if="selected"
        class="fixed inset-0 z-40 flex items-end bg-navy/70 p-4 backdrop-blur-md sm:items-center"
        @click.self="selected = null"
    >
        <div class="mx-auto w-full max-w-xl space-y-4 overflow-hidden rounded-[1.75rem] bg-white shadow-2xl">
            <img
                v-if="selected.image_url"
                :src="selected.image_url"
                alt=""
                class="h-44 w-full object-cover"
                decoding="async"
            >
            <div class="space-y-4 px-5 pb-5" :class="selected.image_url ? '' : 'pt-5'">
                <div class="space-y-1.5">
                    <p class="text-[11px] font-bold tracking-[0.14em] text-gold">إضافة للسلة</p>
                    <h3 class="text-xl font-extrabold tracking-tight text-navy">{{ selected.name_ar }}</h3>
                    <p class="text-sm leading-6 text-muted">{{ selected.description }}</p>
                </div>
                <p v-if="loadingOptions" class="text-sm text-muted">جاري تحميل الإضافات...</p>
                <p v-else-if="optionsError" class="text-sm text-red-600">{{ optionsError }}</p>
                <UiInput
                    v-model.number="modal.quantity"
                    type="number"
                    label="الكمية"
                    min="1"
                    max="20"
                />
                <div v-for="option in selected.options" :key="option.id" class="space-y-2">
                    <p class="text-sm font-semibold text-navy">{{ option.name_ar }}</p>
                    <label
                        v-for="value in option.values"
                        :key="value.id"
                        class="flex items-center justify-between gap-3 rounded-2xl border border-line px-3.5 py-3 text-sm transition has-[:checked]:border-gold has-[:checked]:bg-gold/5"
                    >
                        <span class="flex items-center gap-2.5">
                            <input v-model="modal.options" type="checkbox" :value="value.id" class="accent-gold">
                            {{ value.name_ar }}
                        </span>
                        <span v-if="Number(value.price_delta)" class="shrink-0 font-semibold text-muted">
                            +{{ Number(value.price_delta).toFixed(0) }} {{ currency }}
                        </span>
                    </label>
                </div>
                <UiTextarea
                    v-model="modal.notes"
                    placeholder="ملاحظات للمطبخ أو المتجر"
                />
                <div class="flex gap-2.5">
                    <UiButton
                        v-if="cartEnabled"
                        class="flex-1"
                        :disabled="modal.processing || loadingOptions"
                        @click="addFromModal"
                    >
                        أضف للسلة
                    </UiButton>
                    <p v-else class="flex-1 self-center text-sm text-muted">سجّل الدخول لإضافة المنتجات إلى السلة.</p>
                    <UiButton variant="outline" @click="selected = null">إغلاق</UiButton>
                </div>
            </div>
        </div>
    </div>
</template>
