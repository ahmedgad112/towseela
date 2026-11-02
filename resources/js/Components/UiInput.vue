<script setup>
import { computed, ref } from 'vue';
import AppIcon from './AppIcon.vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    modelValue: { type: [String, Number], default: '' },
    type: { type: String, default: 'text' },
});

defineEmits(['update:modelValue']);

const revealed = ref(false);
const isPassword = computed(() => props.type === 'password');
const inputType = computed(() => (isPassword.value && revealed.value ? 'text' : props.type));
</script>

<template>
    <label class="block space-y-1.5">
        <span v-if="label" class="text-sm font-semibold text-navy">{{ label }}</span>
        <span class="relative block">
            <input
                :type="inputType"
                :value="modelValue"
                class="min-h-12 w-full rounded-2xl border border-line bg-white px-4 text-sm text-ink shadow-[inset_0_1px_0_rgba(255,255,255,.8)] transition placeholder:text-muted/70 focus:border-gold"
                :class="isPassword ? 'pe-12' : ''"
                v-bind="$attrs"
                @input="$emit('update:modelValue', $event.target.value)"
            >
            <button
                v-if="isPassword"
                type="button"
                class="absolute inset-y-0 end-0 z-10 grid w-12 place-items-center text-muted transition hover:text-navy"
                :aria-label="revealed ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
                :aria-pressed="revealed"
                tabindex="-1"
                @click.prevent.stop="revealed = !revealed"
            >
                <AppIcon :name="revealed ? 'eye-off' : 'eye'" size="sm" />
            </button>
        </span>
        <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
    </label>
</template>
