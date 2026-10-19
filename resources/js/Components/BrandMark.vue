<script setup>
// Isotipo de Mr Tecnología (punto + ondas). Para el logo completo usa BrandLogo.
defineProps({ size: { type: [Number, String], default: 28 } });
</script>

<template>
    <svg :width="size" :height="size" viewBox="0 0 100 100" aria-hidden="true" focusable="false">
        <circle cx="50" cy="50" r="8.00" fill="#8A1FD0"/>
        <path d="M35.88 37.29A19.00 19.00 0 0 0 35.88 62.71" fill="none" stroke="#93C722" stroke-width="6.50" stroke-linecap="round"/>
        <path d="M64.12 37.29A19.00 19.00 0 0 1 64.12 62.71" fill="none" stroke="#1FA0CB" stroke-width="6.50" stroke-linecap="round"/>
        <path d="M25.91 30.49A31.00 31.00 0 0 0 25.91 69.51" fill="none" stroke="#93C722" stroke-width="6.50" stroke-linecap="round"/>
        <path d="M74.09 30.49A31.00 31.00 0 0 1 74.09 69.51" fill="none" stroke="#1FA0CB" stroke-width="6.50" stroke-linecap="round"/>
        <path d="M15.21 24.73A43.00 43.00 0 0 0 15.21 75.27" fill="none" stroke="#93C722" stroke-width="6.50" stroke-linecap="round"/>
        <path d="M84.79 24.73A43.00 43.00 0 0 1 84.79 75.27" fill="none" stroke="#1FA0CB" stroke-width="6.50" stroke-linecap="round"/>
    </svg>
</template>
