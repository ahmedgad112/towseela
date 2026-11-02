<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import UiButton from '../../Components/UiButton.vue';
import UiInput from '../../Components/UiInput.vue';
import UiTextarea from '../../Components/UiTextarea.vue';
import PageHeader from '../../Components/PageHeader.vue';
import FormSection from '../../Components/FormSection.vue';
import ToggleField from '../../Components/ToggleField.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ settings: { type: Object, required: true } });

const form = useForm({
    brand: {
        name_ar: props.settings.brand.name_ar,
        name_en: props.settings.brand.name_en,
        tagline: props.settings.brand.tagline,
        support_phone: props.settings.brand.support_phone,
        support_email: props.settings.brand.support_email || '',
        whatsapp: props.settings.brand.whatsapp || '',
    },
    operations: {
        currency: props.settings.operations.currency,
        currency_label: props.settings.operations.currency_label,
        allow_mixed_business_cart: Boolean(props.settings.operations.allow_mixed_business_cart),
        require_proof_of_delivery_note: Boolean(props.settings.operations.require_proof_of_delivery_note),
        driver_active_order_limit: props.settings.operations.driver_active_order_limit,
        overdue_minutes: props.settings.operations.overdue_minutes,
    },
    map: {
        default_lat: props.settings.map.default_lat,
        default_lng: props.settings.map.default_lng,
        default_city: props.settings.map.default_city,
    },
    features: {
        customer_registration_enabled: Boolean(props.settings.features.customer_registration_enabled),
        maintenance_mode: Boolean(props.settings.features.maintenance_mode),
        maintenance_message: props.settings.features.maintenance_message || '',
    },
});
</script>

<template>
    <PageHeader title="إعدادات المنصة" text="الهوية والتشغيل والخريطة والصيانة. التحكم في الأزرار من صفحة الأدوار والصلاحيات." />

    <form class="space-y-4" @submit.prevent="form.patch('/admin/settings')">
        <div class="grid gap-4 xl:grid-cols-2">
            <FormSection title="الهوية والدعم" text="الاسم والشعار الظاهر للعملاء، وبيانات التواصل.">
                <div class="space-y-4">
                    <UiInput v-model="form.brand.name_ar" label="اسم المنصة بالعربي" :error="form.errors['brand.name_ar']" />
                    <UiInput v-model="form.brand.name_en" label="اسم المنصة بالإنجليزي" :error="form.errors['brand.name_en']" />
                    <UiInput v-model="form.brand.tagline" label="الوصف القصير" :error="form.errors['brand.tagline']" />
                    <UiInput v-model="form.brand.support_phone" label="هاتف الدعم" :error="form.errors['brand.support_phone']" />
                    <UiInput v-model="form.brand.support_email" type="email" label="بريد الدعم" :error="form.errors['brand.support_email']" />
                    <UiInput v-model="form.brand.whatsapp" label="واتساب الدعم" :error="form.errors['brand.whatsapp']" />
                </div>
            </FormSection>

            <FormSection title="التشغيل" text="العملة وحدود المناديب والطلبات المتأخرة وإثبات التسليم.">
                <div class="space-y-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <UiInput v-model="form.operations.currency" label="رمز العملة" :error="form.errors['operations.currency']" />
                        <UiInput v-model="form.operations.currency_label" label="تسمية العملة" :error="form.errors['operations.currency_label']" />
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <UiInput v-model="form.operations.driver_active_order_limit" type="number" min="1" max="20" label="حد الطلبات النشطة للمندوب" :error="form.errors['operations.driver_active_order_limit']" />
                        <UiInput v-model="form.operations.overdue_minutes" type="number" min="15" max="1440" label="دقائق اعتبار الطلب متأخراً" :error="form.errors['operations.overdue_minutes']" />
                    </div>
                    <ToggleField
                        v-model="form.operations.allow_mixed_business_cart"
                        label="السماح بخلط متاجر في السلة"
                        text="إن تعطيلها يلزم العميل بطلب من متجر واحد فقط في كل مرة."
                    />
                    <ToggleField
                        v-model="form.operations.require_proof_of_delivery_note"
                        label="إثبات تسليم إلزامي"
                        text="المندوب يجب أن يكتب ملاحظة قبل تأكيد التسليم."
                    />
                </div>
            </FormSection>

            <FormSection title="الخريطة والموقع الافتراضي" text="مركز الخريطة والمدينة الافتراضية عند فتح تحديد الموقع.">
                <div class="space-y-4">
                    <UiInput v-model="form.map.default_city" label="المدينة الافتراضية" :error="form.errors['map.default_city']" />
                    <div class="grid gap-3 sm:grid-cols-2">
                        <UiInput v-model="form.map.default_lat" type="number" step="0.0001" label="خط العرض" :error="form.errors['map.default_lat']" />
                        <UiInput v-model="form.map.default_lng" type="number" step="0.0001" label="خط الطول" :error="form.errors['map.default_lng']" />
                    </div>
                </div>
            </FormSection>

            <FormSection title="التسجيل والصيانة" text="إيقاف التسجيل أو تفعيل وضع الصيانة للعملاء.">
                <div class="space-y-4">
                    <ToggleField
                        v-model="form.features.customer_registration_enabled"
                        label="تسجيل العملاء مفتوح"
                        text="عند الإيقاف لن يستطيع الزائر إنشاء حساب جديد."
                    />
                    <ToggleField
                        v-model="form.features.maintenance_mode"
                        label="وضع الصيانة"
                        text="يظهر تنبيه للعملاء أثناء أعمال الصيانة."
                    />
                    <UiTextarea
                        v-model="form.features.maintenance_message"
                        label="رسالة الصيانة"
                        :error="form.errors['features.maintenance_message']"
                    />
                </div>
            </FormSection>
        </div>

        <div class="flex justify-end">
            <UiButton type="submit" :disabled="form.processing">حفظ كل الإعدادات</UiButton>
        </div>
    </form>
</template>
