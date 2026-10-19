<script setup>
import { computed, onBeforeUnmount, onMounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import { dateTime, money, orderStatusType } from '@/utils';

const props = defineProps({ order: Object });

const result = computed(() => ({
    paid: { icon: 'success', title: '¡Pago confirmado!', text: 'Gracias por tu compra. Te contactaremos para coordinar la entrega.' },
    pending: { icon: 'warning', title: 'Pago pendiente', text: 'Si ya pagaste, la confirmación puede tardar unos segundos.' },
    failed: { icon: 'error', title: 'Pago rechazado', text: 'El stock se liberó. Puedes volver a intentarlo desde el carrito.' },
    expired: { icon: 'info', title: 'Orden expirada', text: 'El tiempo de reserva terminó y el stock se liberó.' },
    cancelled: { icon: 'info', title: 'Orden cancelada', text: '' },
})[props.order.status]);

// Mientras está pendiente, refrescamos por si llega el webhook.
let poll;
onMounted(() => {
    if (props.order.status === 'pending') {
        poll = setInterval(() => router.reload({ only: ['order'] }), 5000);
    }
});
watch(() => props.order.status, (status) => status !== 'pending' && clearInterval(poll));
onBeforeUnmount(() => clearInterval(poll));

const pay = () => router.post(route('orders.pay', props.order.number));
</script>

<template>
    <Head :title="`Orden ${order.number}`" />
    <ShopLayout>
        <el-result :icon="result.icon" :title="result.title" :sub-title="result.text">
            <template #extra>
                <div class="row" style="justify-content: center">
                    <el-button v-if="order.can_pay" type="primary" @click="pay">Ir a pagar</el-button>
                    <Link :href="route('shop.index')"><el-button>Seguir comprando</el-button></Link>
                    <Link v-if="order.status === 'paid'" :href="route('support.index')"><el-button>Agendar soporte técnico</el-button></Link>
                </div>
            </template>
        </el-result>

        <div class="card" style="max-width: 760px; margin-inline: auto">
            <div class="row spread mb">
                <h2 style="margin: 0">Orden <span class="mono">{{ order.number }}</span></h2>
                <el-tag :type="orderStatusType(order.status)">{{ order.status_label }}</el-tag>
            </div>
            <p class="muted small">Creada el {{ dateTime(order.created_at) }}<template v-if="order.paid_at"> · Pagada el {{ dateTime(order.paid_at) }}</template></p>
            <el-table :data="order.items" class="mt">
                <el-table-column label="Producto" prop="name" min-width="200" />
                <el-table-column label="Cant." prop="quantity" width="80" align="center" />
                <el-table-column label="Precio" width="120" align="right">
                    <template #default="{ row }">{{ money(row.unit_price) }}</template>
                </el-table-column>
                <el-table-column label="Total" width="130" align="right">
                    <template #default="{ row }"><span class="price">{{ money(row.line_total) }}</span></template>
                </el-table-column>
            </el-table>
            <div class="row spread mt"><strong>Total</strong><span class="price" style="font-size: 1.3rem">{{ money(order.total) }}</span></div>
            <el-divider />
            <p class="small"><strong>Entrega:</strong> {{ order.shipping_name }} · {{ order.shipping_phone }}<br />{{ order.shipping_address }}</p>
            <p v-if="order.notes" class="small muted">{{ order.notes }}</p>
        </div>
    </ShopLayout>
</template>
