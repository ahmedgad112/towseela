<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, default: 'الصورة' },
    text: { type: String, default: 'JPG أو PNG أو WEBP حتى 2 ميجا.' },
    error: { type: String, default: '' },
    preview: { type: String, default: '' },
    modelValue: { default: null },
});

const emit = defineEmits(['update:modelValue']);
const localPreview = computed(() => {
    if (props.modelValue instanceof File) {
        return URL.createObjectURL(props.modelValue);
    }

    return props.preview || '';
});

const choose = (event) => {
    emit('update:modelValue', event.target.files?.[0] || null);
};
</script>

<template>
    <div class="space-y-2">
        <p class="text-sm font-semibold text-navy">{{ label }}</p>
        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-dashed border-line bg-canvas px-3 py-3">
            <img v-if="localPreview" :src="localPreview" alt="" class="size-16 rounded-2xl object-cover">
            <span v-else class="grid size-16 place-items-center rounded-2xl bg-white text-xs text-muted">بدون صورة</span>
            <span class="min-w-0">
                <span class="block text-sm font-semibold text-navy">اختر صورة</span>
                <span class="block text-xs text-muted">{{ text }}</span>
            </span>
            <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="choose">
        </label>
        <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
    </div>
</template>
