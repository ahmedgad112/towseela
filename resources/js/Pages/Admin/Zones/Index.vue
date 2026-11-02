<script setup>
import { reactive, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UiButton from '../../../Components/UiButton.vue';
import UiInput from '../../../Components/UiInput.vue';
import UiModal from '../../../Components/UiModal.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import ToggleField from '../../../Components/ToggleField.vue';
import { canDo } from '../../../workspace';

defineOptions({ layout: AdminLayout });
const props = defineProps({
    zones: { type: Array, default: () => [] },
    governorates: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});
const page = usePage();

const createOpen = ref(false);
const form = useForm({
    name_ar: '',
    name_en: '',
    governorate: props.governorates[0] || 'القاهرة',
    min_order: 0,
    fee: '',
    is_active: true,
});
const ruleDrafts = reactive({});

const draftFor = (zoneId) => {
    ruleDrafts[zoneId] ??= { min_order: 0, fee: '' };
    return ruleDrafts[zoneId];
};

const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.governorate = props.governorates[0] || 'القاهرة';
    form.is_active = true;
    createOpen.value = true;
};

const closeCreate = () => {
    createOpen.value = false;
    form.reset();
    form.clearErrors();
};

const saveCreate = () => {
    form.post('/admin/zones', {
        onSuccess: () => closeCreate(),
    });
};

const submitRule = (zone) => {
    router.post(`/admin/zones/${zone.id}/rules`, draftFor(zone.id), {
        onSuccess: () => {
            ruleDrafts[zone.id] = { min_order: 0, fee: '' };
        },
    });
};

const removeZone = (zone) => {
    if (window.confirm(`حذف منطقة «${zone.name_ar}» وجميع قواعد رسومها؟`)) {
        router.delete(`/admin/zones/${zone.id}`);
    }
};

const removeRule = (rule) => {
    if (window.confirm('حذف قاعدة الرسوم هذه؟')) {
        router.delete(`/admin/zone-rules/${rule.id}`);
    }
};
</script>

<template>
    <PageHeader title="مناطق التوصيل" text="أضف منطقة مع رسومها، أو احذف منطقة وقواعدها عند الحاجة.">
        <UiButton v-if="can.manage || canDo(page.props.auth, 'delivery_zones.manage')" type="button" @click="openCreate">
            منطقة جديدة
        </UiButton>
    </PageHeader>

    <EmptyState v-if="!zones.length" title="لا توجد مناطق" text="أضف منطقة مثل المعادي أو مدينة نصر." />

    <div v-else class="app-card-grid">
        <article v-for="zone in zones" :key="zone.id" class="app-item-card space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-bold text-navy">{{ zone.name_ar }}</p>
                    <p class="mt-1 text-sm text-muted">محافظة {{ zone.governorate || 'غير محددة' }}</p>
                    <p class="text-xs text-muted">
                        {{ zone.fee_rules_count ?? zone.fee_rules?.length ?? 0 }} قاعدة
                        <span v-if="zone.addresses_count"> · {{ zone.addresses_count }} عنوان</span>
                    </p>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <span
                        class="rounded-full px-2 py-0.5 text-xs font-semibold"
                        :class="zone.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                    >
                        {{ zone.is_active ? 'مفعّلة' : 'موقوفة' }}
                    </span>
                    <UiButton
                        v-if="can.manage || canDo(page.props.auth, 'delivery_zones.manage')"
                        type="button"
                        variant="outline"
                        class="text-red-600"
                        @click="removeZone(zone)"
                    >
                        حذف
                    </UiButton>
                </div>
            </div>

            <ul v-if="zone.fee_rules?.length" class="space-y-2">
                <li
                    v-for="rule in zone.fee_rules"
                    :key="rule.id"
                    class="flex items-center justify-between gap-2 text-sm text-muted"
                >
                    <span>من {{ rule.min_order }} ج.م · رسوم {{ rule.fee }} ج.م</span>
                    <button
                        v-if="can.manage || canDo(page.props.auth, 'delivery_zones.manage')"
                        type="button"
                        class="text-xs font-semibold text-red-600 hover:underline"
                        @click="removeRule(rule)"
                    >
                        مسح
                    </button>
                </li>
            </ul>
            <p v-else class="text-sm text-muted">لا توجد قواعد رسوم بعد.</p>

            <form
                v-if="can.manage || canDo(page.props.auth, 'delivery_zones.manage')"
                class="grid gap-3 sm:grid-cols-[1fr_1fr_auto]"
                @submit.prevent="submitRule(zone)"
            >
                <UiInput v-model="draftFor(zone.id).min_order" type="number" min="0" label="من قيمة" />
                <UiInput v-model="draftFor(zone.id).fee" type="number" min="0" label="الرسوم" />
                <UiButton type="submit" variant="outline" class="self-end">إضافة قاعدة</UiButton>
            </form>
        </article>
    </div>

    <UiModal
        :open="createOpen"
        title="منطقة توصيل جديدة"
        text="مثال: المعادي في محافظة القاهرة، ورسوم 20 ج.م من أول الطلب."
        @close="closeCreate"
    >
        <form id="zone-create-form" class="space-y-4" @submit.prevent="saveCreate">
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
        </form>
        <template #footer>
            <UiButton type="button" variant="outline" @click="closeCreate">إلغاء</UiButton>
            <UiButton type="submit" form="zone-create-form" :disabled="form.processing">إضافة المنطقة</UiButton>
        </template>
    </UiModal>
</template>
