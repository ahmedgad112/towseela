<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import UiSelect from '../../../Components/UiSelect.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Pagination from '../../../Components/Pagination.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import FormSection from '../../../Components/FormSection.vue';
import ToggleField from '../../../Components/ToggleField.vue';
import { canDo } from '../../../workspace';

defineOptions({ layout: AdminLayout });
defineProps({ coupons: { type: Object, required: true } });
const page = usePage();
const form = useForm({
    code: '',
    name_ar: '',
    type: 'percent',
    value: 10,
    min_order: 0,
    usage_limit: '',
    starts_at: '',
    ends_at: '',
    is_active: true,
});
</script>

<template>
    <PageHeader title="العروض والكوبونات" text="أنشئ كود خصم يدخله العميل عند الدفع، مع حد أدنى وعدد استخدامات إن لزم." />

    <div class="grid gap-4 xl:grid-cols-[minmax(0,22rem)_1fr]">
        <FormSection title="كوبون جديد">
            <form v-if="canDo(page.props.auth, 'offers.manage')" class="space-y-4" @submit.prevent="form.post('/admin/coupons', { onSuccess: () => form.reset() })">
                <UiInput v-model="form.code" label="الكود" placeholder="TOWSEELA10" :error="form.errors.code" />
                <UiInput v-model="form.name_ar" label="الاسم الظاهر" placeholder="خصم الترحيب" :error="form.errors.name_ar" />
                <UiSelect v-model="form.type" label="نوع الخصم" :error="form.errors.type">
                    <option value="percent">نسبة مئوية</option>
                    <option value="fixed">مبلغ ثابت</option>
                </UiSelect>
                <div class="grid gap-3 sm:grid-cols-2">
                    <UiInput v-model="form.value" type="number" min="0" :label="form.type === 'percent' ? 'النسبة %' : 'المبلغ'" :error="form.errors.value" />
                    <UiInput v-model="form.min_order" type="number" min="0" label="الحد الأدنى للطلب" :error="form.errors.min_order" />
                </div>
                <UiInput v-model="form.usage_limit" type="number" min="1" label="حد الاستخدام (اختياري)" :error="form.errors.usage_limit" />
                <div class="grid gap-3 sm:grid-cols-2">
                    <UiInput v-model="form.starts_at" type="datetime-local" label="يبدأ" :error="form.errors.starts_at" />
                    <UiInput v-model="form.ends_at" type="datetime-local" label="ينتهي" :error="form.errors.ends_at" />
                </div>
                <ToggleField v-model="form.is_active" label="الكوبون مفعّل" />
                <UiButton type="submit" :disabled="form.processing">حفظ الكوبون</UiButton>
            </form>
        </FormSection>

        <div class="space-y-4">
            <div class="app-card overflow-hidden">
                <div v-if="coupons.data?.length">
                    <p v-for="coupon in coupons.data" :key="coupon.id" class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 text-sm last:border-0">
                        <span>
                            <span class="block font-semibold text-navy">{{ coupon.code }}</span>
                            <span class="text-muted">{{ coupon.name_ar }} · {{ coupon.type === 'percent' ? coupon.value + '%' : coupon.value + ' ج.م' }}</span>
                        </span>
                        <span class="text-muted">{{ coupon.used_count }} استخدام</span>
                    </p>
                </div>
                <EmptyState v-else title="لا توجد كوبونات" text="أنشئ كود خصم ليظهر عند الدفع." />
            </div>
            <Pagination :links="coupons.links" />
        </div>
    </div>
</template>
