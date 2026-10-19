<script setup>
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';

const page = usePage();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) ElMessage({ message: flash.success, type: 'success', grouping: true });
        if (flash?.error) ElMessage({ message: flash.error, type: 'error', grouping: true, duration: 5000 });
    },
    { immediate: true, deep: true },
);
</script>

<template><span hidden /></template>
