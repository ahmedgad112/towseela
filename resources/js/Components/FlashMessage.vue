<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const success = computed(() => page.props.flash?.success);
const error = computed(() => page.props.flash?.error);
</script>

<template>
    <div v-if="success" class="no-print rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ success }}</div>
    <div v-if="error" class="no-print rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ error }}</div>
</template>
