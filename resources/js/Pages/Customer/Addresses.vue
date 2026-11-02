<script setup>
import { computed, onMounted, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';
import UiButton from '../../Components/UiButton.vue';
import UiInput from '../../Components/UiInput.vue';
import UiTextarea from '../../Components/UiTextarea.vue';
import AppIcon from '../../Components/AppIcon.vue';
import LocationPicker from '../../Components/LocationPicker.vue';
import CustomerPageHeader from '../../Components/CustomerPageHeader.vue';
import { readStoredLocation } from '../../customerLocation';

defineOptions({ layout: CustomerLayout });

const props = defineProps({
    addresses: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] },
    governorates: { type: Array, default: () => [] },
    labels: { type: Array, default: () => [] },
});

const form = useForm({
    label: 'home',
    line1: '',
    city: 'القاهرة',
    delivery_zone_id: '',
    notes: '',
    lat: '',
    lng: '',
    is_default: true,
});

const governorateOptions = computed(() => {
    const names = [...props.governorates];

    if (form.city && !names.includes(form.city)) {
        names.unshift(form.city);
    }

    return names;
});

const visibleZones = computed(() => props.zones.filter((zone) => !zone.governorate || zone.governorate === form.city));

const selectedZone = computed(() => props.zones.find((zone) => String(zone.id) === String(form.delivery_zone_id)));

const applyLocation = (place) => {
    if (place.line1) {
        form.line1 = place.line1;
    }

    if (place.governorate || place.city) {
        form.city = place.governorate || place.city;
    }

    form.delivery_zone_id = place.delivery_zone_id ? String(place.delivery_zone_id) : '';

    if (place.lat !== undefined && place.lng !== undefined) {
        form.lat = place.lat;
        form.lng = place.lng;
    }
};

watch(() => form.city, (governorate) => {
    const zone = selectedZone.value;

    if (zone?.governorate && zone.governorate !== governorate) {
        form.delivery_zone_id = '';
    }
});

onMounted(() => {
    if (form.line1 || form.lat) {
        return;
    }

    const stored = readStoredLocation();

    if (stored) {
        applyLocation(stored);
    }
});
</script>

<template>
    <CustomerPageHeader
        eyebrow="التوصيل"
        title="عناويني"
        text="اكتب العنوان يدوياً أو حدده من الخريطة، ومنطقة التوصيل تُختار حسب المكان."
    />

    <div v-if="addresses.length" class="flex flex-col gap-3">
        <article v-for="address in addresses" :key="address.id" class="customer-surface flex items-start gap-3.5 p-4 sm:p-5">
            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-navy text-gold">
                <AppIcon name="pin" size="sm" />
            </span>
            <div class="min-w-0 space-y-1">
                <p class="font-extrabold tracking-tight text-navy">{{ address.line1 }}</p>
                <p class="text-sm leading-6 text-muted">
                    {{ address.delivery_zone?.name_ar }} · محافظة {{ address.delivery_zone?.governorate || address.city }}
                </p>
                <p v-if="address.lat && address.lng" class="text-xs font-medium text-gold">
                    تم تحديد الموقع على الخريطة
                </p>
            </div>
        </article>
    </div>

    <form class="customer-surface mb-4 space-y-3.5 p-4 sm:p-5" @submit.prevent="form.post('/addresses')">
        <p class="text-sm font-extrabold tracking-tight text-navy">إضافة عنوان جديد</p>
        <select v-model="form.label" class="app-select w-full">
            <option v-for="label in labels" :key="label.value" :value="label.value">{{ label.label }}</option>
        </select>
        <LocationPicker :lat="form.lat" :lng="form.lng" @located="applyLocation" />
        <UiInput v-model="form.line1" label="العنوان" placeholder="اكتب العنوان بالتفصيل" :error="form.errors.line1" />
        <label class="block space-y-1.5">
            <span class="text-sm font-semibold text-navy">المحافظة</span>
            <select v-model="form.city" class="app-select w-full">
                <option v-for="governorate in governorateOptions" :key="governorate" :value="governorate">{{ governorate }}</option>
            </select>
            <p v-if="form.errors.city" class="text-xs text-red-600">{{ form.errors.city }}</p>
        </label>
        <label class="block space-y-1.5">
            <span class="text-sm font-semibold text-navy">منطقة التوصيل</span>
            <select v-model="form.delivery_zone_id" class="app-select w-full">
                <option value="">اختر المنطقة داخل المحافظة</option>
                <option v-for="zone in visibleZones" :key="zone.id" :value="String(zone.id)">{{ zone.name_ar }}</option>
            </select>
            <p v-if="selectedZone" class="text-xs leading-5 text-muted">
                تم تحديد {{ selectedZone.name_ar }} في محافظة {{ selectedZone.governorate || form.city }}.
            </p>
            <p v-else-if="form.city && !visibleZones.length" class="text-xs text-red-600">
                لا توجد مناطق توصيل في محافظة {{ form.city }}.
            </p>
            <p v-if="form.errors.delivery_zone_id" class="text-xs text-red-600">{{ form.errors.delivery_zone_id }}</p>
        </label>
        <UiTextarea
            v-model="form.notes"
            label="ملاحظات الوصول"
            placeholder="علامة مميزة، رقم العمارة، الدور..."
            :error="form.errors.notes"
        />
        <UiButton type="submit" :disabled="form.processing">حفظ العنوان</UiButton>
    </form>
</template>
