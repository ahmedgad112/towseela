<script setup>
defineProps({
    name: { type: String, required: true },
});
</script>

<template>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-[1.15em] shrink-0" aria-hidden="true">
        <path v-if="name === 'home'" d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z" />
        <path v-else-if="name === 'search'" d="m20 20-3.5-3.5M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
        <path v-else-if="name === 'bag'" d="M7 8V7a5 5 0 0 1 10 0v1M6 8h12l-1 13H7L6 8Z" />
        <path v-else-if="name === 'user'" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm8 8a8 8 0 0 0-16 0" />
        <path v-else-if="name === 'cart'" d="M4 5h2l1.5 10h11L20 8H7M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm8 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" />
        <path v-else-if="name === 'pin'" d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11Zm0-8.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
        <path v-else-if="name === 'clock'" d="M12 21a9 9 0 1 0-9-9 9 9 0 0 0 9 9Zm0-13v5l3 2" />
        <path v-else-if="name === 'utensils'" d="M8 3v7M6 3v5a2 2 0 0 0 4 0V3M16 3v18M14 3h6v5a3 3 0 0 1-3 3h-1" />
        <path v-else-if="name === 'coffee'" d="M5 9h11v4a5 5 0 0 1-5 5H10a5 5 0 0 1-5-5Zm11 1h2a3 3 0 0 1 0 6h-2M6 21h10M8 3v2M12 3v2" />
        <path v-else-if="name === 'basket'" d="M4 9h16l-1.4 10.2A2 2 0 0 1 16.6 21H7.4a2 2 0 0 1-2-1.8Zm2-4 2-3h8l2 3" />
        <path v-else-if="name === 'plus'" d="M12 5v14M5 12h14" />
        <path v-else-if="name === 'star'" d="m12 4 2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 16.8 7.2 18.9l.9-5.4L4.2 9.7l5.4-.8Z" />
        <path v-else-if="name === 'logout'" d="M10 7V5a1 1 0 0 1 1-1h8v16h-8a1 1 0 0 1-1-1v-2M4 12h10M7 9l-3 3 3 3" />
        <path v-else-if="name === 'truck'" d="M3 7h11v10H3zm11 3h4l3 3v4h-7M7 20a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm10 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
        <path v-else-if="name === 'phone'" d="M8 4h8l1 5-3 2-2 5-3-2-5 1v-3a12 12 0 0 1 4-8Z" />
        <path v-else-if="name === 'dashboard'" d="M4 4h7v9H4Zm9 0h7v5h-7ZM4 15h7v5H4Zm9-4h7v9h-7Z" />
        <path v-else-if="name === 'orders'" d="M8 6h13M8 12h13M8 18h13M4 6h.01M4 12h.01M4 18h.01" />
        <path v-else-if="name === 'store'" d="M4 8 6 4h12l2 4v2a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0Zm2 4v8h12v-8" />
        <path v-else-if="name === 'tag'" d="M12 3H5v7l8.5 8.5a2 2 0 0 0 2.8 0l4.2-4.2a2 2 0 0 0 0-2.8Zm-5.5 4a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
        <path v-else-if="name === 'box'" d="m12 3 8 4.5v9L12 21 4 16.5v-9Zm0 9 8-4.5M12 12 4 7.5M12 12v9" />
        <path v-else-if="name === 'ticket'" d="M4 8a2 2 0 0 0 2-2h12a2 2 0 0 0 2 2v8a2 2 0 0 0-2 2H6a2 2 0 0 0-2-2Zm8-2v2m0 8v2m0-8v4" />
        <path v-else-if="name === 'map'" d="m9 5-5 2v14l5-2 6 2 5-2V3l-5 2Zm0 0v14m6-12v14" />
        <path v-else-if="name === 'staff'" d="M16 13a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm-8 8a6 6 0 0 1 12 0M7 13a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm-4 8a5 5 0 0 1 5-4" />
        <path v-else-if="name === 'shield'" d="M12 3 5 6v6c0 5 3.4 7.8 7 9 3.6-1.2 7-4 7-9V6Z" />
        <path v-else-if="name === 'chart'" d="M4 19h16M7 16v-5m5 5V8m5 8V5" />
        <path v-else-if="name === 'settings'" d="M12 15a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm8-3a7.5 7.5 0 0 0-.2-1.6l2-1.5-2-3.5-2.4.7a7.7 7.7 0 0 0-2.8-1.6L14 2h-4l-.6 2.5a7.7 7.7 0 0 0-2.8 1.6L4.2 5.4l-2 3.5 2 1.5A7.5 7.5 0 0 0 4 12a7.5 7.5 0 0 0 .2 1.6l-2 1.5 2 3.5 2.4-.7a7.7 7.7 0 0 0 2.8 1.6L10 22h4l.6-2.5a7.7 7.7 0 0 0 2.8-1.6l2.4.7 2-3.5-2-1.5A7.5 7.5 0 0 0 20 12Z" />
        <path v-else-if="name === 'audit'" d="M8 3h8a2 2 0 0 1 2 2v16l-6-3-6 3V5a2 2 0 0 1 2-2Zm2 6h4M10 12h4" />
        <path v-else-if="name === 'menu'" d="M4 7h16M4 12h16M4 17h16" />
        <path v-else-if="name === 'close'" d="M6 6l12 12M18 6 6 18" />
        <path v-else-if="name === 'filter'" d="M4 6h16l-6 7v6l-4-2v-4Z" />
        <path v-else-if="name === 'check'" d="m5 13 4 4 10-10" />
        <path v-else-if="name === 'alert'" d="M12 4 3 19h18Zm0 6v4m0 3h.01" />
        <path v-else-if="name === 'note'" d="M7 4h7l4 4v12H7Zm7 0v4h4M9 12h6M9 16h4" />
        <path v-else-if="name === 'headset'" d="M5 12a7 7 0 0 1 14 0v5a2 2 0 0 1-2 2h-2v-6h4M5 17h2v-5H5Zm7 5h2" />
        <path v-else-if="name === 'dispatch'" d="M4 19V5m0 14 6-3 6 3 6-3M10 16V5m6 14V8" />
        <path v-else-if="name === 'crown'" d="m3 8 4 9h10l4-9-5 3-4-5-4 5Z" />
        <path v-else-if="name === 'wallet'" d="M4 7h16v12H4Zm12 6h4V7H16" />
        <path v-else-if="name === 'history'" d="M4 12a8 8 0 1 0 2.3-5.7M4 4v4h4M12 8v5l3 2" />
        <path v-else d="M12 21a9 9 0 1 0-9-9 9 9 0 0 0 9 9Z" />
    </svg>
</template>
