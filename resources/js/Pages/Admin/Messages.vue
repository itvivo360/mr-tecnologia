<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ElMessageBox } from 'element-plus';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { dateTime } from '@/utils';

const props = defineProps({ messages: Object, filters: Object, unread: Number });

const status = ref(props.filters.status ?? '');
watch(status, (s) => router.get(route('admin.messages.index'), { status: s || undefined }, { preserveState: true, replace: true }));

const expanded = ref(null);
const open = (m) => {
    expanded.value = expanded.value === m.id ? null : m.id;
    if (!m.read && expanded.value === m.id) toggleRead(m);
};
const toggleRead = (m) => router.post(route('admin.messages.read', m.id), {}, { preserveScroll: true, preserveState: true });

const destroy = async (m) => {
    const ok = await ElMessageBox.confirm(`¿Eliminar el mensaje de ${m.name}?`, 'Eliminar mensaje', {
        type: 'warning', confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar',
    }).then(() => true, () => false);
    if (!ok) return;
    router.delete(route('admin.messages.destroy', m.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Mensajes" />
    <AdminLayout title="Mensajes de contacto">
        <div class="row mb">
            <el-radio-group v-model="status">
                <el-radio-button value="">Todos</el-radio-button>
                <el-radio-button value="unread">Sin leer ({{ unread }})</el-radio-button>
                <el-radio-button value="read">Leídos</el-radio-button>
            </el-radio-group>
        </div>

        <el-empty v-if="!messages.data.length" description="No hay mensajes en esta vista." />

        <div v-else class="card" style="padding: 0">
            <div v-for="m in messages.data" :key="m.id" class="msg" :class="{ 'msg--unread': !m.read }">
                <button class="msg__head" type="button" :aria-expanded="expanded === m.id" @click="open(m)">
                    <span class="msg__dot" aria-hidden="true"></span>
                    <span class="msg__who"><strong>{{ m.name }}</strong> <span class="muted small">{{ m.email }}</span></span>
                    <span class="msg__preview muted small">{{ m.message }}</span>
                    <span class="muted small msg__date">{{ dateTime(m.created_at) }}</span>
                </button>
                <div v-if="expanded === m.id" class="msg__body">
                    <p class="msg__text">{{ m.message }}</p>
                    <div class="row">
                        <a :href="`mailto:${m.email}`"><el-button size="small" type="primary">Responder por correo</el-button></a>
                        <a v-if="m.phone" :href="`tel:${m.phone.replace(/\s/g, '')}`"><el-button size="small">Llamar {{ m.phone }}</el-button></a>
                        <el-button size="small" text @click="toggleRead(m)">{{ m.read ? 'Marcar como no leído' : 'Marcar como leído' }}</el-button>
                        <el-button size="small" text type="danger" @click="destroy(m)">Eliminar</el-button>
                    </div>
                </div>
            </div>
        </div>
        <Pagination :paginator="messages" />
    </AdminLayout>
</template>

<style scoped>
.msg { border-bottom: 1px solid var(--line); }
.msg:last-child { border-bottom: 0; }
.msg__head {
    width: 100%;
    display: grid;
    grid-template-columns: 10px minmax(160px, 260px) minmax(0, 1fr) auto;
    gap: 14px;
    align-items: center;
    padding: 14px 20px;
    background: none;
    border: 0;
    text-align: left;
    font: inherit;
    color: inherit;
    cursor: pointer;
}
.msg__head:hover { background: #fafafa; }
.msg__dot { width: 8px; height: 8px; border-radius: 50%; background: transparent; }
.msg--unread .msg__dot { background: var(--brand); }
.msg__who, .msg__preview { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.msg__body { padding: 0 20px 18px 44px; }
.msg__text { white-space: pre-line; line-height: 1.6; max-width: 70ch; }
@media (max-width: 760px) {
    .msg__head { grid-template-columns: 10px 1fr; }
    .msg__preview, .msg__date { grid-column: 2; }
}
</style>
