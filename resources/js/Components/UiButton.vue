<script setup>
defineProps({
    href: { type: String, default: null },
    variant: { type: String, default: 'primary' },
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
});
</script>

<template>
    <component
        :is="href ? 'a' : 'button'"
        :href="href"
        :type="href ? undefined : type"
        :disabled="disabled"
        class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50"
        :class="{
            'bg-gold text-navy hover:bg-gold-hover': variant === 'primary',
            'bg-navy text-white hover:bg-navy-secondary': variant === 'navy',
            'border border-line bg-white text-ink hover:border-gold': variant === 'outline',
            'bg-red-50 text-red-700 hover:bg-red-100': variant === 'danger',
            'text-muted hover:text-navy': variant === 'ghost',
        }"
    >
        <slot />
    </component>
</template>
