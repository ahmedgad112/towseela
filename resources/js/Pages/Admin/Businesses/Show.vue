<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import UiSelect from '../../../Components/UiSelect.vue';
import UiTextarea from '../../../Components/UiTextarea.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import FormSection from '../../../Components/FormSection.vue';
import ToggleField from '../../../Components/ToggleField.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import ImageField from '../../../Components/ImageField.vue';
import { canDo, money } from '../../../workspace';

defineOptions({ layout: AdminLayout });
const props = defineProps({
    business: { type: Object, required: true },
    canManageCatalog: { type: Boolean, default: false },
});

const page = usePage();
const currency = computed(() => page.props.currencyLabel || 'ج.م');
const canEditStore = computed(() => canDo(page.props.auth, 'businesses.manage'));
const categories = computed(() => props.business.product_categories || []);
const products = computed(() => props.business.products || []);
const editingCategoryId = ref(null);
const editingProductId = ref(null);

const categoryForm = useForm({
    name_ar: '',
    is_active: true,
});

const coverForm = useForm({
    cover: null,
});

const productForm = useForm({
    product_category_id: '',
    category_name: '',
    name_ar: '',
    description: '',
    price: '',
    image: null,
    is_available: true,
    is_active: true,
});
const productImagePreview = ref('');

const resetCategoryForm = () => {
    editingCategoryId.value = null;
    categoryForm.reset();
    categoryForm.is_active = true;
};

const resetProductForm = () => {
    editingProductId.value = null;
    productImagePreview.value = '';
    productForm.reset();
    productForm.image = null;
    productForm.is_available = true;
    productForm.is_active = true;
};

const submitCover = () => {
    coverForm.post(`/admin/businesses/${props.business.id}/cover`, {
        forceFormData: true,
        onSuccess: () => {
            coverForm.reset();
            coverForm.cover = null;
        },
    });
};

const submitCategory = () => {
    const options = { onSuccess: () => resetCategoryForm() };
    if (editingCategoryId.value) {
        categoryForm.patch(`/admin/businesses/${props.business.id}/product-categories/${editingCategoryId.value}`, options);
        return;
    }
    categoryForm.post(`/admin/businesses/${props.business.id}/product-categories`, options);
};

const editCategory = (category) => {
    editingCategoryId.value = category.id;
    categoryForm.name_ar = category.name_ar;
    categoryForm.is_active = category.is_active;
};

const deleteCategory = (category) => {
    if (!window.confirm(`حذف تصنيف ${category.name_ar}؟`)) {
        return;
    }
    router.delete(`/admin/businesses/${props.business.id}/product-categories/${category.id}`);
};

const submitProduct = () => {
    const options = { forceFormData: true, onSuccess: () => resetProductForm() };
    if (editingProductId.value) {
        productForm.patch(`/admin/businesses/${props.business.id}/products/${editingProductId.value}`, options);
        return;
    }
    productForm.post(`/admin/businesses/${props.business.id}/products`, options);
};

const editProduct = (product) => {
    editingProductId.value = product.id;
    productForm.product_category_id = product.product_category_id;
    productForm.category_name = '';
    productForm.name_ar = product.name_ar;
    productForm.description = product.description || '';
    productForm.price = product.price;
    productForm.image = null;
    productImagePreview.value = product.image_url || '';
    productForm.is_available = product.is_available;
    productForm.is_active = product.is_active;
};

const toggleProductAvailability = (product) => {
    if (!props.canManageCatalog) {
        return;
    }
    router.patch(`/admin/businesses/${props.business.id}/products/${product.id}/availability`, {}, { preserveScroll: true });
};

const deleteProduct = (product) => {
    if (!window.confirm(`حذف منتج ${product.name_ar}؟`)) {
        return;
    }
    router.delete(`/admin/businesses/${props.business.id}/products/${product.id}`);
};
</script>

<template>
    <PageHeader
        :eyebrow="business.category?.name_ar || 'متجر'"
        :title="business.name_ar"
        :text="business.description || 'من هنا تضيف تصنيفات المتجر ومنتجاته، وتعدّلها أو تحذفها.'"
    >
        <Link href="/admin/businesses" class="text-sm text-muted hover:text-navy">العودة للمتاجر</Link>
        <UiButton v-if="canEditStore" variant="outline" :href="`/admin/businesses/${business.id}/edit`">تعديل بيانات المتجر</UiButton>
    </PageHeader>

    <section class="app-card overflow-hidden">
        <div class="relative h-44 bg-navy">
            <img v-if="business.cover_url" :src="business.cover_url" alt="" class="size-full object-cover">
            <div v-else class="grid size-full place-items-center text-sm text-white/70">أضف صورة للمتجر ليظهر بها للعملاء</div>
        </div>
        <form v-if="canEditStore || canManageCatalog" class="grid gap-3 p-4 sm:grid-cols-[1fr_auto] sm:items-end" @submit.prevent="submitCover">
            <ImageField v-model="coverForm.cover" label="صورة المتجر" :preview="business.cover_url" :error="coverForm.errors.cover" />
            <UiButton type="submit" :disabled="coverForm.processing || !coverForm.cover">حفظ الصورة</UiButton>
        </form>
    </section>

    <div class="grid gap-4 md:grid-cols-3">
        <section class="app-card p-4">
            <p class="text-xs text-muted">الحالة</p>
            <p class="mt-1 font-semibold text-navy">{{ business.is_active ? 'ظاهر للعملاء' : 'موقوف' }}</p>
            <p class="mt-1 text-sm text-muted">{{ business.accepts_orders ? 'يستقبل طلبات' : 'لا يستقبل طلبات الآن' }}</p>
        </section>
        <section class="app-card p-4">
            <p class="text-xs text-muted">التصنيفات</p>
            <p class="mt-1 text-2xl font-bold text-navy">{{ categories.length }}</p>
        </section>
        <section class="app-card p-4">
            <p class="text-xs text-muted">المنتجات</p>
            <p class="mt-1 text-2xl font-bold text-navy">{{ products.length }}</p>
        </section>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <FormSection
            :title="editingCategoryId ? 'تعديل تصنيف' : 'إضافة تصنيف'"
            text="التصنيف يظهر للعميل داخل قائمة هذا المتجر فقط."
        >
            <form v-if="canManageCatalog" class="space-y-4" @submit.prevent="submitCategory">
                <UiInput v-model="categoryForm.name_ar" label="اسم التصنيف" placeholder="مشويات، مشروبات، سلطات" :error="categoryForm.errors.name_ar" />
                <ToggleField v-model="categoryForm.is_active" label="التصنيف ظاهر" />
                <div class="flex flex-wrap gap-2">
                    <UiButton type="submit" :disabled="categoryForm.processing">
                        {{ editingCategoryId ? 'حفظ التصنيف' : 'إضافة تصنيف' }}
                    </UiButton>
                    <UiButton v-if="editingCategoryId" type="button" variant="ghost" @click="resetCategoryForm">إلغاء</UiButton>
                </div>
            </form>
            <div v-if="categories.length" class="space-y-2">
                <div v-for="category in categories" :key="category.id" class="flex items-center justify-between gap-3 rounded-2xl bg-canvas px-3 py-3">
                    <div>
                        <p class="font-semibold text-navy">{{ category.name_ar }}</p>
                        <p class="text-xs text-muted">{{ category.products_count || 0 }} منتج</p>
                    </div>
                    <div v-if="canManageCatalog" class="flex gap-3">
                        <button type="button" class="text-sm font-semibold text-gold" @click="editCategory(category)">تعديل</button>
                        <button type="button" class="text-sm font-semibold text-red-600" @click="deleteCategory(category)">حذف</button>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-muted">لا توجد تصنيفات بعد.</p>
        </FormSection>

        <FormSection
            :title="editingProductId ? 'تعديل منتج' : 'إضافة منتج'"
            text="المنتج يُحفظ داخل هذا المتجر فقط."
        >
            <form v-if="canManageCatalog" class="space-y-4" @submit.prevent="submitProduct">
                <UiSelect v-model="productForm.product_category_id" label="تصنيف موجود" :error="productForm.errors.product_category_id">
                    <option value="">اختر تصنيفاً أو اكتب جديداً</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name_ar }}</option>
                </UiSelect>
                <UiInput v-model="productForm.category_name" label="أو تصنيف جديد" placeholder="مشويات" :error="productForm.errors.category_name" />
                <UiInput v-model="productForm.name_ar" label="اسم المنتج" placeholder="شاورما فراخ" :error="productForm.errors.name_ar" />
                <UiTextarea v-model="productForm.description" label="الوصف" :error="productForm.errors.description" />
                <UiInput v-model="productForm.price" type="number" min="0" step="0.5" label="السعر بالجنيه" :error="productForm.errors.price" />
                <ImageField v-model="productForm.image" label="صورة المنتج" :preview="productImagePreview" :error="productForm.errors.image" />
                <ToggleField v-model="productForm.is_available" label="متاح للطلب الآن" />
                <div class="flex flex-wrap gap-2">
                    <UiButton type="submit" :disabled="productForm.processing">
                        {{ editingProductId ? 'حفظ المنتج' : 'إضافة منتج' }}
                    </UiButton>
                    <UiButton v-if="editingProductId" type="button" variant="ghost" @click="resetProductForm">إلغاء</UiButton>
                </div>
            </form>
            <p v-else class="text-sm text-muted">يمكنك مشاهدة منتجات المتجر فقط.</p>
        </FormSection>
    </div>

    <FormSection title="منتجات المتجر" :text="products.length ? 'عدّل أو احذف أي منتج من هنا.' : 'أضف أول منتج لهذا المتجر.'">
        <div v-if="products.length" class="app-card-grid xl:grid-cols-2">
            <article v-for="product in products" :key="product.id" class="rounded-2xl border border-line bg-canvas/40 p-4">
                <div class="flex items-start gap-3">
                    <img v-if="product.image_url" :src="product.image_url" alt="" class="size-14 rounded-2xl object-cover">
                    <span v-else class="grid size-14 place-items-center rounded-2xl bg-white text-xs text-muted">صورة</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-navy">{{ product.name_ar }}</p>
                        <p class="mt-1 text-sm text-muted">{{ product.category?.name_ar || 'عام' }}</p>
                        <p class="mt-2 font-semibold text-navy">{{ money(product.price, currency) }}</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-full px-2.5 py-1 text-xs font-semibold transition"
                        :class="product.is_available ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-white text-muted hover:bg-line'"
                        :disabled="!canManageCatalog"
                        @click="toggleProductAvailability(product)"
                    >
                        {{ product.is_available ? 'متاح' : 'غير متاح' }}
                    </button>
                </div>
                <div v-if="canManageCatalog" class="mt-3 flex gap-3 border-t border-line pt-3">
                    <button type="button" class="text-sm font-semibold text-gold" @click="editProduct(product)">تعديل</button>
                    <button type="button" class="text-sm font-semibold text-red-600" @click="deleteProduct(product)">حذف</button>
                </div>
            </article>
        </div>
        <EmptyState v-else title="لا توجد منتجات" text="أضف تصنيفاً ثم منتجاً ليظهر للعملاء داخل هذا المتجر." />
    </FormSection>
</template>
