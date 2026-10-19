<script setup>
// Logo horizontal. light: versión con texto blanco para fondos oscuros.
defineProps({
    height: { type: [Number, String], default: 32 },
    light: { type: Boolean, default: false },
});
</script>

<template>
    <img
        :src="light ? '/brand/logo-horizontal-white.svg' : '/brand/logo-horizontal.svg'"
        alt="Mr Tecnología"
        :height="height"
        :style="{ height: `${height}px`, width: 'auto' }"
        class="brand-logo"
    />
</template>

<style scoped>
.brand-logo { display: block; }
</style>
