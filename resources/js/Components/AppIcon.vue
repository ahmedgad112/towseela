<script setup>
defineProps({
    name: { type: String, required: true },
    size: { type: String, default: 'md' },
});

const sizes = {
    xs: 'size-3.5',
    sm: 'size-4',
    md: 'size-5',
    lg: 'size-6',
    xl: 'size-7',
};
</script>

<template>
    <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        :class="['shrink-0', sizes[size] || sizes.md]"
        aria-hidden="true"
    >
        <template v-if="name === 'home'">
            <path d="M4 10.5 12 3.8l8 6.7V20a1.2 1.2 0 0 1-1.2 1.2H14v-6.2h-4v6.2H5.2A1.2 1.2 0 0 1 4 20z" />
        </template>
        <template v-else-if="name === 'search'">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
        </template>
        <template v-else-if="name === 'bag'">
            <path d="M7.5 8V7a4.5 4.5 0 0 1 9 0v1" />
            <path d="M6.2 8h11.6l-.9 12.2a1.5 1.5 0 0 1-1.5 1.4H8.6a1.5 1.5 0 0 1-1.5-1.4Z" />
        </template>
        <template v-else-if="name === 'user'">
            <circle cx="12" cy="8" r="3.5" />
            <path d="M5.5 19.5a6.5 6.5 0 0 1 13 0" />
        </template>
        <template v-else-if="name === 'cart'">
            <path d="M3.5 5h1.8l1.7 10.2h10.4L20 8.2H7" />
            <circle cx="9.5" cy="19.5" r="1.3" />
            <circle cx="16.5" cy="19.5" r="1.3" />
        </template>
        <template v-else-if="name === 'pin'">
            <path d="M12 21s6.5-5.8 6.5-11a6.5 6.5 0 1 0-13 0c0 5.2 6.5 11 6.5 11Z" />
            <circle cx="12" cy="10" r="2.2" />
        </template>
        <template v-else-if="name === 'clock'">
            <circle cx="12" cy="12" r="8.5" />
            <path d="M12 7.5V12l3 2" />
        </template>
        <template v-else-if="name === 'utensils'">
            <path d="M8 3v7M6.2 3v5a1.8 1.8 0 0 0 3.6 0V3M16 3v18M14.2 3h5.6v5a2.8 2.8 0 0 1-2.8 2.8h-1" />
        </template>
        <template v-else-if="name === 'coffee'">
            <path d="M5.5 9h10.5v3.8a4.7 4.7 0 0 1-4.7 4.7H10.2A4.7 4.7 0 0 1 5.5 12.8Z" />
            <path d="M16 10.2h1.8a2.7 2.7 0 0 1 0 5.4H16M6.5 21h10M8.5 3.5v2M12 3.5v2" />
        </template>
        <template v-else-if="name === 'basket'">
            <path d="M4.5 9.2h15l-1.3 9.4a1.8 1.8 0 0 1-1.8 1.6H7.6a1.8 1.8 0 0 1-1.8-1.6Z" />
            <path d="m6.5 5.5 1.8-2.5h7.4l1.8 2.5" />
        </template>
        <template v-else-if="name === 'plus'">
            <path d="M12 5.5v13M5.5 12h13" />
        </template>
        <template v-else-if="name === 'star'">
            <path d="m12 3.8 2.2 4.6 5 .7-3.6 3.5.9 5.1L12 15.4 7.5 17.7l.9-5.1L4.8 9.1l5-.7Z" />
        </template>
        <template v-else-if="name === 'logout'">
            <path d="M10 7.2V5.5A1.5 1.5 0 0 1 11.5 4H19v16h-7.5A1.5 1.5 0 0 1 10 18.5v-1.7" />
            <path d="M4 12h10M7.5 8.8 4 12l3.5 3.2" />
        </template>
        <template v-else-if="name === 'truck'">
            <path d="M3.5 7.5h10.5v9.5H3.5z" />
            <path d="M14 10.5h3.4L20 13.4v3.6h-6" />
            <circle cx="7.2" cy="19.2" r="1.4" />
            <circle cx="16.8" cy="19.2" r="1.4" />
        </template>
        <template v-else-if="name === 'phone'">
            <path d="M8.2 4.5h7.6l.9 4.4-2.8 1.7-1.6 4.2-2.6-1.5-4.4.8v-2.4a10.5 10.5 0 0 1 2.9-7.2Z" />
        </template>
        <template v-else-if="name === 'dashboard'">
            <rect x="3.8" y="3.8" width="7" height="7.8" rx="1.4" />
            <rect x="13.2" y="3.8" width="7" height="4.6" rx="1.4" />
            <rect x="3.8" y="14.2" width="7" height="6" rx="1.4" />
            <rect x="13.2" y="11" width="7" height="9.2" rx="1.4" />
        </template>
        <template v-else-if="name === 'orders'">
            <path d="M9 6.5h11M9 12h11M9 17.5h11" />
            <circle cx="5" cy="6.5" r=".9" fill="currentColor" stroke="none" />
            <circle cx="5" cy="12" r=".9" fill="currentColor" stroke="none" />
            <circle cx="5" cy="17.5" r=".9" fill="currentColor" stroke="none" />
        </template>
        <template v-else-if="name === 'store'">
            <path d="m4.2 8.2 1.7-3.7h12.2l1.7 3.7v1.4a2.7 2.7 0 0 1-5.4 0 2.7 2.7 0 0 1-5.4 0 2.7 2.7 0 0 1-5.4 0Z" />
            <path d="M6.2 12.2v7.3h11.6v-7.3" />
        </template>
        <template v-else-if="name === 'tag'">
            <path d="M12.2 3.5H5.5v6.7l8.1 8.1a1.8 1.8 0 0 0 2.5 0l3.7-3.7a1.8 1.8 0 0 0 0-2.5Z" />
            <circle cx="8.3" cy="7.2" r="1.2" />
        </template>
        <template v-else-if="name === 'box'">
            <path d="m12 3.5 7.5 4.2v8.6L12 20.5 4.5 16.3V7.7Z" />
            <path d="m12 12 7.5-4.3M12 12 4.5 7.7M12 12v8.5" />
        </template>
        <template v-else-if="name === 'ticket'">
            <path d="M4.5 8.2A2 2 0 0 0 6.5 6h11a2 2 0 0 0 2 2.2v7.6a2 2 0 0 0-2 2.2h-11a2 2 0 0 0-2-2.2Z" />
            <path d="M12 6.5v2M12 15.5v2M12 10.5v3" />
        </template>
        <template v-else-if="name === 'map'">
            <path d="m9.2 5.2-5 1.8v13l5-1.8 5.6 1.8 5-1.8v-13l-5 1.8Z" />
            <path d="M9.2 5.2v13M14.8 7v13" />
        </template>
        <template v-else-if="name === 'staff'">
            <circle cx="15.5" cy="8" r="3.2" />
            <path d="M9.5 19.5a6 6 0 0 1 12 0" />
            <circle cx="7.5" cy="10" r="2.5" />
            <path d="M3.5 19.5a4.8 4.8 0 0 1 5.2-4" />
        </template>
        <template v-else-if="name === 'shield'">
            <path d="M12 3.5 5.2 6.2v5.5c0 4.7 3.1 7.4 6.8 8.6 3.7-1.2 6.8-3.9 6.8-8.6V6.2Z" />
            <path d="m9.4 12 1.8 1.8 3.6-3.8" />
        </template>
        <template v-else-if="name === 'chart'">
            <path d="M4 19.2h16" />
            <path d="M7.2 16.2V11M12 16.2V8.2M16.8 16.2V5.5" />
        </template>
        <template v-else-if="name === 'settings'">
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 13.2a1.5 1.5 0 0 0 .3-1.2 7.6 7.6 0 0 0 0-1.2 1.5 1.5 0 0 0-.3-1.2l1.4-1.1-1.8-3.1-1.9.6a7.2 7.2 0 0 0-2.1-1.2L13.6 2.5h-3.2l-.6 2.3a7.2 7.2 0 0 0-2.1 1.2l-1.9-.6-1.8 3.1 1.4 1.1a1.5 1.5 0 0 0-.3 1.2 7.6 7.6 0 0 0 0 1.2 1.5 1.5 0 0 0 .3 1.2l-1.4 1.1 1.8 3.1 1.9-.6a7.2 7.2 0 0 0 2.1 1.2l.6 2.3h3.2l.6-2.3a7.2 7.2 0 0 0 2.1-1.2l1.9.6 1.8-3.1Z" />
        </template>
        <template v-else-if="name === 'audit'">
            <path d="M8.2 3.5h7.6A1.8 1.8 0 0 1 17.6 5.3v15.2l-5.6-2.6-5.6 2.6V5.3A1.8 1.8 0 0 1 8.2 3.5Z" />
            <path d="M9.5 9.2h5M9.5 12.5h5" />
        </template>
        <template v-else-if="name === 'menu'">
            <path d="M4.5 7h15M4.5 12h15M4.5 17h15" />
        </template>
        <template v-else-if="name === 'close'">
            <path d="m6.5 6.5 11 11M17.5 6.5l-11 11" />
        </template>
        <template v-else-if="name === 'filter'">
            <path d="M4.5 6.2h15l-5.5 6.4v5.2l-4-2v-3.2Z" />
        </template>
        <template v-else-if="name === 'check'">
            <path d="m5.2 12.5 4.2 4.2 9.4-9.4" />
        </template>
        <template v-else-if="name === 'alert'">
            <path d="M12 4.2 3.5 19.2h17Z" />
            <path d="M12 10v4M12 16.8h.01" />
        </template>
        <template v-else-if="name === 'note'">
            <path d="M7.2 3.8h6.8l4 4v12.4H7.2Z" />
            <path d="M14 3.8v4h4M9.5 12h5M9.5 15.5h3.5" />
        </template>
        <template v-else-if="name === 'print'">
            <path d="M7 9V4.8h10V9" />
            <path d="M7 15.5H5.2A1.7 1.7 0 0 1 3.5 13.8v-3.6A1.7 1.7 0 0 1 5.2 8.5h13.6a1.7 1.7 0 0 1 1.7 1.7v3.6a1.7 1.7 0 0 1-1.7 1.7H17" />
            <path d="M7 13.5h10v6.2H7z" />
        </template>
        <template v-else-if="name === 'headset'">
            <path d="M5.2 12a6.8 6.8 0 0 1 13.6 0v4.5a1.8 1.8 0 0 1-1.8 1.8h-1.8v-5.5h3.6" />
            <path d="M5.2 16.5h1.8v-4.5H5.2Zm6.8 4.5h2" />
        </template>
        <template v-else-if="name === 'dispatch'">
            <path d="M4.5 19V5.5M4.5 19l5.5-2.8 5.5 2.8 5.5-2.8" />
            <path d="M10 16.2V5.5M15.5 19V8.2" />
        </template>
        <template v-else-if="name === 'crown'">
            <path d="m3.8 8.2 3.7 8.6h9l3.7-8.6-4.5 2.6L12 4.8 8.3 10.8Z" />
        </template>
        <template v-else-if="name === 'wallet'">
            <path d="M4.2 7.2h15.6v11.6H4.2Z" />
            <path d="M15.5 13.2h4.3V7.2H15.5" />
            <circle cx="16.8" cy="13.2" r=".8" fill="currentColor" stroke="none" />
        </template>
        <template v-else-if="name === 'history'">
            <path d="M4.5 12a7.5 7.5 0 1 0 2.2-5.3" />
            <path d="M4.5 4.5v3.8h3.8M12 8.2v4.5l2.8 1.8" />
        </template>
        <template v-else-if="name === 'spinner'">
            <path d="M12 3.5a8.5 8.5 0 1 1-8.5 8.5" />
        </template>
        <template v-else-if="name === 'eye'">
            <path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" />
            <circle cx="12" cy="12" r="2.8" />
        </template>
        <template v-else-if="name === 'eye-off'">
            <path d="m4 4 16 16" />
            <path d="M9.9 5.3A10.4 10.4 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a16.7 16.7 0 0 1-3.3 3.8" />
            <path d="M6.7 6.8C4.1 8.6 2.5 12 2.5 12S6 18.5 12 18.5c1.4 0 2.7-.3 3.8-.8" />
            <path d="M10.2 10.3a2.8 2.8 0 0 0 3.5 3.5" />
        </template>
        <template v-else>
            <circle cx="12" cy="12" r="8.5" />
        </template>
    </svg>
</template>
