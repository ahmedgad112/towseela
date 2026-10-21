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
        <select
            :value="modelValue"
            class="app-select w-full"
            v-bind="$attrs"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <slot />
        </select>
        <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
    </label>
</template>
