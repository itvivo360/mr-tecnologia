<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { ElMessage } from 'element-plus';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CameraScanner from '@/Components/CameraScanner.vue';
import ProductImage from '@/Components/ProductImage.vue';
import { useBarcodeScanner } from '@/Composables/useBarcodeScanner';
import { firstError, money, time } from '@/utils';

const modes = [
    { value: 'lookup', label: 'Consultar', hint: 'Escanea para ver el producto y su stock.' },
    { value: 'in', label: 'Ingreso', hint: 'Cada escaneo suma unidades al stock (recepción de mercadería).' },
    { value: 'out', label: 'Salida', hint: 'Cada escaneo descuenta unidades del stock.' },
    { value: 'assign', label: 'Asociar código', hint: 'Elige un producto y escanea su código para asociarlo.' },
];

const mode = ref('lookup');
const quantity = ref(1);
const reason = ref('');
const manual = ref('');
const camera = ref(false);

const current = ref(null);      // { code, product, status: 'found'|'missing'|'updated'|'error', message }
const target = ref(null);       // producto destino para "asociar"
const searchResults = ref([]);
const searching = ref(false);
const log = ref([]);

const modeHint = computed(() => modes.find((m) => m.value === mode.value)?.hint);

// Los escaneos se procesan en serie para no perder ni duplicar movimientos.
let queue = Promise.resolve();
const enqueue = (code) => {
    queue = queue.then(() => process(code.trim())).catch(() => {});
};

const addLog = (code, product, action, ok) => {
    log.value.unshift({ id: crypto.randomUUID?.() ?? Date.now() + Math.random(), at: new Date().toISOString(), code, product: product?.name ?? '—', action, ok });
    log.value = log.value.slice(0, 50);
};

let audio;
const beep = (ok = true) => {
    try {
        audio ??= new (window.AudioContext || window.webkitAudioContext)();
        const ctx = audio;
        const osc = ctx.createOscillator();
        osc.frequency.value = ok ? 880 : 220;
        osc.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + (ok ? 0.08 : 0.25));
    } catch { /* sin audio */ }
};

async function process(code) {
    if (!code) return;

    if (mode.value === 'assign' && target.value) {
        return assign(target.value, code);
    }

    let product;
    try {
        const { data } = await axios.get(route('admin.products.lookup'), { params: { code } });
        product = data.product;
    } catch (e) {
        current.value = { code, product: null, status: 'error', message: firstError(e) };
        beep(false);
        return;
    }

    if (!product) {
        current.value = { code, product: null, status: 'missing' };
        addLog(code, null, 'No encontrado', false);
        beep(false);
        return;
    }

    if (mode.value === 'in' || mode.value === 'out') {
        try {
            const { data } = await axios.post(route('admin.products.stock', product.id), {
                type: mode.value,
                quantity: quantity.value,
                reason: reason.value || (mode.value === 'in' ? 'Ingreso por escáner' : 'Salida por escáner'),
            });
            const sign = mode.value === 'in' ? '+' : '−';
            current.value = { code, product: data.product, status: 'updated', message: `${sign}${quantity.value} → stock ${data.product.stock}` };
            addLog(code, data.product, `${sign}${quantity.value}`, true);
            beep(true);
        } catch (e) {
            current.value = { code, product, status: 'error', message: firstError(e) };
            addLog(code, product, 'Error', false);
            beep(false);
        }
        return;
    }

    current.value = { code, product, status: 'found' };
    addLog(code, product, mode.value === 'assign' ? 'Ya asociado' : 'Consulta', true);
    beep(true);
}

async function assign(product, code) {
    try {
        const { data } = await axios.post(route('admin.products.barcode', product.id), { barcode: code });
        current.value = { code, product: data.product, status: 'updated', message: 'Código asociado' };
        addLog(code, data.product, 'Asociado', true);
        ElMessage.success(`Código ${code} asociado a ${data.product.name}`);
        target.value = null;
        beep(true);
    } catch (e) {
        current.value = { code, product, status: 'error', message: firstError(e) };
        addLog(code, product, 'Error al asociar', false);
        beep(false);
    }
}

const search = async (q) => {
    if (!q) return (searchResults.value = []);
    searching.value = true;
    try {
        const { data } = await axios.get(route('admin.products.search'), { params: { q } });
        searchResults.value = data.products;
    } finally {
        searching.value = false;
    }
};

const assignMissingTo = (productId) => {
    const product = searchResults.value.find((p) => p.id === productId);
    if (product && current.value?.code) assign(product, current.value.code);
};

const submitManual = () => {
    if (manual.value.trim()) enqueue(manual.value);
    manual.value = '';
};

useBarcodeScanner((code) => {
    manual.value = '';
    enqueue(code);
});
</script>

<template>
    <Head title="Escáner" />
    <AdminLayout title="Escáner y stock">
        <div class="layout">
            <div class="stack">
                <div class="card">
                    <el-radio-group v-model="mode" size="large" class="modes">
                        <el-radio-button v-for="m in modes" :key="m.value" :value="m.value">{{ m.label }}</el-radio-button>
                    </el-radio-group>
                    <p class="muted small">{{ modeHint }}</p>

                    <div v-if="mode === 'in' || mode === 'out'" class="row">
                        <span class="small">Unidades por escaneo</span>
                        <el-input-number v-model="quantity" :min="1" :max="10000" size="small" />
                        <el-input v-model="reason" size="small" placeholder="Motivo (opcional)" style="flex: 1; min-width: 180px" />
                    </div>

                    <div v-if="mode === 'assign'" class="row">
                        <el-select
                            v-model="target"
                            value-key="id"
                            filterable
                            remote
                            clearable
                            :remote-method="search"
                            :loading="searching"
                            placeholder="Busca el producto al que asociarás el código"
                            style="flex: 1"
                        >
                            <el-option v-for="p in searchResults" :key="p.id" :label="`${p.name} (${p.sku})`" :value="p" />
                        </el-select>
                    </div>
                </div>

                <div class="card scan-zone" :class="current?.status">
                    <div class="row spread">
                        <div class="row"><span class="dot"></span><strong>Lector listo</strong></div>
                        <el-button @click="camera = true"><el-icon class="el-icon--left"><IconCamera /></el-icon>Usar cámara</el-button>
                    </div>
                    <p class="muted small">
                        Escanea con el lector USB/Bluetooth en cualquier momento (no necesitas hacer clic en un campo),
                        usa la cámara o escribe el código y presiona Enter.
                    </p>
                    <el-input v-model="manual" size="large" placeholder="Código de barras o SKU" class="mono" data-barcode-capture @keydown.enter.prevent="submitManual">
                        <template #prefix><el-icon><IconAim /></el-icon></template>
                        <template #append><el-button @click="submitManual">Buscar</el-button></template>
                    </el-input>

                    <div v-if="current" class="result mt">
                        <div class="small muted mono">Código: {{ current.code }}</div>

                        <template v-if="current.product">
                            <div class="row mt" style="flex-wrap: nowrap">
                                <ProductImage :src="current.product.image_url" :alt="current.product.name" size="72" />
                                <div style="flex: 1; min-width: 0">
                                    <Link :href="route('admin.products.show', current.product.id)" class="result__name">{{ current.product.name }}</Link>
                                    <div class="small muted">{{ current.product.sku }} · {{ money(current.product.price) }} · {{ current.product.category ?? 'Sin categoría' }}</div>
                                </div>
                                <div class="right">
                                    <div class="stat-label">Stock</div>
                                    <div class="stat-value" :class="{ danger: current.product.low_stock }">{{ current.product.stock }}</div>
                                </div>
                            </div>
                        </template>

                        <el-alert v-if="current.status === 'updated'" type="success" :title="current.message" :closable="false" show-icon class="mt" />
                        <el-alert v-if="current.status === 'error'" type="error" :title="current.message" :closable="false" show-icon class="mt" />

                        <template v-if="current.status === 'missing'">
                            <el-alert type="warning" :closable="false" show-icon class="mt" title="Ningún producto tiene este código." />
                            <div class="row mt">
                                <Link :href="route('admin.products.create', { barcode: current.code })">
                                    <el-button type="primary"><el-icon class="el-icon--left"><IconPlus /></el-icon>Crear producto con este código</el-button>
                                </Link>
                                <el-select
                                    filterable
                                    remote
                                    :remote-method="search"
                                    :loading="searching"
                                    placeholder="…o asociarlo a un producto existente"
                                    style="flex: 1; min-width: 220px"
                                    @change="assignMissingTo"
                                >
                                    <el-option v-for="p in searchResults" :key="p.id" :label="`${p.name} (${p.sku})`" :value="p.id" />
                                </el-select>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="card log">
                <h2>Últimos escaneos</h2>
                <el-empty v-if="!log.length" description="Aún no hay escaneos en esta sesión." :image-size="70" />
                <div v-for="entry in log" :key="entry.id" class="log__row">
                    <span class="small muted">{{ time(entry.at) }}</span>
                    <div style="flex: 1; min-width: 0">
                        <div class="small ellipsis">{{ entry.product }}</div>
                        <div class="small muted mono">{{ entry.code }}</div>
                    </div>
                    <el-tag :type="entry.ok ? 'success' : 'danger'" size="small">{{ entry.action }}</el-tag>
                </div>
            </div>
        </div>

        <CameraScanner v-model="camera" @detected="enqueue" />
    </AdminLayout>
</template>

<style scoped>
.layout { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 20px; align-items: start; }
.modes { margin-bottom: 8px; flex-wrap: wrap; }
.scan-zone { border-width: 2px; transition: border-color 0.2s; }
.scan-zone.updated, .scan-zone.found { border-color: #16a34a; }
.scan-zone.missing { border-color: #d97706; }
.scan-zone.error { border-color: #dc2626; }
.dot { width: 10px; height: 10px; border-radius: 50%; background: #16a34a; box-shadow: 0 0 0 4px #dcfce7; }
.result__name { font-weight: 700; font-size: 1.1rem; }
.log { max-height: calc(100vh - 120px); overflow: auto; position: sticky; top: 76px; }
.log__row { display: flex; gap: 10px; align-items: center; padding-block: 8px; border-bottom: 1px solid var(--line); }
.ellipsis { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
@media (max-width: 960px) {
    .layout { grid-template-columns: 1fr; }
    .log { position: static; max-height: none; }
}
</style>
