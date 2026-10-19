<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ElMessageBox } from 'element-plus';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import { appointmentStatusType, date, dateTime, time, ymd } from '@/utils';

const props = defineProps({ appointments: Array, topics: Array, orders: Array, schedule: Object });

const day = ref(null);
const slots = ref([]);
const loadingSlots = ref(false);

const form = useForm({
    starts_at: null,
    topic: props.topics[0]?.value ?? null,
    description: '',
    order_id: null,
    product_id: null,
});

const today = new Date();
today.setHours(0, 0, 0, 0);
const horizon = new Date(today);
horizon.setDate(horizon.getDate() + props.schedule.horizon_days);

// getDay(): 0 = domingo; el backend usa ISO (7 = domingo)
const disabledDate = (d) => {
    const iso = d.getDay() === 0 ? 7 : d.getDay();
    return d < today || d > horizon || !props.schedule.working_days.includes(iso);
};

const loadSlots = async () => {
    form.starts_at = null;
    slots.value = [];
    if (!day.value) return;
    loadingSlots.value = true;
    try {
        const { data } = await axios.get(route('support.slots'), { params: { date: ymd(day.value) } });
        slots.value = data.slots;
    } finally {
        loadingSlots.value = false;
    }
};
watch(day, loadSlots);

const orderProducts = computed(() => props.orders.find((o) => o.id === form.order_id)?.items ?? []);
watch(() => form.order_id, () => (form.product_id = null));

const submit = () =>
    form.post(route('support.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('starts_at', 'description', 'order_id', 'product_id');
            loadSlots();
        },
        onError: loadSlots,
    });

const cancel = async (appointment) => {
    const ok = await ElMessageBox.confirm(`¿Cancelar el turno del ${dateTime(appointment.starts_at)}?`, 'Cancelar turno', {
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'Volver',
        type: 'warning',
    }).then(() => true, () => false);
    if (!ok) return;
    router.post(route('support.cancel', appointment.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Soporte técnico" />
    <ShopLayout>
        <h1>Soporte técnico</h1>
        <p class="muted">Agenda un turno con nuestro equipo técnico. Los turnos se pueden cancelar hasta {{ schedule.min_notice_hours }} horas antes.</p>

        <div class="grid grid-2 mt">
            <el-form class="card" label-position="top" @submit.prevent="submit">
                <h2>Nuevo turno</h2>

                <el-form-item label="1. Elige el día">
                    <el-date-picker v-model="day" type="date" placeholder="Selecciona una fecha" :disabled-date="disabledDate" format="DD/MM/YYYY" style="width: 100%" />
                </el-form-item>

                <el-form-item label="2. Elige el horario" :error="form.errors.starts_at">
                    <div v-loading="loadingSlots" class="slots">
                        <span v-if="!day" class="muted small">Primero elige un día.</span>
                        <span v-else-if="!loadingSlots && !slots.length" class="muted small">No hay atención ese día.</span>
                        <el-button
                            v-for="s in slots"
                            :key="s.starts_at"
                            :type="form.starts_at === s.starts_at ? 'primary' : 'default'"
                            :disabled="!s.available"
                            size="small"
                            @click="form.starts_at = s.starts_at"
                        >{{ s.label }}</el-button>
                    </div>
                </el-form-item>

                <el-form-item label="3. Motivo" :error="form.errors.topic">
                    <el-select v-model="form.topic" style="width: 100%">
                        <el-option v-for="t in topics" :key="t.value" :label="t.label" :value="t.value" />
                    </el-select>
                </el-form-item>

                <div v-if="orders.length" class="row" style="align-items: flex-start">
                    <el-form-item label="Compra relacionada (opcional)" style="flex: 1; min-width: 180px">
                        <el-select v-model="form.order_id" clearable placeholder="Ninguna" style="width: 100%">
                            <el-option v-for="o in orders" :key="o.id" :label="o.number" :value="o.id" />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="Producto" style="flex: 1; min-width: 180px">
                        <el-select v-model="form.product_id" clearable :disabled="!orderProducts.length" placeholder="Ninguno" style="width: 100%">
                            <el-option v-for="p in orderProducts" :key="p.product_id" :label="p.name" :value="p.product_id" />
                        </el-select>
                    </el-form-item>
                </div>

                <el-form-item label="Describe el problema" :error="form.errors.description">
                    <el-input v-model="form.description" type="textarea" :rows="4" maxlength="2000" show-word-limit placeholder="¿Qué ocurre? ¿Desde cuándo? ¿Algún mensaje de error?" />
                </el-form-item>

                <el-button type="primary" native-type="submit" :loading="form.processing" :disabled="!form.starts_at" style="width: 100%">
                    <template v-if="form.starts_at">Reservar {{ date(form.starts_at) }} a las {{ time(form.starts_at) }}</template>
                    <template v-else>Reservar turno</template>
                </el-button>
            </el-form>

            <div class="card">
                <h2>Mis turnos</h2>
                <el-empty v-if="!appointments.length" description="No tienes turnos agendados." :image-size="80" />
                <div v-for="a in appointments" :key="a.id" class="appt">
                    <div class="row spread">
                        <strong>{{ dateTime(a.starts_at) }}</strong>
                        <el-tag :type="appointmentStatusType(a.status)" size="small">{{ a.status_label }}</el-tag>
                    </div>
                    <div class="small">{{ a.topic }}<template v-if="a.product"> · {{ a.product }}</template></div>
                    <div class="small muted clamp">{{ a.description }}</div>
                    <el-button v-if="a.can_cancel" text type="danger" size="small" @click="cancel(a)">Cancelar turno</el-button>
                </div>
            </div>
        </div>
    </ShopLayout>
</template>

<style scoped>
.slots { display: flex; flex-wrap: wrap; gap: 8px; min-height: 40px; width: 100%; }
.slots .el-button + .el-button { margin-left: 0; }
.appt { padding-block: 12px; border-bottom: 1px solid var(--line); }
.appt:last-child { border-bottom: 0; }
.clamp { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-top: 4px; }
</style>
