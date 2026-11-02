<script setup>
defineProps({
    alt: {
        type: String,
        default: 'توسيلا',
    },
});

const src = '/image/logo.webp';
</script>

<template>
    <img
        :src="src"
        :alt="alt"
        width="128"
        height="128"
        decoding="async"
        class="shrink-0 object-cover"
    >
</template>
