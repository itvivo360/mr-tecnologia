<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { appointmentStatusType, dateTime, money } from '@/utils';

defineProps({ stats: Object, lowStock: Array, nextAppointments: Array });
</script>

<template>
    <Head title="Backoffice" />
    <AdminLayout title="Resumen">
        <div class="grid grid-stats">
            <div class="card"><div class="stat-label">Ventas del mes</div><div class="stat-value">{{ money(stats.sales_month) }}</div></div>
            <div class="card"><div class="stat-label">Órdenes pagadas (mes)</div><div class="stat-value">{{ stats.orders_month }}</div></div>
            <div class="card"><div class="stat-label">Productos</div><div class="stat-value">{{ stats.products }}</div></div>
            <Link :href="route('admin.products.index', { stock: 'low' })" class="card stat-link">
                <div class="stat-label">Con stock bajo</div>
                <div class="stat-value" :class="{ danger: stats.low_stock }">{{ stats.low_stock }}</div>
            </Link>
            <Link :href="route('admin.appointments.index')" class="card stat-link">
                <div class="stat-label">Turnos próximos</div><div class="stat-value">{{ stats.appointments_upcoming }}</div>
            </Link>
            <Link :href="route('admin.messages.index', { status: 'unread' })" class="card stat-link">
                <div class="stat-label">Mensajes sin leer</div><div class="stat-value">{{ stats.messages_unread }}</div>
            </Link>
        </div>

        <el-alert v-if="stats.orders_review" type="error" show-icon :closable="false" class="mt"
            :title="`${stats.orders_review} orden(es) requieren revisión (pago tardío sin stock o monto distinto).`">
            <Link :href="route('admin.orders.index', { review: 1 })">Ver órdenes</Link>
        </el-alert>

        <div class="grid grid-2 mt">
            <div class="card">
                <div class="row spread mb"><h2 style="margin: 0">Reponer stock</h2><Link :href="route('admin.scanner')" class="small">Ir al escáner</Link></div>
                <el-empty v-if="!lowStock.length" description="Todo el stock está sobre el mínimo." :image-size="70" />
                <el-table v-else :data="lowStock" size="small">
                    <el-table-column label="Producto" min-width="180">
                        <template #default="{ row }"><Link :href="route('admin.products.show', row.id)">{{ row.name }}</Link></template>
                    </el-table-column>
                    <el-table-column label="Stock" width="80" align="right">
                        <template #default="{ row }"><span :class="{ danger: row.stock === 0 }" class="price">{{ row.stock }}</span></template>
                    </el-table-column>
                    <el-table-column label="Mín." prop="min_stock" width="70" align="right" />
                </el-table>
            </div>
            <div class="card">
                <div class="row spread mb"><h2 style="margin: 0">Próximos turnos</h2><Link :href="route('admin.appointments.index')" class="small">Ver agenda</Link></div>
                <el-empty v-if="!nextAppointments.length" description="Sin turnos agendados." :image-size="70" />
                <div v-for="a in nextAppointments" :key="a.id" class="row spread appt">
                    <div>
                        <strong>{{ dateTime(a.starts_at) }}</strong>
                        <div class="small muted">{{ a.customer }} · {{ a.topic }}</div>
                    </div>
                    <el-tag :type="appointmentStatusType(a.status)" size="small">{{ a.status_label }}</el-tag>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.stat-link { color: inherit; text-decoration: none; }
.stat-link:hover { border-color: var(--brand); }
.appt { padding-block: 10px; border-bottom: 1px solid var(--line); }
.appt:last-child { border-bottom: 0; }
</style>
