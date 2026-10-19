<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({ paginator: { type: Object, required: true } });

const go = (page) => {
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    router.get(url.pathname + url.search, {}, { preserveState: true, preserveScroll: false });
};
</script>

<template>
    <div v-if="paginator.last_page > 1" class="row mt" style="justify-content: center">
        <el-pagination
            background
            layout="prev, pager, next"
            :current-page="paginator.current_page"
            :page-size="paginator.per_page"
            :total="paginator.total"
            @current-change="go"
        />
    </div>
</template>
