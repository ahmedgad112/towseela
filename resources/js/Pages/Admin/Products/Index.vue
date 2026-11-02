<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import UiSelect from '../../../Components/UiSelect.vue';
import UiTextarea from '../../../Components/UiTextarea.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Pagination from '../../../Components/Pagination.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import FormSection from '../../../Components/FormSection.vue';
import ToggleField from '../../../Components/ToggleField.vue';
import { canDo, money } from '../../../workspace';

defineOptions({ layout: AdminLayout });
const props = defineProps({
    products: { type: Object, required: true },
    businesses: { type: Array, default: () => [] },
    productCategories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const currency = computed(() => page.props.currencyLabel || 'ج.م');
const editingId = ref(null);

const form = useForm({
    business_id: props.filters.business_id || '',
    product_category_id: '',
    category_name: '',
    name_ar: '',
    description: '',
    price: '',
    is_available: true,
    is_active: true,
});

const filter = useForm({
    q: props.filters.q || '',
    business_id: props.filters.business_id || '',
});

const categoriesForBusiness = computed(() => (
    props.productCategories.filter((category) => String(category.business_id) === String(form.business_id))
));

watch(() => form.business_id, () => {
    if (!categoriesForBusiness.value.some((category) => String(category.id) === String(form.product_category_id))) {
        form.product_category_id = '';
    }
});

const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.business_id = props.filters.business_id || '';
    form.is_available = true;
    form.is_active = true;
};

const submit = () => {
    const options = { onSuccess: () => resetForm() };
    if (editingId.value) {
        form.patch(`/admin/products/${editingId.value}`, options);
        return;
    }
    form.post('/admin/products', options);
};

const editProduct = (product) => {
    editingId.value = product.id;
    form.business_id = product.business_id;
    form.product_category_id = product.product_category_id;
    form.category_name = '';
    form.name_ar = product.name_ar;
    form.description = product.description || '';
    form.price = product.price;
    form.is_available = product.is_available;
    form.is_active = product.is_active;
};

const applyFilter = () => {
    router.get('/admin/products', {
        q: filter.q || undefined,
        business_id: filter.business_id || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <PageHeader title="المنتجات" text="اختر المتجر أولاً، ثم التصنيف أو أنشئ تصنيفاً جديداً أثناء إضافة المنتج." />

    <div class="grid gap-4 xl:grid-cols-[minmax(0,22rem)_1fr]">
        <FormSection :title="editingId ? 'تعديل المنتج' : 'إضافة منتج'" text="لا تحتاج رقم تصنيف. اختر من القائمة أو اكتب اسماً جديداً مثل مشويات.">
            <form v-if="canDo(page.props.auth, 'products.manage')" class="space-y-4" @submit.prevent="submit">
                <UiSelect v-model="form.business_id" label="المتجر" :error="form.errors.business_id">
                    <option value="">اختر المتجر</option>
                    <option v-for="business in businesses" :key="business.id" :value="business.id">{{ business.name_ar }}</option>
                </UiSelect>
                <UiSelect v-model="form.product_category_id" label="تصنيف المنتج" :error="form.errors.product_category_id">
                    <option value="">اختر تصنيفاً موجوداً</option>
                    <option v-for="category in categoriesForBusiness" :key="category.id" :value="category.id">{{ category.name_ar }}</option>
                </UiSelect>
                <UiInput
                    v-model="form.category_name"
                    label="أو تصنيف جديد"
                    placeholder="مشويات، مشروبات، سلطات"
                    :error="form.errors.category_name"
                />
                <UiInput v-model="form.name_ar" label="اسم المنتج" placeholder="شاورما فراخ" :error="form.errors.name_ar" />
                <UiTextarea v-model="form.description" label="الوصف (اختياري)" placeholder="وصف قصير يظهر للعميل" :error="form.errors.description" />
                <UiInput v-model="form.price" type="number" min="0" step="0.5" label="السعر بالجنيه" :error="form.errors.price" />
                <ToggleField v-model="form.is_available" label="متاح للطلب الآن" />
                <ToggleField v-model="form.is_active" label="ظاهر في قائمة المتجر" />
                <div class="flex flex-wrap gap-2">
                    <UiButton type="submit" :disabled="form.processing">
                        {{ editingId ? 'حفظ التعديل' : 'إضافة المنتج' }}
                    </UiButton>
                    <UiButton v-if="editingId" type="button" variant="ghost" @click="resetForm">إلغاء</UiButton>
                </div>
            </form>
            <p v-else class="text-sm text-muted">ليس لديك صلاحية إضافة منتجات.</p>
        </FormSection>

        <div class="space-y-4">
            <form class="app-card grid gap-3 p-4 sm:grid-cols-[1fr_12rem_auto]" @submit.prevent="applyFilter">
                <UiInput v-model="filter.q" placeholder="ابحث باسم المنتج" />
                <UiSelect v-model="filter.business_id">
                    <option value="">كل المتاجر</option>
                    <option v-for="business in businesses" :key="business.id" :value="business.id">{{ business.name_ar }}</option>
                </UiSelect>
                <UiButton type="submit" variant="outline">تصفية</UiButton>
            </form>
            <div v-if="products.data?.length" class="app-card-grid xl:grid-cols-2">
                <article v-for="product in products.data" :key="product.id" class="app-item-card">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-navy">{{ product.name_ar }}</p>
                            <p class="mt-1 text-sm text-muted">{{ product.business?.name_ar }} · {{ product.category?.name_ar || '—' }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="product.is_available ? 'bg-emerald-50 text-emerald-700' : 'bg-canvas text-muted'">
                            {{ product.is_available ? 'متاح' : 'غير متاح' }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line pt-3 text-sm">
                        <span class="font-semibold text-navy">{{ money(product.price, currency) }}</span>
                        <button
                            v-if="canDo(page.props.auth, 'products.manage')"
                            type="button"
                            class="font-semibold text-gold"
                            @click="editProduct(product)"
                        >
                            تعديل
                        </button>
                    </div>
                </article>
            </div>
            <EmptyState v-else title="لا توجد منتجات" text="أضف أول منتج من النموذج الجانبي ليظهر في المتجر." />
            <Pagination :links="products.links" />
        </div>
    </div>
</template>
