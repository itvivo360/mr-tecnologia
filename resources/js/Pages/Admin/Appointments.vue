<script setup>
import { reactive, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { appointmentStatusType, date, time } from '@/utils';

const props = defineProps({ appointments: Array, filters: Object, statuses: Array, staff: Array });

const range = ref([props.filters.from, props.filters.to]);
const status = ref(props.filters.status);

watch([range, status], () => {
    router.get(route('admin.appointments.index'), {
        from: range.value?.[0], to: range.value?.[1], status: status.value || undefined,
    }, { preserveState: true, replace: true });
});

// Agrupa por día para leer la agenda más fácil.
const grouped = () => {
    const groups = {};
    for (const a of props.appointments) {
        const key = a.starts_at.slice(0, 10);
        (groups[key] ??= []).push(a);
    }
    return Object.entries(groups);
};

const editing = ref(null);
const form = useForm({ status: null, admin_notes: '', assigned_to: null });
const open = (a) => {
    editing.value = a;
    form.status = a.status;
    form.admin_notes = a.admin_notes ?? '';
    form.assigned_to = a.assigned_to;
};
const save = () =>
    form.put(route('admin.appointments.update', editing.value.id), {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    });
</script>

<template>
    <Head title="Turnos de soporte" />
    <AdminLayout title="Turnos de soporte">
        <div class="row mb">
            <el-date-picker v-model="range" type="daterange" value-format="YYYY-MM-DD" format="DD/MM/YYYY" range-separator="a" :clearable="false" />
            <el-select v-model="status" placeholder="Todos los estados" clearable style="width: 180px">
                <el-option v-for="s in statuses" :key="s.value" :label="s.label" :value="s.value" />
            </el-select>
        </div>

        <el-empty v-if="!appointments.length" description="No hay turnos en este rango." />

        <div v-for="[day, items] in grouped()" :key="day" class="mb">
            <h2 class="day">{{ date(items[0].starts_at) }}</h2>
            <div class="card" style="padding: 0">
                <div v-for="a in items" :key="a.id" class="appt" @click="open(a)">
                    <div class="appt__time">{{ time(a.starts_at) }}<div class="small muted">{{ time(a.ends_at) }}</div></div>
                    <div style="flex: 1; min-width: 0">
                        <div><strong>{{ a.customer.name }}</strong> <span class="small muted">{{ a.customer.phone ?? a.customer.email }}</span></div>
                        <div class="small">{{ a.topic }}<template v-if="a.product"> · {{ a.product }}</template><template v-if="a.order"> · <span class="mono">{{ a.order }}</span></template></div>
                        <div class="small muted clamp">{{ a.description }}</div>
                    </div>
                    <div class="right">
                        <el-tag :type="appointmentStatusType(a.status)" size="small">{{ a.status_label }}</el-tag>
                        <div class="small muted" style="margin-top: 4px">{{ a.assignee ?? 'Sin asignar' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <el-drawer :model-value="!!editing" title="Gestionar turno" size="min(440px, 100%)" @close="editing = null">
            <template v-if="editing">
                <p><strong>{{ editing.customer.name }}</strong><br /><span class="small muted">{{ editing.customer.email }} · {{ editing.customer.phone ?? 'sin teléfono' }}</span></p>
                <p class="small">{{ date(editing.starts_at) }}, {{ time(editing.starts_at) }}–{{ time(editing.ends_at) }}</p>
                <p class="small"><strong>{{ editing.topic }}</strong></p>
                <p class="small" style="white-space: pre-line">{{ editing.description }}</p>
                <el-form label-position="top" @submit.prevent="save">
                    <el-form-item label="Estado" :error="form.errors.status">
                        <el-radio-group v-model="form.status">
                            <el-radio-button v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</el-radio-button>
                        </el-radio-group>
                    </el-form-item>
                    <el-form-item label="Técnico asignado" :error="form.errors.assigned_to">
                        <el-select v-model="form.assigned_to" clearable placeholder="Sin asignar" style="width: 100%">
                            <el-option v-for="u in staff" :key="u.id" :label="u.name" :value="u.id" />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="Notas internas" :error="form.errors.admin_notes">
                        <el-input v-model="form.admin_notes" type="textarea" :rows="4" />
                    </el-form-item>
                    <el-button type="primary" native-type="submit" :loading="form.processing">Guardar</el-button>
                </el-form>
            </template>
        </el-drawer>
    </AdminLayout>
</template>

<style scoped>
.day { font-size: 1rem; text-transform: capitalize; color: var(--muted); }
.appt { display: flex; gap: 16px; padding: 14px 20px; border-bottom: 1px solid var(--line); cursor: pointer; align-items: flex-start; }
.appt:last-child { border-bottom: 0; }
.appt:hover { background: #fafafa; }
.appt__time { width: 56px; font-weight: 700; font-variant-numeric: tabular-nums; }
.clamp { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
</style>
