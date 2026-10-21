<script setup>
defineProps({
    label: { type: String, required: true },
    text: { type: String, default: '' },
    modelValue: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-line bg-canvas/70 px-4 py-3">
        <input
            type="checkbox"
            class="mt-1 size-4 accent-gold"
            :checked="modelValue"
            @change="$emit('update:modelValue', $event.target.checked)"
        >
        <span>
            <span class="block text-sm font-semibold text-navy">{{ label }}</span>
            <span v-if="text" class="text-xs leading-5 text-muted">{{ text }}</span>
        </span>
    </label>
</template>
