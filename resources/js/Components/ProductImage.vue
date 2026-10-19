<script setup>
defineProps({
    src: { type: String, default: null },
    alt: { type: String, default: '' },
    size: { type: [Number, String], default: 48 },
});
</script>

<template>
    <img v-if="src" :src="src" :alt="alt" class="thumb" :style="{ width: `${size}px`, height: `${size}px` }" loading="lazy" />
    <div v-else class="thumb img-placeholder" :style="{ width: `${size}px`, height: `${size}px` }" aria-hidden="true">
        <el-icon :size="Number(size) / 2.5"><IconPicture /></el-icon>
    </div>
</template>
