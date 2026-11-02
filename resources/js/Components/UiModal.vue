<script setup>
import { onMounted, onUnmounted, watch } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, required: true },
    text: { type: String, default: '' },
});

const emit = defineEmits(['close']);

const close = () => emit('close');

const onKeydown = (event) => {
    if (event.key === 'Escape' && props.open) {
        close();
    }
};

watch(() => props.open, (isOpen) => {
    document.body.classList.toggle('overflow-hidden', isOpen);
});

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.classList.remove('overflow-hidden');
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
            role="dialog"
            aria-modal="true"
        >
            <button
                type="button"
                class="absolute inset-0 bg-navy/50 backdrop-blur-[1px]"
                aria-label="إغلاق"
                @click="close"
            />
            <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-3xl border border-line bg-white shadow-[0_24px_60px_rgba(16,29,53,.18)]">
                <div class="flex items-start justify-between gap-3 border-b border-line px-5 py-4">
                    <div class="min-w-0">
                        <h3 class="text-lg font-bold text-navy">{{ title }}</h3>
                        <p v-if="text" class="mt-1 text-sm leading-6 text-muted">{{ text }}</p>
                    </div>
                    <button
                        type="button"
                        class="grid size-10 shrink-0 place-items-center rounded-2xl border border-line text-navy hover:border-gold"
                        aria-label="إغلاق"
                        @click="close"
                    >
                        <AppIcon name="close" />
                    </button>
                </div>
                <div class="max-h-[min(70vh,36rem)] overflow-y-auto px-5 py-5">
                    <slot />
                </div>
                <div v-if="$slots.footer" class="flex flex-wrap items-center justify-end gap-2 border-t border-line bg-canvas/60 px-5 py-4">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </Teleport>
</template>
