<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ElMessageBox } from 'element-plus';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { dateTime, money, orderStatusType } from '@/utils';

const props = defineProps({ order: Object });

const cancel = async () => {
    const ok = await ElMessageBox.confirm('La orden se cancelará y el stock reservado volverá a estar disponible.', 'Cancelar orden', {
        type: 'warning', confirmButtonText: 'Cancelar orden', cancelButtonText: 'Volver',
    }).then(() => true, () => false);
    if (!ok) return;
    router.post(route('admin.orders.cancel', props.order.number), {}, { preserveScroll: true });
};
const reviewed = () => router.post(route('admin.orders.reviewed', props.order.number), {}, { preserveScroll: true });
</script>

<template>
    <Head :title="`Orden ${order.number}`" />
    <AdminLayout title="Detalle de orden">
        <el-alert v-if="order.needs_review" type="error" show-icon :closable="false" class="mb"
            title="Requiere revisión: el pago llegó con un monto distinto o después de liberar un stock que ya no estaba disponible.">
            <el-button size="small" class="mt" @click="reviewed">Marcar como revisada</el-button>
        </el-alert>

        <div class="grid grid-2">
            <div class="card">
                <div class="row spread mb">
                    <h2 style="margin: 0" class="mono">{{ order.number }}</h2>
                    <el-tag :type="orderStatusType(order.status)">{{ order.status_label }}</el-tag>
                </div>
                <el-descriptions :column="1" size="small" border>
                    <el-descriptions-item label="Creada">{{ dateTime(order.created_at) }}</el-descriptions-item>
                    <el-descriptions-item label="Pagada">{{ dateTime(order.paid_at) }}</el-descriptions-item>
                    <el-descriptions-item v-if="order.status === 'pending'" label="Reserva hasta">{{ dateTime(order.reserved_until) }}</el-descriptions-item>
                    <el-descriptions-item label="Pasarela">{{ order.payment_gateway }}</el-descriptions-item>
                    <el-descriptions-item label="ID de pago"><span class="mono">{{ order.payment_id ?? '—' }}</span></el-descriptions-item>
                    <el-descriptions-item label="Estado en pasarela">{{ order.payment_status ?? '—' }}</el-descriptions-item>
                </el-descriptions>
                <el-button v-if="order.status === 'pending'" type="danger" plain class="mt" @click="cancel">Cancelar orden</el-button>
            </div>
            <div class="card">
                <h2>Cliente y entrega</h2>
                <p><strong>{{ order.customer.name }}</strong><br /><span class="muted">{{ order.customer.email }} · {{ order.customer.phone ?? 'sin teléfono' }}</span></p>
                <p class="small"><strong>Recibe:</strong> {{ order.shipping_name }} · {{ order.shipping_phone }}<br />{{ order.shipping_address }}</p>
                <p v-if="order.notes" class="small muted">Notas: {{ order.notes }}</p>
            </div>
        </div>

        <div class="card mt" style="padding: 0">
            <el-table :data="order.items">
                <el-table-column label="Producto" min-width="220">
                    <template #default="{ row }">
                        <Link v-if="row.product_id" :href="route('admin.products.show', row.product_id)">{{ row.name }}</Link>
                        <span v-else>{{ row.name }}</span>
                        <div class="small muted mono">{{ row.sku }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="Precio" width="120" align="right"><template #default="{ row }">{{ money(row.unit_price) }}</template></el-table-column>
                <el-table-column label="Cant." prop="quantity" width="80" align="center" />
                <el-table-column label="Total" width="130" align="right"><template #default="{ row }"><span class="price">{{ money(row.line_total) }}</span></template></el-table-column>
            </el-table>
            <div class="row spread" style="padding: 16px 20px"><strong>Total</strong><span class="price" style="font-size: 1.3rem">{{ money(order.total) }}</span></div>
        </div>
    </AdminLayout>
</template>
