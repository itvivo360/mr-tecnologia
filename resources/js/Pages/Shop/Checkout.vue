<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import { money } from '@/utils';

const props = defineProps({ cart: Object, defaults: Object, reservationMinutes: Number });

const form = useForm({
    shipping_name: props.defaults.shipping_name ?? '',
    shipping_phone: props.defaults.shipping_phone ?? '',
    shipping_address: '',
    notes: '',
});

const submit = () => form.post(route('checkout.store'));
</script>

<template>
    <Head title="Finalizar compra" />
    <ShopLayout>
        <h1>Finalizar compra</h1>
        <div class="layout">
            <el-form class="card" label-position="top" @submit.prevent="submit">
                <h2>Datos de entrega</h2>
                <el-form-item label="Nombre de quien recibe" :error="form.errors.shipping_name">
                    <el-input v-model="form.shipping_name" autocomplete="name" />
                </el-form-item>
                <el-form-item label="Teléfono" :error="form.errors.shipping_phone">
                    <el-input v-model="form.shipping_phone" autocomplete="tel" />
                </el-form-item>
                <el-form-item label="Dirección" :error="form.errors.shipping_address">
                    <el-input v-model="form.shipping_address" autocomplete="street-address" placeholder="Calle, número, depto., comuna" />
                </el-form-item>
                <el-form-item label="Notas (opcional)" :error="form.errors.notes">
                    <el-input v-model="form.notes" type="textarea" :rows="2" maxlength="500" show-word-limit />
                </el-form-item>
                <el-alert type="info" :closable="false" show-icon
                    :title="`Reservamos tu stock por ${reservationMinutes} minutos mientras completas el pago en Mercado Pago.`" />
                <div class="row mt">
                    <Link :href="route('cart.index')"><el-button>Volver al carrito</el-button></Link>
                    <el-button type="primary" size="large" native-type="submit" :loading="form.processing">
                        Pagar {{ money(cart.subtotal) }}
                    </el-button>
                </div>
            </el-form>

            <aside class="card">
                <h2>Tu pedido</h2>
                <div v-for="item in cart.items" :key="item.id" class="row spread small line">
                    <span>{{ item.quantity }} × {{ item.product.name }}</span>
                    <span>{{ money(item.line_total) }}</span>
                </div>
                <el-divider />
                <div class="row spread"><strong>Total</strong><span class="price">{{ money(cart.subtotal) }}</span></div>
            </aside>
        </div>
    </ShopLayout>
</template>

<style scoped>
.layout { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 20px; align-items: start; }
.line { padding-block: 6px; flex-wrap: nowrap; }
@media (max-width: 860px) { .layout { grid-template-columns: 1fr; } }
</style>
