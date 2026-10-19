<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ProductImage from '@/Components/ProductImage.vue';
import StockMoveDialog from '@/Components/StockMoveDialog.vue';
import Pagination from '@/Components/Pagination.vue';
import { dateTime, money } from '@/utils';

defineProps({ product: Object, movements: Object });

const dialog = ref(false);
const dialogType = ref('in');
const open = (type) => {
    dialogType.value = type;
    dialog.value = true;
};

const typeTag = (t) => ({ in: 'success', release: 'success', out: 'danger', sale: 'primary', adjustment: 'warning' })[t] ?? 'info';
</script>

<template>
    <Head :title="product.name" />
    <AdminLayout title="Detalle de producto">
        <div class="card mb">
            <div class="row" style="align-items: flex-start; gap: 20px">
                <ProductImage :src="product.image_url" :alt="product.name" size="120" />
                <div style="flex: 1; min-width: 220px">
                    <div class="row">
                        <h1 style="margin: 0">{{ product.name }}</h1>
                        <el-tag :type="product.is_active ? 'success' : 'info'" size="small">{{ product.is_active ? 'Activo' : 'Inactivo' }}</el-tag>
                        <el-tag v-if="product.is_featured" type="warning" size="small">En portada</el-tag>
                    </div>
                    <p class="muted small mono">SKU {{ product.sku }} · Código {{ product.barcode ?? 'sin asociar' }} · {{ product.category ?? 'Sin categoría' }}</p>
                    <p class="price">{{ money(product.price) }}</p>
                </div>
                <div class="stock-box" :class="{ low: product.low_stock }">
                    <div class="stat-label">Stock</div>
                    <div class="stat-value">{{ product.stock }}</div>
                    <div class="small muted">mínimo {{ product.min_stock }}</div>
                </div>
            </div>
            <div class="row mt">
                <el-button type="primary" @click="open('in')"><el-icon class="el-icon--left"><IconPlus /></el-icon>Ingreso</el-button>
                <el-button @click="open('out')"><el-icon class="el-icon--left"><IconMinus /></el-icon>Salida</el-button>
                <el-button @click="open('count')">Conteo de inventario</el-button>
                <Link :href="route('admin.products.edit', product.id)" style="margin-left: auto"><el-button><el-icon class="el-icon--left"><IconEdit /></el-icon>Editar</el-button></Link>
            </div>
        </div>

        <div class="card" style="padding: 0">
            <h2 style="padding: 16px 20px 0">Movimientos</h2>
            <el-table :data="movements.data" empty-text="Sin movimientos">
                <el-table-column label="Fecha" width="170"><template #default="{ row }">{{ dateTime(row.created_at) }}</template></el-table-column>
                <el-table-column label="Tipo" width="120"><template #default="{ row }"><el-tag :type="typeTag(row.type)" size="small">{{ row.type_label }}</el-tag></template></el-table-column>
                <el-table-column label="Cantidad" width="100" align="right">
                    <template #default="{ row }"><span class="price" :class="row.quantity > 0 ? 'success' : 'danger'">{{ row.quantity > 0 ? '+' : '' }}{{ row.quantity }}</span></template>
                </el-table-column>
                <el-table-column label="Saldo" prop="stock_after" width="90" align="right" />
                <el-table-column label="Motivo" prop="reason" min-width="200" />
                <el-table-column label="Usuario" width="150"><template #default="{ row }">{{ row.user ?? 'Sistema' }}</template></el-table-column>
            </el-table>
        </div>
        <Pagination :paginator="movements" />

        <StockMoveDialog v-model="dialog" :product="product" :initial-type="dialogType" />
    </AdminLayout>
</template>

<style scoped>
.stock-box { text-align: center; padding: 12px 20px; border-radius: var(--radius); background: var(--el-color-primary-light-9); }
.stock-box.low { background: #fef2f2; }
.stock-box.low .stat-value { color: #b91c1c; }
</style>
