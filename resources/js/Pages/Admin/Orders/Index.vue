<script setup>
import { reactive, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { dateTime, money, orderStatusType } from '@/utils';

const props = defineProps({ orders: Object, filters: Object, statuses: Array });

const filters = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? null,
    review: !!Number(props.filters.review ?? 0),
});

let timer;
const apply = () => {
    const query = { q: filters.q || undefined, status: filters.status || undefined, review: filters.review ? 1 : undefined };
    router.get(route('admin.orders.index'), query, { preserveState: true, replace: true });
};
watch(() => filters.q, () => {
    clearTimeout(timer);
    timer = setTimeout(apply, 350);
});
watch(() => [filters.status, filters.review], apply);
</script>

<template>
    <Head title="Órdenes" />
    <AdminLayout title="Órdenes">
        <div class="row mb">
            <el-input v-model="filters.q" placeholder="N° de orden, cliente o correo" clearable style="width: 280px">
                <template #prefix><el-icon><IconSearch /></el-icon></template>
            </el-input>
            <el-select v-model="filters.status" placeholder="Estado" clearable style="width: 180px">
                <el-option v-for="s in statuses" :key="s.value" :label="s.label" :value="s.value" />
            </el-select>
            <el-checkbox v-model="filters.review">Requieren revisión</el-checkbox>
        </div>
        <div class="card" style="padding: 0">
            <el-table :data="orders.data" empty-text="Sin órdenes">
                <el-table-column label="Orden" min-width="170">
                    <template #default="{ row }">
                        <Link :href="route('admin.orders.show', row.number)" class="mono">{{ row.number }}</Link>
                        <el-tooltip v-if="row.needs_review" content="Requiere revisión"><el-icon class="danger" style="margin-left: 6px"><IconWarning /></el-icon></el-tooltip>
                    </template>
                </el-table-column>
                <el-table-column label="Cliente" min-width="200">
                    <template #default="{ row }">{{ row.customer }}<div class="small muted">{{ row.email }}</div></template>
                </el-table-column>
                <el-table-column label="Fecha" width="170"><template #default="{ row }">{{ dateTime(row.created_at) }}</template></el-table-column>
                <el-table-column label="Ítems" prop="items_count" width="80" align="center" />
                <el-table-column label="Total" width="130" align="right"><template #default="{ row }"><span class="price">{{ money(row.total) }}</span></template></el-table-column>
                <el-table-column label="Estado" width="170"><template #default="{ row }"><el-tag :type="orderStatusType(row.status)">{{ row.status_label }}</el-tag></template></el-table-column>
            </el-table>
        </div>
        <Pagination :paginator="orders" />
    </AdminLayout>
</template>
