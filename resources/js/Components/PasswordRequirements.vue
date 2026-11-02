<script setup>
import { computed } from 'vue';

const props = defineProps({
    password: { type: String, default: '' },
});

const rules = [
    {
        key: 'length',
        label: '٨ أحرف على الأقل',
        test: (value) => value.length >= 8,
    },
    {
        key: 'letters',
        label: 'حرف واحد على الأقل',
        test: (value) => /[A-Za-z\u0600-\u06FF]/.test(value),
    },
    {
        key: 'mixed',
        label: 'حرف كبير وحرف صغير',
        test: (value) => /[a-z]/.test(value) && /[A-Z]/.test(value),
    },
    {
        key: 'numbers',
        label: 'رقم واحد على الأقل',
        test: (value) => /\d/.test(value),
    },
    {
        key: 'symbols',
        label: 'رمز خاص مثل ! @ # $',
        test: (value) => /[^A-Za-z0-9\u0600-\u06FF]/.test(value),
    },
];

const checks = computed(() => rules.map((rule) => ({
    ...rule,
    passed: rule.test(props.password || ''),
})));
</script>

<template>
    <ul class="space-y-1.5 rounded-2xl border border-line/80 bg-canvas/70 px-3 py-2.5 text-[12px]" aria-live="polite">
        <li
            v-for="rule in checks"
            :key="rule.key"
            class="flex items-center gap-2 font-medium transition"
            :class="rule.passed ? 'text-emerald-700' : 'text-muted'"
        >
            <span
                class="grid size-4 shrink-0 place-items-center rounded-full text-[10px] font-bold"
                :class="rule.passed ? 'bg-emerald-100 text-emerald-700' : 'bg-white text-muted'"
                aria-hidden="true"
            >
                {{ rule.passed ? '✓' : '•' }}
            </span>
            <span>{{ rule.label }}</span>
        </li>
    </ul>
</template>
