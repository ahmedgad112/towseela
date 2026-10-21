<script setup>
defineProps({
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    modelValue: { type: [String, Number], default: '' },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="block space-y-1.5">
        <span v-if="label" class="text-sm font-semibold text-navy">{{ label }}</span>
        <textarea
            :value="modelValue"
            class="min-h-28 w-full rounded-2xl border border-line bg-white p-3 text-sm text-ink transition placeholder:text-muted/70 focus:border-gold"
            v-bind="$attrs"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
    </label>
</template>
