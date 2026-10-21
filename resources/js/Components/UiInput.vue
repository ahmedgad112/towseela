<script setup>
defineProps({
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    modelValue: { type: [String, Number], default: '' },
    type: { type: String, default: 'text' },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="block space-y-1.5">
        <span v-if="label" class="text-sm font-semibold text-navy">{{ label }}</span>
        <input
            :type="type"
            :value="modelValue"
            class="min-h-12 w-full rounded-2xl border border-line bg-white px-4 text-sm text-ink shadow-[inset_0_1px_0_rgba(255,255,255,.8)] transition placeholder:text-muted/70 focus:border-gold"
            v-bind="$attrs"
            @input="$emit('update:modelValue', $event.target.value)"
        >
        <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
    </label>
</template>
