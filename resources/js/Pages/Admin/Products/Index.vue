<script setup>
import { reactive, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ElMessageBox } from 'element-plus';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ProductImage from '@/Components/ProductImage.vue';
import Pagination from '@/Components/Pagination.vue';
import { money } from '@/utils';

const props = defineProps({ products: Object, categories: Array, filters: Object });

const filters = reactive({
    q: props.filters.q ?? '',
    category_id: props.filters.category_id ? Number(props.filters.category_id) : null,
    stock: props.filters.stock ?? null,
    status: props.filters.status ?? null,
    featured: !!Number(props.filters.featured ?? 0),
});

let timer;
const apply = () => {
    const query = Object.fromEntries(
        Object.entries({ ...filters, featured: filters.featured ? 1 : null }).filter(([, v]) => v !== null && v !== ''),
    );
    router.get(route('admin.products.index'), query, { preserveState: true, replace: true });
};
watch(() => filters.q, () => {
    clearTimeout(timer);
    timer = setTimeout(apply, 350);
});
watch(() => [filters.category_id, filters.stock, filters.status, filters.featured], apply);

const toggleFeatured = (product) =>
    router.post(route('admin.products.featured', product.id), {}, { preserveScroll: true, preserveState: true });

const destroy = async (product) => {
    const ok = await ElMessageBox.confirm(
        `Se eliminará "${product.name}". Las órdenes y movimientos históricos se conservan.`,
        'Eliminar producto',
        { confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar', type: 'warning', confirmButtonClass: 'el-button--danger' },
    ).then(() => true, () => false);
    if (!ok) return;
    router.delete(route('admin.products.destroy', product.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Productos" />
    <AdminLayout title="Productos">
        <div class="page-header">
            <div class="row">
                <el-input v-model="filters.q" placeholder="Nombre, SKU o código de barras" clearable style="width: 260px" data-barcode-search>
                    <template #prefix><el-icon><IconSearch /></el-icon></template>
                </el-input>
                <el-select v-model="filters.category_id" placeholder="Categoría" clearable style="width: 170px">
                    <el-option v-for="c in categories" :key="c.id" :label="c.name" :value="c.id" />
                </el-select>
                <el-select v-model="filters.stock" placeholder="Stock" clearable style="width: 140px">
                    <el-option label="Stock bajo" value="low" />
                    <el-option label="Sin stock" value="out" />
                </el-select>
                <el-select v-model="filters.status" placeholder="Estado" clearable style="width: 130px">
                    <el-option label="Activos" value="active" />
                    <el-option label="Inactivos" value="inactive" />
                </el-select>
                <el-checkbox v-model="filters.featured">En portada</el-checkbox>
            </div>
            <div class="row">
                <Link :href="route('admin.scanner')"><el-button><el-icon class="el-icon--left"><IconAim /></el-icon>Escáner</el-button></Link>
                <Link :href="route('admin.products.create')"><el-button type="primary"><el-icon class="el-icon--left"><IconPlus /></el-icon>Nuevo producto</el-button></Link>
            </div>
        </div>

        <div class="card" style="padding: 0">
            <el-table :data="products.data" empty-text="No hay productos">
                <el-table-column min-width="260" label="Producto">
                    <template #default="{ row }">
                        <div class="row" style="flex-wrap: nowrap">
                            <ProductImage :src="row.image_url" :alt="row.name" size="40" />
                            <div>
                                <Link :href="route('admin.products.show', row.id)" style="font-weight: 600">{{ row.name }}</Link>
                                <div class="small muted">{{ row.category ?? 'Sin categoría' }}</div>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="SKU" prop="sku" width="120" class-name="mono" />
                <el-table-column label="Código de barras" width="150">
                    <template #default="{ row }">
                        <span v-if="row.barcode" class="mono small">{{ row.barcode }}</span>
                        <el-tag v-else size="small" type="info">Sin código</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="Precio" width="120" align="right">
                    <template #default="{ row }">{{ money(row.price) }}</template>
                </el-table-column>
                <el-table-column label="Stock" width="100" align="right">
                    <template #default="{ row }">
                        <span class="price" :class="{ danger: row.low_stock }">{{ row.stock }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="Estado" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.is_active ? 'success' : 'info'" size="small">{{ row.is_active ? 'Activo' : 'Inactivo' }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column width="150" align="right" fixed="right">
                    <template #default="{ row }">
                        <el-tooltip :content="row.is_featured ? 'Quitar de la portada' : 'Mostrar en la portada'">
                            <el-button text size="small" :type="row.is_featured ? 'warning' : 'default'" :disabled="!row.is_active && !row.is_featured" :aria-label="row.is_featured ? 'Quitar de la portada' : 'Mostrar en la portada'" @click="toggleFeatured(row)">
                                <el-icon><IconStarFilled v-if="row.is_featured" /><IconStar v-else /></el-icon>
                            </el-button>
                        </el-tooltip>
                        <Link :href="route('admin.products.edit', row.id)"><el-button text size="small" aria-label="Editar"><el-icon><IconEdit /></el-icon></el-button></Link>
                        <el-button text size="small" type="danger" aria-label="Eliminar" @click="destroy(row)"><el-icon><IconDelete /></el-icon></el-button>
                    </template>
                </el-table-column>
            </el-table>
        </div>
        <Pagination :paginator="products" />
    </AdminLayout>
</template>
