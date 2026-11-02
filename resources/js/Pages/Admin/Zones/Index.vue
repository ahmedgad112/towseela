<script setup>
import { computed, reactive, ref } from 'vue';
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

const selectedGovernorate = ref('الكل');
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

const canManage = computed(() => props.can.manage || canDo(page.props.auth, 'delivery_zones.manage'));

const zonesByGovernorate = computed(() => {
    const groups = props.governorates.map((governorate) => ({
        governorate,
        zones: props.zones.filter((zone) => zone.governorate === governorate),
    }));

    const known = new Set(props.governorates);
    const orphans = props.zones.filter((zone) => !zone.governorate || !known.has(zone.governorate));

    if (orphans.length) {
        groups.push({
            governorate: 'غير مصنّفة',
            zones: orphans,
        });
    }

    return groups;
});

const visibleGroups = computed(() => {
    if (selectedGovernorate.value === 'الكل') {
        return zonesByGovernorate.value.filter((group) => group.zones.length > 0);
    }

    return zonesByGovernorate.value.filter((group) => group.governorate === selectedGovernorate.value);
});

const totalZones = computed(() => props.zones.length);

const draftFor = (zoneId) => {
    ruleDrafts[zoneId] ??= { min_order: 0, fee: '' };
    return ruleDrafts[zoneId];
};

const openCreate = (governorate = null) => {
    form.reset();
    form.clearErrors();
    form.governorate = governorate && props.governorates.includes(governorate)
        ? governorate
        : (props.governorates[0] || 'القاهرة');
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
        onSuccess: () => {
            selectedGovernorate.value = form.governorate;
            closeCreate();
        },
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
    <PageHeader title="مناطق التوصيل" text="أضف مناطق التوصيل داخل كل محافظة، مع رسومها وقواعدها.">
        <UiButton v-if="canManage" type="button" @click="openCreate()">
            منطقة جديدة
        </UiButton>
    </PageHeader>

    <div class="mb-4 flex flex-wrap gap-2">
        <button
            type="button"
            class="rounded-full px-3 py-1.5 text-sm font-semibold transition"
            :class="selectedGovernorate === 'الكل' ? 'bg-navy text-white' : 'bg-white text-muted ring-1 ring-line hover:text-navy'"
            @click="selectedGovernorate = 'الكل'"
        >
            الكل ({{ totalZones }})
        </button>
        <button
            v-for="group in zonesByGovernorate"
            :key="group.governorate"
            type="button"
            class="rounded-full px-3 py-1.5 text-sm font-semibold transition"
            :class="selectedGovernorate === group.governorate ? 'bg-navy text-white' : 'bg-white text-muted ring-1 ring-line hover:text-navy'"
            @click="selectedGovernorate = group.governorate"
        >
            {{ group.governorate }}
            <span class="opacity-70">({{ group.zones.length }})</span>
        </button>
    </div>

    <EmptyState
        v-if="!totalZones"
        title="لا توجد مناطق"
        text="اختر محافظة وأضف مناطقها مثل المعادي في القاهرة أو العجمي في الإسكندرية."
    />

    <div v-else class="space-y-6">
        <section v-for="group in visibleGroups" :key="group.governorate" class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-navy">محافظة {{ group.governorate }}</h3>
                    <p class="text-sm text-muted">
                        {{ group.zones.length ? `${group.zones.length} منطقة توصيل` : 'لا توجد مناطق بعد في هذه المحافظة' }}
                    </p>
                </div>
                <UiButton
                    v-if="canManage && group.governorate !== 'غير مصنّفة'"
                    type="button"
                    variant="outline"
                    @click="openCreate(group.governorate)"
                >
                    إضافة لمنطقة {{ group.governorate }}
                </UiButton>
            </div>

            <EmptyState
                v-if="!group.zones.length"
                title="لا مناطق هنا"
                :text="`أضف أول منطقة توصيل داخل محافظة ${group.governorate}.`"
            />

            <div v-else class="app-card-grid">
                <article v-for="zone in group.zones" :key="zone.id" class="app-item-card space-y-4">
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
                                v-if="canManage"
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
                                v-if="canManage"
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
                        v-if="canManage"
                        class="grid gap-3 sm:grid-cols-[1fr_1fr_auto]"
                        @submit.prevent="submitRule(zone)"
                    >
                        <UiInput v-model="draftFor(zone.id).min_order" type="number" min="0" label="من قيمة" />
                        <UiInput v-model="draftFor(zone.id).fee" type="number" min="0" label="الرسوم" />
                        <UiButton type="submit" variant="outline" class="self-end">إضافة قاعدة</UiButton>
                    </form>
                </article>
            </div>
        </section>
    </div>

    <UiModal
        :open="createOpen"
        title="منطقة توصيل جديدة"
        text="اختر المحافظة أولاً، ثم أضف المنطقة داخلها مع رسوم التوصيل."
        @close="closeCreate"
    >
        <form id="zone-create-form" class="space-y-4" @submit.prevent="saveCreate">
            <label class="block space-y-1.5">
                <span class="text-sm font-semibold text-navy">المحافظة</span>
                <select v-model="form.governorate" class="app-select w-full">
                    <option v-for="governorate in governorates" :key="governorate" :value="governorate">{{ governorate }}</option>
                </select>
                <p v-if="form.errors.governorate" class="text-xs text-red-600">{{ form.errors.governorate }}</p>
            </label>
            <UiInput v-model="form.name_ar" label="اسم المنطقة داخل المحافظة" placeholder="المعادي" :error="form.errors.name_ar" />
            <UiInput v-model="form.name_en" label="الاسم بالإنجليزية" placeholder="Maadi" :error="form.errors.name_en" />
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
