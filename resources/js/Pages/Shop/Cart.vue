<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import ProductImage from '@/Components/ProductImage.vue';
import { money } from '@/utils';

defineProps({ cart: Object });

const update = (item, quantity) =>
    router.patch(route('cart.update', item.id), { quantity }, { preserveScroll: true, preserveState: true });

const remove = (item) => router.delete(route('cart.destroy', item.id), { preserveScroll: true });
</script>

<template>
    <Head title="Carrito" />
    <ShopLayout>
        <h1>Carrito</h1>

        <el-empty v-if="!cart.items.length" description="Tu carrito está vacío.">
            <Link :href="route('shop.index')"><el-button type="primary">Ver productos</el-button></Link>
        </el-empty>

        <div v-else class="layout">
            <div class="card">
                <div v-for="item in cart.items" :key="item.id" class="line">
                    <ProductImage :src="item.product.image_url" :alt="item.product.name" size="64" />
                    <div class="line__info">
                        <Link :href="route('shop.product', item.product.slug)" class="line__name">{{ item.product.name }}</Link>
                        <div class="muted small">{{ money(item.product.price) }} c/u</div>
                        <div v-if="item.exceeds_stock" class="danger small">Solo quedan {{ item.product.stock }} unidades</div>
                    </div>
                    <el-input-number
                        :model-value="item.quantity"
                        :min="1"
                        :max="Math.max(1, Math.min(item.product.stock, 99))"
                        size="small"
                        @change="(q) => update(item, q)"
                    />
                    <div class="price line__total">{{ money(item.line_total) }}</div>
                    <el-button text type="danger" aria-label="Quitar" @click="remove(item)">
                        <el-icon><IconDelete /></el-icon>
                    </el-button>
                </div>
            </div>

            <aside class="card summary">
                <h2>Resumen</h2>
                <div class="row spread"><span class="muted">Productos ({{ cart.count }})</span><span>{{ money(cart.subtotal) }}</span></div>
                <el-divider />
                <div class="row spread"><strong>Total</strong><span class="price" style="font-size: 1.3rem">{{ money(cart.subtotal) }}</span></div>
                <el-alert v-if="cart.has_stock_issues" type="warning" :closable="false" class="mt" title="Ajusta las cantidades sin stock suficiente para continuar." />
                <Link :href="route('checkout.create')" :class="{ disabled: cart.has_stock_issues }">
                    <el-button type="primary" size="large" class="mt" style="width: 100%" :disabled="cart.has_stock_issues">Continuar compra</el-button>
                </Link>
            </aside>
        </div>
    </ShopLayout>
</template>

<style scoped>
.layout { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 20px; align-items: start; }
.line { display: flex; gap: 14px; align-items: center; padding-block: 14px; border-bottom: 1px solid var(--line); }
.line:last-child { border-bottom: 0; }
.line__info { flex: 1; min-width: 0; }
.line__name { color: var(--ink); font-weight: 600; }
.line__total { width: 110px; text-align: right; }
.summary { position: sticky; top: 84px; }
.disabled { pointer-events: none; }
@media (max-width: 860px) {
    .layout { grid-template-columns: 1fr; }
    .line { flex-wrap: wrap; }
    .line__total { width: auto; margin-left: auto; }
}
</style>
