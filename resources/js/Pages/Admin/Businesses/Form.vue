<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import UiSelect from '../../../Components/UiSelect.vue';
import UiTextarea from '../../../Components/UiTextarea.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import FormSection from '../../../Components/FormSection.vue';
import ToggleField from '../../../Components/ToggleField.vue';
import ImageField from '../../../Components/ImageField.vue';
import { canDo } from '../../../workspace';

defineOptions({ layout: AdminLayout });
const props = defineProps({
    business: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    managers: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] },
    hours: { type: Array, default: () => [] },
});

const page = usePage();
const canManageProducts = computed(() => canDo(page.props.auth, 'products.manage'));
const dayLabels = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
const emptyProduct = () => ({
    category_name: '',
    product_category_id: '',
    name_ar: '',
    description: '',
    price: '',
});

const form = useForm({
    business_category_id: props.business?.business_category_id || '',
    manager_user_id: props.business?.manager_user_id || '',
    name_ar: props.business?.name_ar || '',
    name_en: props.business?.name_en || '',
    description: props.business?.description || '',
    phone: props.business?.phone || '',
    min_order_amount: props.business?.min_order_amount || 0,
    delivery_time_min: props.business?.delivery_time_min || 30,
    delivery_time_max: props.business?.delivery_time_max || 60,
    is_active: props.business?.is_active ?? true,
    accepts_orders: props.business?.accepts_orders ?? true,
    branch_name: 'الفرع الرئيسي',
    delivery_zone_id: '',
    branch_address: '',
    branch_phone: '',
    hours: props.hours.length
        ? props.hours
        : dayLabels.map((_, day) => ({ day_of_week: day, opens_at: '10:00', closes_at: '23:30', is_closed: false })),
    products: [emptyProduct()],
    cover: null,
});

const branchForm = useForm({
    branch_name: '',
    delivery_zone_id: '',
    branch_address: '',
    branch_phone: '',
});

const submit = () => (props.business
    ? form.patch(`/admin/businesses/${props.business.id}`, { forceFormData: true })
    : form.post('/admin/businesses', { forceFormData: true }));

const addBranch = () => {
    if (!props.business) {
        return;
    }
    branchForm.post(`/admin/businesses/${props.business.id}/branches`, {
        onSuccess: () => branchForm.reset(),
    });
};

const addProductRow = () => form.products.push(emptyProduct());
const removeProductRow = (index) => {
    if (form.products.length === 1) {
        form.products[0] = emptyProduct();
        return;
    }
    form.products.splice(index, 1);
};
</script>

<template>
    <PageHeader
        :title="business ? 'تعديل المتجر' : 'إضافة متجر جديد'"
        text="أدخل بيانات المتجر. بعد الحفظ ستفتح صفحة المتجر لإضافة التصنيفات والمنتجات."
    >
        <Link href="/admin/businesses" class="text-sm text-muted hover:text-navy">العودة للمتاجر</Link>
        <Link v-if="business" :href="`/admin/businesses/${business.id}`" class="text-sm font-semibold text-gold">عرض صفحة المتجر</Link>
    </PageHeader>

    <form class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]" @submit.prevent="submit">
        <div class="space-y-4">
            <FormSection title="بيانات المتجر" text="الاسم والتصنيف هما ما يراه العميل أولاً في الصفحة الرئيسية.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <UiInput v-model="form.name_ar" label="اسم المتجر بالعربية" placeholder="بيت الشاورما" :error="form.errors.name_ar" />
                    <UiInput v-model="form.name_en" label="الاسم بالإنجليزية (اختياري)" placeholder="Beit El Shawerma" :error="form.errors.name_en" />
                </div>
                <UiSelect v-model="form.business_category_id" label="تصنيف المتجر" :error="form.errors.business_category_id">
                    <option value="">اختر تصنيفاً مثل مطاعم أو صيدليات</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name_ar }}</option>
                </UiSelect>
                <p v-if="!categories.length" class="text-xs text-red-600">
                    لا توجد تصنيفات بعد.
                    <Link href="/admin/categories" class="font-semibold text-gold">أضف تصنيفاً أولاً</Link>
                </p>
                <UiInput v-model="form.phone" label="هاتف المتجر" placeholder="02xxxxxxx" :error="form.errors.phone" />
                <UiTextarea v-model="form.description" label="وصف قصير يظهر للعميل" placeholder="شاورما وفراخ بخلطات البيت." :error="form.errors.description" />
                <ImageField v-model="form.cover" label="صورة المتجر" :preview="business?.cover_url" :error="form.errors.cover" />
            </FormSection>

            <FormSection title="التشغيل والتوصيل" text="الحد الأدنى ومدة التوصيل تظهر في بطاقة المتجر عند الطلب.">
                <div class="grid gap-4 sm:grid-cols-3">
                    <UiInput v-model="form.min_order_amount" type="number" min="0" label="الحد الأدنى للطلب (ج.م)" :error="form.errors.min_order_amount" />
                    <UiInput v-model="form.delivery_time_min" type="number" min="5" label="أقل مدة توصيل (دقيقة)" :error="form.errors.delivery_time_min" />
                    <UiInput v-model="form.delivery_time_max" type="number" min="5" label="أعلى مدة توصيل (دقيقة)" :error="form.errors.delivery_time_max" />
                </div>
            </FormSection>

            <FormSection v-if="!business && canManageProducts" title="منتجات هذا المتجر" text="أضف أصناف المتجر هنا. كل صف منتج داخل هذا المتجر فقط.">
                <div v-for="(product, index) in form.products" :key="index" class="space-y-3 rounded-2xl border border-line p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-navy">منتج {{ index + 1 }}</p>
                        <button type="button" class="text-xs text-muted hover:text-navy" @click="removeProductRow(index)">حذف الصف</button>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <UiInput v-model="product.category_name" label="تصنيف المنتج" placeholder="مشويات، مشروبات" :error="form.errors[`products.${index}.category_name`]" />
                        <UiInput v-model="product.name_ar" label="اسم المنتج" placeholder="شاورما فراخ" :error="form.errors[`products.${index}.name_ar`]" />
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <UiInput v-model="product.price" type="number" min="0" step="0.5" label="السعر بالجنيه" :error="form.errors[`products.${index}.price`]" />
                        <UiInput v-model="product.description" label="وصف مختصر" :error="form.errors[`products.${index}.description`]" />
                    </div>
                </div>
                <UiButton type="button" variant="outline" @click="addProductRow">إضافة منتج آخر</UiButton>
            </FormSection>

            <FormSection v-if="!business" title="الفرع الرئيسي" text="اختياري الآن، لكن الفرع والمنطقة يسهّلان ظهور المتجر في نطاق التوصيل.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <UiInput v-model="form.branch_name" label="اسم الفرع" placeholder="الفرع الرئيسي" :error="form.errors.branch_name" />
                    <UiSelect v-model="form.delivery_zone_id" label="منطقة التوصيل" :error="form.errors.delivery_zone_id">
                        <option value="">اختر المنطقة</option>
                        <option v-for="zone in zones" :key="zone.id" :value="zone.id">{{ zone.name_ar }}</option>
                    </UiSelect>
                </div>
                <UiInput v-model="form.branch_address" label="العنوان" placeholder="شارع التحرير، المعادي" :error="form.errors.branch_address" />
                <UiInput v-model="form.branch_phone" label="هاتف الفرع" :error="form.errors.branch_phone" />
            </FormSection>

            <FormSection title="ساعات العمل" text="نفس المواعيد لكل أيام الأسبوع، ويمكن إغلاق يوم محدد.">
                <div class="space-y-2">
                    <div
                        v-for="(hour, index) in form.hours"
                        :key="hour.day_of_week"
                        class="grid items-center gap-3 rounded-2xl border border-line px-3 py-2 sm:grid-cols-[7rem_1fr_1fr_auto]"
                    >
                        <p class="text-sm font-semibold text-navy">{{ dayLabels[hour.day_of_week] }}</p>
                        <UiInput v-model="form.hours[index].opens_at" type="time" label="يفتح" :disabled="hour.is_closed" />
                        <UiInput v-model="form.hours[index].closes_at" type="time" label="يغلق" :disabled="hour.is_closed" />
                        <label class="flex items-center gap-2 text-sm text-muted">
                            <input v-model="form.hours[index].is_closed" type="checkbox" class="accent-gold">
                            مغلق
                        </label>
                    </div>
                </div>
                <p v-if="form.errors.hours" class="text-xs text-red-600">{{ form.errors.hours }}</p>
            </FormSection>
        </div>

        <div class="space-y-4">
            <FormSection title="الحالة والمدير">
                <ToggleField v-model="form.is_active" label="المتجر ظاهر للعملاء" text="إذا أوقفته لن يظهر في الصفحة الرئيسية." />
                <ToggleField v-model="form.accepts_orders" label="يستقبل طلبات الآن" text="يمكن إيقاف الاستقبال مؤقتاً مع بقاء الصفحة ظاهرة." />
                <UiSelect v-model="form.manager_user_id" label="مدير المتجر" :error="form.errors.manager_user_id">
                    <option value="">بدون مدير</option>
                    <option v-for="manager in managers" :key="manager.id" :value="manager.id">{{ manager.name }}</option>
                </UiSelect>
                <UiButton type="submit" :disabled="form.processing" class="w-full">
                    {{ business ? 'حفظ التعديلات' : 'إنشاء المتجر ومنتجاته' }}
                </UiButton>
            </FormSection>

            <FormSection v-if="business" title="الفروع" text="أضف فرعاً جديداً بعد إنشاء المتجر.">
                <div v-if="business.branches?.length" class="space-y-2">
                    <p v-for="branch in business.branches" :key="branch.id" class="rounded-2xl bg-canvas px-3 py-3 text-sm">
                        <span class="block font-semibold text-navy">{{ branch.name_ar }}</span>
                        <span class="text-muted">{{ branch.address || 'بدون عنوان' }}</span>
                        <span v-if="branch.delivery_zone" class="mt-1 block text-xs text-gold">{{ branch.delivery_zone.name_ar }}</span>
                    </p>
                </div>
                <p v-else class="text-sm text-muted">لا يوجد فرع بعد.</p>
                <UiInput v-model="branchForm.branch_name" label="اسم الفرع" :error="branchForm.errors.branch_name" />
                <UiSelect v-model="branchForm.delivery_zone_id" label="المنطقة" :error="branchForm.errors.delivery_zone_id">
                    <option value="">اختر المنطقة</option>
                    <option v-for="zone in zones" :key="zone.id" :value="zone.id">{{ zone.name_ar }}</option>
                </UiSelect>
                <UiInput v-model="branchForm.branch_address" label="العنوان" :error="branchForm.errors.branch_address" />
                <UiInput v-model="branchForm.branch_phone" label="الهاتف" :error="branchForm.errors.branch_phone" />
                <UiButton type="button" variant="outline" :disabled="branchForm.processing" class="w-full" @click="addBranch">
                    إضافة فرع
                </UiButton>
            </FormSection>
        </div>
    </form>
</template>
