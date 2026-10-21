<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {
    defaultMapCenter,
    loadLeaflet,
    requestBrowserLocation,
    reverseGeocode,
    searchPlaces,
} from '../customerLocation';
import AppIcon from './AppIcon.vue';
import UiButton from './UiButton.vue';

const props = defineProps({
    lat: { type: [Number, String], default: null },
    lng: { type: [Number, String], default: null },
});

const emit = defineEmits(['located']);

const page = usePage();
const mapEl = ref(null);
const query = ref('');
const results = ref([]);
const loading = ref(false);
const locating = ref(false);
const error = ref('');
const hasPin = computed(() => props.lat !== null && props.lat !== '' && props.lng !== null && props.lng !== '');

let map = null;
let marker = null;
let leaflet = null;
let searchTimer = null;

const applyPlace = (place) => {
    emit('located', place);
    error.value = '';
    results.value = [];
    query.value = place.line1 || '';
    moveMarker(Number(place.lat), Number(place.lng));
};

const moveMarker = (lat, lng) => {
    if (!map || !leaflet) {
        return;
    }

    const point = leaflet.latLng(lat, lng);

    if (!marker) {
        marker = leaflet.marker(point, {
            icon: leaflet.divIcon({
                className: 'towseela-map-pin',
                html: '<span></span>',
                iconSize: [22, 22],
                iconAnchor: [11, 11],
            }),
        }).addTo(map);
    } else {
        marker.setLatLng(point);
    }

    map.setView(point, Math.max(map.getZoom(), 16));
};

const locateFromGps = async () => {
    locating.value = true;
    error.value = '';

    try {
        const coords = await requestBrowserLocation();
        const place = await reverseGeocode(coords.lat, coords.lng);
        applyPlace(place);
    } catch (exception) {
        error.value = exception.message;
    } finally {
        locating.value = false;
    }
};

const locateFromMap = async (lat, lng) => {
    loading.value = true;
    error.value = '';

    try {
        applyPlace(await reverseGeocode(lat, lng));
    } catch (exception) {
        error.value = exception.message;
        moveMarker(lat, lng);
        emit('located', { lat, lng });
    } finally {
        loading.value = false;
    }
};

const search = async () => {
    const term = query.value.trim();

    if (term.length < 2) {
        results.value = [];
        return;
    }

    loading.value = true;
    error.value = '';

    try {
        results.value = await searchPlaces(term);
    } catch (exception) {
        error.value = exception.message;
        results.value = [];
    } finally {
        loading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(search, 400);
};

onMounted(async () => {
    leaflet = await loadLeaflet();
    const center = hasPin.value
        ? { lat: Number(props.lat), lng: Number(props.lng) }
        : defaultMapCenter(page);

    map = leaflet.map(mapEl.value, { zoomControl: true, attributionControl: true })
        .setView([center.lat, center.lng], hasPin.value ? 16 : 12);

    leaflet.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    map.on('click', (event) => locateFromMap(event.latlng.lat, event.latlng.lng));

    if (hasPin.value) {
        moveMarker(center.lat, center.lng);
    }

    window.setTimeout(() => map?.invalidateSize(), 120);
});

watch(() => [props.lat, props.lng], ([lat, lng]) => {
    if (lat === null || lat === '' || lng === null || lng === '') {
        return;
    }

    moveMarker(Number(lat), Number(lng));
});

onBeforeUnmount(() => {
    window.clearTimeout(searchTimer);
    map?.remove();
    map = null;
    marker = null;
});
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-col gap-2 sm:flex-row">
            <div class="relative grow">
                <input
                    v-model="query"
                    type="search"
                    placeholder="اكتب العنوان للبحث على الخريطة"
                    class="min-h-12 w-full rounded-2xl border border-line bg-white px-4 text-sm text-ink placeholder:text-muted/70 focus:border-gold"
                    @input="queueSearch"
                    @keydown.enter.prevent="search"
                >
                <ul
                    v-if="results.length"
                    class="absolute inset-x-0 top-full z-20 mt-1 max-h-56 overflow-auto rounded-2xl border border-line bg-white p-1 shadow-[var(--shadow-card)]"
                >
                    <li v-for="result in results" :key="`${result.lat}-${result.lng}-${result.line1}`">
                        <button
                            type="button"
                            class="flex w-full flex-col rounded-xl px-3 py-2 text-right text-sm hover:bg-canvas"
                            @click="applyPlace(result)"
                        >
                            <span class="font-semibold text-navy">{{ result.line1 }}</span>
                            <span class="text-xs text-muted">{{ result.place || result.city }}{{ result.governorate ? ` · ${result.governorate}` : '' }}</span>
                        </button>
                    </li>
                </ul>
            </div>
            <UiButton type="button" variant="navy" :disabled="locating" @click="locateFromGps">
                <span class="inline-flex items-center gap-2">
                    <AppIcon name="pin" />
                    {{ locating ? 'جاري تحديد موقعك...' : 'موقعي الحالي' }}
                </span>
            </UiButton>
        </div>

        <div class="overflow-hidden rounded-2xl border border-line">
            <div ref="mapEl" class="h-64 w-full" dir="ltr"></div>
        </div>

        <p v-if="hasPin" class="text-xs text-muted">اضغط على الخريطة لتعديل النقطة، أو اكتب العنوان يدوياً في الحقل بالأسفل.</p>
        <p v-else class="text-xs text-muted">حدد موقع التوصيل من الخريطة أو فعّل GPS.</p>
        <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
        <p v-if="loading" class="text-xs text-muted">جاري تحديث العنوان...</p>
    </div>
</template>
