<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import AddToCart from '@/Components/AddToCart.vue';
import ProductImage from '@/Components/ProductImage.vue';
import { money } from '@/utils';

defineProps({ product: Object, related: Array });
</script>

<template>
    <Head :title="product.name" />
    <ShopLayout>
        <el-breadcrumb class="mb">
            <el-breadcrumb-item><Link :href="route('shop.index')">Productos</Link></el-breadcrumb-item>
            <el-breadcrumb-item v-if="product.category">
                <Link :href="route('shop.index', { category: product.category.slug })">{{ product.category.name }}</Link>
            </el-breadcrumb-item>
            <el-breadcrumb-item>{{ product.name }}</el-breadcrumb-item>
        </el-breadcrumb>

        <div class="detail card">
            <div class="detail__image"><ProductImage :src="product.image_url" :alt="product.name" size="420" /></div>
            <div class="stack">
                <h1>{{ product.name }}</h1>
                <p class="muted small mono">SKU {{ product.sku }}</p>
                <p class="price" style="font-size: 1.8rem">{{ money(product.price) }}</p>
                <p v-if="product.stock > 0" class="success small">{{ product.stock }} unidades disponibles</p>
                <p v-else class="danger small">Sin stock por ahora</p>
                <AddToCart :product="product" with-quantity />
                <p v-if="product.description" class="description">{{ product.description }}</p>
            </div>
        </div>

        <template v-if="related.length">
            <h2 class="mt" style="margin-top: 32px">También te puede interesar</h2>
            <div class="related">
                <Link v-for="r in related" :key="r.id" :href="route('shop.product', r.slug)" class="card related__item">
                    <ProductImage :src="r.image_url" :alt="r.name" size="56" />
                    <div>
                        <div class="related__name">{{ r.name }}</div>
                        <div class="price small">{{ money(r.price) }}</div>
                    </div>
                </Link>
            </div>
        </template>
    </ShopLayout>
</template>

<style scoped>
.detail { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 32px; }
.detail__image :deep(.thumb) { width: 100% !important; height: auto !important; aspect-ratio: 1; }
.description { white-space: pre-line; line-height: 1.6; }
.related { display: grid; gap: 12px; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
.related__item { display: flex; gap: 12px; align-items: center; padding: 12px; color: var(--ink); text-decoration: none; }
.related__name { font-weight: 600; }
@media (max-width: 760px) { .detail { grid-template-columns: 1fr; } }
</style>
