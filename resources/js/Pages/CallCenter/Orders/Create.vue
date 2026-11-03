<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import CallCenterLayout from '../../../Layouts/CallCenterLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import UiModal from '../../../Components/UiModal.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import AppIcon from '../../../Components/AppIcon.vue';
import { money } from '../../../workspace';

defineOptions({ layout: CallCenterLayout });

const props = defineProps({
    categories: { type: Array, default: () => [] },
    businesses: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] },
    governorates: { type: Array, default: () => [] },
    labels: { type: Array, default: () => [] },
    checkoutToken: { type: String, required: true },
    can: { type: Object, default: () => ({}) },
});

const page = usePage();
const currency = computed(() => page.props.currencyLabel || 'ج.م');

const step = ref(1);
const searchTerm = ref('');
const searching = ref(false);
const searchResults = ref([]);
const searchError = ref('');
const selectedCustomer = ref(null);
const selectedAddressId = ref('');
const categoryId = ref('');
const businessId = ref('');
const businessSearch = ref('');
const products = ref([]);
const loadingProducts = ref(false);
const cart = ref([]);
const createCustomerOpen = ref(false);
const createAddressOpen = ref(false);
const savingCustomer = ref(false);
const savingAddress = ref(false);
const actionError = ref('');

const orderForm = useForm({
    customer_id: '',
    address_id: '',
    checkout_token: props.checkoutToken,
    notes: '',
    items: [],
});

const customerForm = useForm({
    name: '',
    phone: '',
    email: '',
});

const addressForm = useForm({
    label: props.labels[0]?.value || 'home',
    line1: '',
    line2: '',
    city: props.governorates[0] || 'القاهرة',
    delivery_zone_id: '',
    notes: '',
    is_default: true,
});

const visibleZones = computed(() => props.zones.filter((zone) => !zone.governorate || zone.governorate === addressForm.city));
const selectedCategory = computed(() => props.categories.find((item) => String(item.id) === String(categoryId.value)));
const selectedBusiness = computed(() => props.businesses.find((item) => String(item.id) === String(businessId.value)));
const categoryBusinesses = computed(() => {
    if (! categoryId.value) {
        return [];
    }

    return props.businesses.filter((business) => String(business.business_category_id) === String(categoryId.value));
});
const filteredBusinesses = computed(() => {
    const term = businessSearch.value.trim().toLowerCase();

    if (! term) {
        return categoryBusinesses.value;
    }

    return categoryBusinesses.value.filter((business) => {
        const haystack = [business.name_ar, business.description].filter(Boolean).join(' ').toLowerCase();

        return haystack.includes(term);
    });
});
const cartTotal = computed(() => cart.value.reduce((sum, item) => sum + Number(item.price) * item.quantity, 0));

const selectCategory = (category) => {
    categoryId.value = String(category.id);
    businessId.value = '';
    businessSearch.value = '';
    products.value = [];
    cart.value = [];
};

const clearCategory = () => {
    categoryId.value = '';
    businessId.value = '';
    businessSearch.value = '';
    products.value = [];
    cart.value = [];
};

const selectBusiness = (business) => {
    businessId.value = String(business.id);
};

function csrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function api(url, { method = 'GET', body } = {}) {
    const response = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    const data = await response.json().catch(() => ({}));

    if (! response.ok) {
        const message = data.message
            || Object.values(data.errors || {}).flat()[0]
            || 'تعذر إكمال العملية.';

        throw new Error(message);
    }

    return data;
}

let searchTimer;

const runSearch = async () => {
    const term = searchTerm.value.trim();
    searchError.value = '';

    if (term.length < 2) {
        searchResults.value = [];

        return;
    }

    searching.value = true;

    try {
        const data = await api(`/dispatch/customers/search?q=${encodeURIComponent(term)}`);
        searchResults.value = data.customers || [];
    } catch (error) {
        searchError.value = error.message;
        searchResults.value = [];
    } finally {
        searching.value = false;
    }
};

watch(searchTerm, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(runSearch, 300);
});

const selectCustomer = (customer) => {
    selectedCustomer.value = customer;
    const defaultAddress = customer.addresses?.find((item) => item.is_default)
        || customer.addresses?.[0];
    selectedAddressId.value = defaultAddress ? String(defaultAddress.id) : '';
    orderForm.customer_id = customer.id;
    orderForm.address_id = defaultAddress?.id || '';
    step.value = 2;
    actionError.value = '';
};

const openCreateCustomer = () => {
    customerForm.reset();
    customerForm.clearErrors();
    customerForm.phone = searchTerm.value.trim();
    createCustomerOpen.value = true;
};

const saveCustomer = async () => {
    actionError.value = '';
    customerForm.clearErrors();
    savingCustomer.value = true;

    try {
        const data = await api('/dispatch/customers', {
            method: 'POST',
            body: {
                name: customerForm.name,
                phone: customerForm.phone,
                email: customerForm.email || null,
            },
        });
        createCustomerOpen.value = false;
        selectCustomer(data.customer);
    } catch (error) {
        actionError.value = error.message;
    } finally {
        savingCustomer.value = false;
    }
};

const openCreateAddress = () => {
    addressForm.reset();
    addressForm.clearErrors();
    addressForm.label = props.labels[0]?.value || 'home';
    addressForm.city = props.governorates[0] || 'القاهرة';
    addressForm.is_default = ! selectedCustomer.value?.addresses?.length;
    createAddressOpen.value = true;
};

const saveAddress = async () => {
    if (! selectedCustomer.value) {
        return;
    }

    actionError.value = '';
    savingAddress.value = true;

    try {
        const data = await api(`/dispatch/customers/${selectedCustomer.value.id}/addresses`, {
            method: 'POST',
            body: {
                label: addressForm.label,
                line1: addressForm.line1,
                line2: addressForm.line2 || null,
                city: addressForm.city,
                delivery_zone_id: Number(addressForm.delivery_zone_id),
                notes: addressForm.notes || null,
                is_default: Boolean(addressForm.is_default),
            },
        });

        selectedCustomer.value = {
            ...selectedCustomer.value,
            addresses: [...(selectedCustomer.value.addresses || []), data.address],
        };
        selectedAddressId.value = String(data.address.id);
        orderForm.address_id = data.address.id;
        createAddressOpen.value = false;
    } catch (error) {
        actionError.value = error.message;
    } finally {
        savingAddress.value = false;
    }
};

watch(selectedAddressId, (value) => {
    orderForm.address_id = value;
});

watch(businessId, async (value) => {
    products.value = [];
    cart.value = [];

    if (! value) {
        return;
    }

    loadingProducts.value = true;
    actionError.value = '';

    try {
        const data = await api(`/dispatch/businesses/${value}/products`);
        products.value = data.products || [];
    } catch (error) {
        actionError.value = error.message;
    } finally {
        loadingProducts.value = false;
    }
});

const addProduct = (product) => {
    const existing = cart.value.find((item) => item.product_id === product.id);

    if (existing) {
        existing.quantity += 1;

        return;
    }

    cart.value.push({
        product_id: product.id,
        name: product.name_ar,
        price: product.price,
        quantity: 1,
    });
};

const changeQty = (productId, delta) => {
    const item = cart.value.find((row) => row.product_id === productId);

    if (! item) {
        return;
    }

    item.quantity += delta;

    if (item.quantity < 1) {
        cart.value = cart.value.filter((row) => row.product_id !== productId);
    }
};

const goToProducts = () => {
    if (! selectedAddressId.value) {
        actionError.value = 'اختر عنوان التوصيل أو أضف عنواناً جديداً.';

        return;
    }

    actionError.value = '';
    step.value = 3;
};

const submitOrder = () => {
    if (! cart.value.length) {
        actionError.value = 'أضف منتجاً واحداً على الأقل.';

        return;
    }

    orderForm.customer_id = selectedCustomer.value.id;
    orderForm.address_id = selectedAddressId.value;
    orderForm.items = cart.value.map((item) => ({
        product_id: item.product_id,
        quantity: item.quantity,
    }));
    orderForm.post('/dispatch/orders');
};
</script>

<template>
    <PageHeader title="طلب جديد" text="ابحث عن العميل بالاسم أو الهاتف، أو أنشئ عميلاً جديداً ثم أكمل الطلب.">
        <UiButton href="/dispatch" variant="outline">رجوع</UiButton>
    </PageHeader>

    <div class="no-scrollbar flex gap-2 overflow-x-auto">
        <span
            v-for="item in [
                { n: 1, label: 'العميل' },
                { n: 2, label: 'العنوان' },
                { n: 3, label: 'المنتجات' },
            ]"
            :key="item.n"
            class="app-chip shrink-0"
            :class="{ 'border-navy bg-navy text-white': step === item.n }"
        >
            {{ item.n }}. {{ item.label }}
        </span>
    </div>

    <p v-if="actionError || orderForm.errors.items || orderForm.errors.customer_id || orderForm.errors.address_id" class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">
        {{ actionError || orderForm.errors.items || orderForm.errors.customer_id || orderForm.errors.address_id }}
    </p>

    <section v-if="step === 1" class="space-y-3">
        <div class="app-card space-y-3 p-4">
            <UiInput v-model="searchTerm" label="بحث بالاسم أو رقم الهاتف" placeholder="مثال: 010... أو سارة" />
            <div class="flex flex-wrap gap-2">
                <UiButton type="button" variant="navy" :disabled="searching" @click="runSearch">
                    {{ searching ? 'جاري البحث...' : 'بحث' }}
                </UiButton>
                <UiButton v-if="can.createCustomer" type="button" variant="outline" @click="openCreateCustomer">
                    عميل جديد
                </UiButton>
            </div>
            <p v-if="searchError" class="text-sm text-red-600">{{ searchError }}</p>
        </div>

        <EmptyState
            v-if="!searchResults.length && searchTerm.trim().length >= 2 && !searching"
            title="لا يوجد عميل مطابق"
            text="عدّل البحث أو أنشئ عميلاً جديداً بنفس رقم الهاتف."
        />

        <button
            v-for="customer in searchResults"
            :key="customer.id"
            type="button"
            class="app-item-card w-full text-start"
            @click="selectCustomer(customer)"
        >
            <p class="font-bold text-navy">{{ customer.name }}</p>
            <p class="mt-1 text-sm text-muted">{{ customer.phone }}</p>
            <p class="mt-2 text-xs text-muted">{{ customer.orders_count }} طلب سابق</p>
        </button>
    </section>

    <section v-else-if="step === 2" class="space-y-3">
        <article class="app-card p-4">
            <p class="text-sm text-muted">العميل</p>
            <p class="font-bold text-navy">{{ selectedCustomer?.name }} · {{ selectedCustomer?.phone }}</p>
            <button type="button" class="mt-2 text-sm text-navy underline" @click="step = 1">تغيير العميل</button>
        </article>

        <div class="flex items-center justify-between gap-3">
            <h3 class="font-bold text-navy">عنوان التوصيل</h3>
            <UiButton type="button" variant="outline" @click="openCreateAddress">عنوان جديد</UiButton>
        </div>

        <EmptyState
            v-if="!selectedCustomer?.addresses?.length"
            title="لا توجد عناوين"
            text="أضف عنوان توصيل للعميل قبل إكمال الطلب."
        />

        <label
            v-for="address in selectedCustomer?.addresses || []"
            :key="address.id"
            class="app-item-card flex cursor-pointer items-start gap-3"
        >
            <input v-model="selectedAddressId" type="radio" class="mt-1" :value="String(address.id)">
            <div>
                <p class="font-semibold text-navy">{{ address.label_text || 'عنوان' }} · {{ address.line1 }}</p>
                <p class="mt-1 text-sm text-muted">
                    {{ address.delivery_zone?.name_ar }} · {{ address.city }}
                </p>
            </div>
        </label>

        <div class="flex justify-end">
            <UiButton type="button" @click="goToProducts">التالي: المنتجات</UiButton>
        </div>
    </section>

    <section v-else class="space-y-3">
        <article class="app-card space-y-2 p-4">
            <p class="text-sm text-muted">{{ selectedCustomer?.name }} · {{ selectedCustomer?.phone }}</p>
            <button type="button" class="text-sm text-navy underline" @click="step = 2">تعديل العنوان</button>
        </article>

        <section v-if="!categoryId" class="space-y-3">
            <div>
                <h3 class="font-bold text-navy">اختر الفئة</h3>
                <p class="mt-1 text-sm text-muted">بعد اختيار الفئة هتظهر المطاعم المتاحة داخلها.</p>
            </div>

            <EmptyState
                v-if="!categories.length"
                title="لا توجد فئات متاحة"
                text="تأكد من وجود متاجر نشطة تستقبل الطلبات."
            />

            <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                <button
                    v-for="category in categories"
                    :key="category.id"
                    type="button"
                    class="app-item-card flex flex-col items-center gap-3 text-center transition hover:-translate-y-0.5 hover:border-gold"
                    @click="selectCategory(category)"
                >
                    <span class="grid size-14 place-items-center rounded-[1.25rem] bg-navy text-gold">
                        <AppIcon :name="category.icon || 'utensils'" size="md" />
                    </span>
                    <span class="font-extrabold tracking-tight text-navy">{{ category.name_ar }}</span>
                    <span class="text-xs text-muted">{{ category.businesses_count }} متجر</span>
                </button>
            </div>
        </section>

        <section v-else class="space-y-3">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="font-bold text-navy">مطاعم {{ selectedCategory?.name_ar }}</h3>
                    <p class="mt-1 text-sm text-muted">اضغط على بطاقة المتجر لعرض منتجاته.</p>
                    <button type="button" class="mt-2 text-sm font-semibold text-navy underline" @click="clearCategory">
                        تغيير الفئة
                    </button>
                </div>
                <input
                    v-if="categoryBusinesses.length > 4"
                    v-model="businessSearch"
                    class="app-select sm:max-w-64"
                    placeholder="بحث في المتاجر..."
                >
            </div>

            <EmptyState
                v-if="!filteredBusinesses.length"
                title="لا توجد متاجر في هذه الفئة"
                text="جرّب فئة أخرى أو كلمة بحث مختلفة."
            />

            <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <button
                    v-for="business in filteredBusinesses"
                    :key="business.id"
                    type="button"
                    class="group overflow-hidden rounded-[1.35rem] border text-start transition"
                    :class="String(businessId) === String(business.id)
                        ? 'border-navy bg-navy text-white shadow-[0_14px_30px_rgba(17,42,70,0.22)]'
                        : 'border-line bg-white text-navy hover:-translate-y-0.5 hover:border-gold'"
                    style="box-shadow: var(--shadow-card)"
                    @click="selectBusiness(business)"
                >
                    <div class="relative aspect-[16/9] overflow-hidden bg-navy">
                        <img
                            v-if="business.cover_url"
                            :src="business.cover_url"
                            alt=""
                            class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-105"
                            loading="lazy"
                        >
                        <div
                            v-else
                            class="absolute inset-0 bg-linear-to-br from-navy via-navy-secondary to-[#2a4060]"
                        />
                        <div class="absolute inset-0 bg-linear-to-t from-black/55 via-black/10 to-transparent" />
                        <span
                            v-if="String(businessId) === String(business.id)"
                            class="absolute top-2.5 end-2.5 grid size-8 place-items-center rounded-full bg-gold text-navy"
                        >
                            <AppIcon name="check" size="sm" />
                        </span>
                    </div>
                    <div class="space-y-2 p-3.5 sm:p-4">
                        <p class="font-extrabold tracking-tight">{{ business.name_ar }}</p>
                        <p
                            class="line-clamp-2 text-[12px] leading-5"
                            :class="String(businessId) === String(business.id) ? 'text-white/70' : 'text-muted'"
                        >
                            {{ business.description || 'اضغط للاختيار وإضافة المنتجات.' }}
                        </p>
                        <div
                            class="flex flex-wrap items-center justify-between gap-2 border-t pt-2.5 text-[11px] font-semibold"
                            :class="String(businessId) === String(business.id) ? 'border-white/15 text-white/80' : 'border-line text-muted'"
                        >
                            <span class="inline-flex items-center gap-1.5">
                                <AppIcon name="clock" size="xs" />
                                {{ business.delivery_time_min || 30 }}–{{ business.delivery_time_max || 45 }} د
                            </span>
                            <span :class="String(businessId) === String(business.id) ? 'text-gold' : 'text-navy'">
                                من {{ money(business.min_order_amount, currency) }}
                            </span>
                        </div>
                    </div>
                </button>
            </div>
        </section>

        <article
            v-if="selectedBusiness"
            class="app-card flex flex-wrap items-center justify-between gap-3 p-4"
        >
            <div>
                <p class="text-xs font-semibold text-muted">المتجر المختار</p>
                <p class="mt-1 font-bold text-navy">{{ selectedBusiness.name_ar }}</p>
            </div>
            <p class="text-sm text-muted">
                الحد الأدنى {{ money(selectedBusiness.min_order_amount, currency) }}
            </p>
        </article>

        <p v-if="loadingProducts" class="text-sm text-muted">جاري تحميل المنتجات...</p>

        <div v-if="products.length" class="app-card-grid">
            <article v-for="product in products" :key="product.id" class="app-item-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-bold text-navy">{{ product.name_ar }}</p>
                        <p class="mt-1 text-sm text-muted">{{ money(product.price, currency) }}</p>
                    </div>
                    <UiButton type="button" variant="outline" @click="addProduct(product)">إضافة</UiButton>
                </div>
            </article>
        </div>

        <section v-if="cart.length" class="app-card space-y-3 p-4">
            <h3 class="font-bold text-navy">سلة الطلب</h3>
            <div v-for="item in cart" :key="item.product_id" class="flex items-center justify-between gap-3 border-b border-line pb-3 last:border-0 last:pb-0">
                <div>
                    <p class="font-semibold text-navy">{{ item.name }}</p>
                    <p class="text-sm text-muted">{{ money(item.price, currency) }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="grid size-9 place-items-center rounded-xl bg-canvas" @click="changeQty(item.product_id, -1)">−</button>
                    <span class="min-w-6 text-center font-bold">{{ item.quantity }}</span>
                    <button type="button" class="grid size-9 place-items-center rounded-xl bg-canvas" @click="changeQty(item.product_id, 1)">+</button>
                </div>
            </div>
            <p class="text-sm font-bold text-navy">إجمالي المنتجات: {{ money(cartTotal, currency) }}</p>
            <UiInput v-model="orderForm.notes" label="ملاحظة للطلب (اختياري)" :error="orderForm.errors.notes" />
            <UiButton type="button" :disabled="orderForm.processing" @click="submitOrder">
                {{ orderForm.processing ? 'جاري الإنشاء...' : 'إنشاء الطلب' }}
            </UiButton>
        </section>
    </section>

    <UiModal :open="createCustomerOpen" title="عميل جديد" text="لو العميل مش موجود، سجّله بالاسم ورقم الهاتف." @close="createCustomerOpen = false">
        <form class="space-y-3" @submit.prevent="saveCustomer">
            <UiInput v-model="customerForm.name" label="الاسم" required />
            <UiInput v-model="customerForm.phone" label="رقم الهاتف" required />
            <UiInput v-model="customerForm.email" type="email" label="البريد (اختياري)" />
            <div class="flex justify-end gap-2 pt-2">
                <UiButton type="button" variant="outline" @click="createCustomerOpen = false">إلغاء</UiButton>
                <UiButton type="submit" :disabled="savingCustomer">حفظ واختيار</UiButton>
            </div>
        </form>
    </UiModal>

    <UiModal :open="createAddressOpen" title="عنوان جديد" text="أضف عنوان التوصيل للعميل المختار." @close="createAddressOpen = false">
        <form class="space-y-3" @submit.prevent="saveAddress">
            <label class="block space-y-1.5">
                <span class="text-sm font-semibold text-navy">التصنيف</span>
                <select v-model="addressForm.label" class="app-select w-full">
                    <option v-for="label in labels" :key="label.value" :value="label.value">{{ label.label }}</option>
                </select>
            </label>
            <UiInput v-model="addressForm.line1" label="العنوان" required />
            <label class="block space-y-1.5">
                <span class="text-sm font-semibold text-navy">المحافظة</span>
                <select v-model="addressForm.city" class="app-select w-full">
                    <option v-for="city in governorates" :key="city" :value="city">{{ city }}</option>
                </select>
            </label>
            <label class="block space-y-1.5">
                <span class="text-sm font-semibold text-navy">منطقة التوصيل</span>
                <select v-model="addressForm.delivery_zone_id" class="app-select w-full" required>
                    <option value="">اختر المنطقة</option>
                    <option v-for="zone in visibleZones" :key="zone.id" :value="zone.id">
                        {{ zone.name_ar }}
                    </option>
                </select>
            </label>
            <UiInput v-model="addressForm.notes" label="ملاحظات (اختياري)" />
            <div class="flex justify-end gap-2 pt-2">
                <UiButton type="button" variant="outline" @click="createAddressOpen = false">إلغاء</UiButton>
                <UiButton type="submit" :disabled="savingAddress">حفظ العنوان</UiButton>
            </div>
        </form>
    </UiModal>
</template>
