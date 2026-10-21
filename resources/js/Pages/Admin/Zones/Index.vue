<script setup>
import { reactive } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import FormSection from '../../../Components/FormSection.vue';
import ToggleField from '../../../Components/ToggleField.vue';
import { canDo } from '../../../workspace';

defineOptions({ layout: AdminLayout });
defineProps({
    zones: { type: Array, default: () => [] },
    governorates: { type: Array, default: () => [] },
});
const page = usePage();
const form = useForm({
    name_ar: '',
    name_en: '',
    governorate: 'القاهرة',
    min_order: 0,
    fee: '',
    is_active: true,
});
const ruleDrafts = reactive({});
const draftFor = (zoneId) => {
    ruleDrafts[zoneId] ??= { min_order: 0, fee: '' };
    return ruleDrafts[zoneId];
};
const submitRule = (zone) => {
    router.post(`/admin/zones/${zone.id}/rules`, draftFor(zone.id), {
        onSuccess: () => {
            ruleDrafts[zone.id] = { min_order: 0, fee: '' };
        },
    });
};
</script>

<template>
    <PageHeader title="مناطق التوصيل" text="أضف المنطقة مع رسومها الأساسية في خطوة واحدة، ثم أضف قواعد أخرى حسب قيمة الطلب." />

    <div class="grid gap-4 xl:grid-cols-[minmax(0,22rem)_1fr]">
        <FormSection title="منطقة جديدة" text="مثال: المعادي في محافظة القاهرة، ورسوم 20 ج.م من أول الطلب.">
            <form v-if="canDo(page.props.auth, 'delivery_zones.manage')" class="space-y-4" @submit.prevent="form.post('/admin/zones', { onSuccess: () => form.reset() })">
                <UiInput v-model="form.name_ar" label="اسم المنطقة" placeholder="المعادي" :error="form.errors.name_ar" />
                <UiInput v-model="form.name_en" label="الاسم بالإنجليزية" placeholder="Maadi" :error="form.errors.name_en" />
                <label class="block space-y-1.5">
                    <span class="text-sm font-semibold text-navy">المحافظة</span>
                    <select v-model="form.governorate" class="app-select w-full">
                        <option v-for="governorate in governorates" :key="governorate" :value="governorate">{{ governorate }}</option>
                    </select>
                    <p v-if="form.errors.governorate" class="text-xs text-red-600">{{ form.errors.governorate }}</p>
                </label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <UiInput v-model="form.min_order" type="number" min="0" label="من قيمة طلب" :error="form.errors.min_order" />
                    <UiInput v-model="form.fee" type="number" min="0" label="رسوم التوصيل" :error="form.errors.fee" />
                </div>
                <ToggleField v-model="form.is_active" label="المنطقة مفعّلة" />
                <UiButton type="submit" :disabled="form.processing">إضافة المنطقة</UiButton>
            </form>
        </FormSection>

        <div class="space-y-4">
            <EmptyState v-if="!zones.length" title="لا توجد مناطق" text="أضف منطقة مثل المعادي أو مدينة نصر." />
            <section v-for="zone in zones" :key="zone.id" class="app-card space-y-4 p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-navy">{{ zone.name_ar }}</h3>
                        <p class="text-xs text-muted">محافظة {{ zone.governorate || 'غير محددة' }}</p>
                    </div>
                    <span class="text-xs text-muted">{{ zone.fee_rules?.length || 0 }} قاعدة</span>
                </div>
                <p v-for="rule in zone.fee_rules" :key="rule.id" class="text-sm text-muted">
                    من {{ rule.min_order }} ج.م : رسوم {{ rule.fee }} ج.م
                </p>
                <p v-if="!zone.fee_rules?.length" class="text-sm text-muted">لا توجد قواعد رسوم بعد.</p>
                <form
                    v-if="canDo(page.props.auth, 'delivery_zones.manage')"
                    class="grid gap-3 sm:grid-cols-[1fr_1fr_auto]"
                    @submit.prevent="submitRule(zone)"
                >
                    <UiInput v-model="draftFor(zone.id).min_order" type="number" min="0" label="من قيمة" />
                    <UiInput v-model="draftFor(zone.id).fee" type="number" min="0" label="الرسوم" />
                    <UiButton type="submit" variant="outline" class="self-end">إضافة قاعدة</UiButton>
                </form>
            </section>
        </div>
    </div>
</template>
