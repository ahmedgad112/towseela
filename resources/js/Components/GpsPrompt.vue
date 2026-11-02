<script setup>
import { Link } from '@inertiajs/vue3';
import AppIcon from './AppIcon.vue';
import UiButton from './UiButton.vue';

defineProps({
    open: { type: Boolean, default: false },
    locating: { type: Boolean, default: false },
    error: { type: String, default: '' },
    location: { type: Object, default: null },
    isCustomer: { type: Boolean, default: false },
    hasAddress: { type: Boolean, default: false },
});

defineEmits(['enable', 'later', 'close']);
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-end justify-center bg-navy/50 p-4 sm:items-center">
        <section class="w-full max-w-md space-y-4 rounded-[1.75rem] bg-white p-5 shadow-[var(--shadow-card)]">
            <div class="flex items-start justify-between gap-3">
                <span class="grid size-12 place-items-center rounded-2xl bg-navy text-gold">
                    <AppIcon name="pin" size="md" />
                </span>
                <button
                    type="button"
                    class="grid size-9 place-items-center rounded-full bg-canvas text-muted transition hover:text-navy"
                    @click="$emit('later')"
                >
                    <AppIcon name="close" size="sm" />
                </button>
            </div>

            <div v-if="!location" class="space-y-2">
                <h2 class="text-xl font-extrabold tracking-tight text-navy">فعّل الموقع لتحديد عنوانك</h2>
                <p class="text-sm leading-6 text-muted">
                    نحتاج إذن GPS عشان نحدد عنوان التوصيل تلقائياً ونسهّل إضافة عناوينك من الخريطة.
                </p>
            </div>
            <div v-else class="space-y-1.5">
                <h2 class="text-xl font-extrabold tracking-tight text-navy">تم تحديد موقعك</h2>
                <p class="text-sm leading-6 text-navy">{{ location.line1 }}</p>
                <p class="text-sm text-muted">{{ location.city }}</p>
            </div>

            <p v-if="error" class="rounded-2xl bg-red-50 px-3.5 py-2.5 text-sm leading-6 text-red-700">{{ error }}</p>

            <div class="flex flex-col gap-2.5">
                <UiButton v-if="!location" class="w-full" :disabled="locating" @click="$emit('enable')">
                    {{ locating ? 'جاري تحديد الموقع...' : 'تفعيل GPS' }}
                </UiButton>
                <Link
                    v-else-if="isCustomer && !hasAddress"
                    href="/addresses"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-gold px-4 text-sm font-semibold text-navy"
                    @click="$emit('close')"
                >
                    حفظ العنوان
                </Link>
                <UiButton v-else class="w-full" @click="$emit('close')">حسناً</UiButton>
                <UiButton v-if="!location" variant="ghost" class="w-full" @click="$emit('later')">لاحقاً</UiButton>
            </div>
        </section>
    </div>
</template>
