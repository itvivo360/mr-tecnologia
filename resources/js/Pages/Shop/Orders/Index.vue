<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { dateTime, money, orderStatusType } from '@/utils';

defineProps({ orders: Object });
</script>

<template>
    <Head title="Mis compras" />
    <ShopLayout>
        <h1>Mis compras</h1>
        <el-empty v-if="!orders.data.length" description="Aún no tienes compras." />
        <div v-else class="card" style="padding: 0">
            <el-table :data="orders.data">
                <el-table-column label="Orden" min-width="170">
                    <template #default="{ row }">
                        <Link :href="route('orders.show', row.number)" class="mono">{{ row.number }}</Link>
                    </template>
                </el-table-column>
                <el-table-column label="Fecha" min-width="160">
                    <template #default="{ row }">{{ dateTime(row.created_at) }}</template>
                </el-table-column>
                <el-table-column label="Productos" prop="items_count" width="100" align="center" />
                <el-table-column label="Total" width="130" align="right">
                    <template #default="{ row }"><span class="price">{{ money(row.total) }}</span></template>
                </el-table-column>
                <el-table-column label="Estado" width="170">
                    <template #default="{ row }"><el-tag :type="orderStatusType(row.status)">{{ row.status_label }}</el-tag></template>
                </el-table-column>
            </el-table>
        </div>
        <Pagination :paginator="orders" />
    </ShopLayout>
</template>
