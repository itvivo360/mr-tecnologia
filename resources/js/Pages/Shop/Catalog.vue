<script setup>
import { reactive, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import AddToCart from '@/Components/AddToCart.vue';
import ProductImage from '@/Components/ProductImage.vue';
import Pagination from '@/Components/Pagination.vue';
import { money } from '@/utils';

const props = defineProps({ products: Object, categories: Array, filters: Object });

const filters = reactive({
    q: props.filters.q ?? '',
    category: props.filters.category ?? '',
    sort: props.filters.sort ?? 'newest',
});

let timer;
const apply = () => {
    const query = Object.fromEntries(Object.entries(filters).filter(([, v]) => v));
    router.get(route('shop.index'), query, { preserveState: true, replace: true });
};
watch(() => filters.q, () => {
    clearTimeout(timer);
    timer = setTimeout(apply, 350);
});
watch(() => [filters.category, filters.sort], apply);
</script>

<template>
    <Head title="Productos" />
    <ShopLayout>
        <div class="page-header">
            <h1>Productos</h1>
            <div class="row filters">
                <el-input v-model="filters.q" placeholder="Buscar productos" clearable style="width: 240px">
                    <template #prefix><el-icon><IconSearch /></el-icon></template>
                </el-input>
                <el-select v-model="filters.sort" style="width: 170px">
                    <el-option label="Más recientes" value="newest" />
                    <el-option label="Menor precio" value="price_asc" />
                    <el-option label="Mayor precio" value="price_desc" />
                    <el-option label="Nombre" value="name" />
                </el-select>
            </div>
        </div>

        <div class="row mb chips">
            <el-check-tag :checked="!filters.category" @change="filters.category = ''">Todas</el-check-tag>
            <el-check-tag
                v-for="c in categories"
                :key="c.id"
                :checked="filters.category === c.slug"
                @change="filters.category = filters.category === c.slug ? '' : c.slug"
            >{{ c.name }}</el-check-tag>
        </div>

        <el-empty v-if="!products.data.length" description="No encontramos productos con esos filtros." />

        <div class="products">
            <article v-for="p in products.data" :key="p.id" class="product card">
                <Link :href="route('shop.product', p.slug)" class="product__image">
                    <ProductImage :src="p.image_url" :alt="p.name" size="200" />
                </Link>
                <div class="product__body">
                    <span class="muted small">{{ p.category }}</span>
                    <Link :href="route('shop.product', p.slug)" class="product__name">{{ p.name }}</Link>
                    <div class="row spread mt">
                        <span class="price">{{ money(p.price) }}</span>
                        <el-tag v-if="p.stock === 0" type="info" size="small">Agotado</el-tag>
                        <el-tag v-else-if="p.stock <= 5" type="warning" size="small">Últimas {{ p.stock }}</el-tag>
                    </div>
                    <AddToCart :product="p" size="small" class="mt" />
                </div>
            </article>
        </div>

        <Pagination :paginator="products" />
    </ShopLayout>
</template>

<style scoped>
.chips { gap: 8px; }
.products {
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
}
.product { padding: 0; overflow: hidden; display: flex; flex-direction: column; }
.product__image :deep(.thumb) { width: 100% !important; height: 180px !important; border: 0; border-radius: 0; }
.product__body { padding: 14px 16px 16px; display: flex; flex-direction: column; flex: 1; }
.product__name { color: var(--ink); font-weight: 600; margin-top: 2px; }
@media (max-width: 640px) {
    .filters, .filters > * { width: 100% !important; }
}
</style>
