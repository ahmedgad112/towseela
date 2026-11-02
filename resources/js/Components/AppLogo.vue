<script setup>
defineProps({
    alt: {
        type: String,
        default: 'توسيلا',
    },
});

const src = '/image/logo.png';
</script>

<template>
    <img
        :src="src"
        :alt="alt"
        class="shrink-0 object-cover"
    >
</template>
