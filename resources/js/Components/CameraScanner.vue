<script setup>
import { onBeforeUnmount, ref, watch, nextTick } from 'vue';
import { BrowserMultiFormatReader } from '@zxing/browser';
import { BarcodeFormat, DecodeHintType } from '@zxing/library';

/**
 * Escáner con la cámara del dispositivo (celular o webcam).
 * Requiere HTTPS (o localhost) para que el navegador permita usar la cámara.
 */
const props = defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: 'Escanear con cámara' },
});
const emit = defineEmits(['update:modelValue', 'detected']);

const video = ref(null);
const devices = ref([]);
const deviceId = ref(null);
const error = ref(null);
const starting = ref(false);

let controls = null;
let done = false;
const hints = new Map();
hints.set(DecodeHintType.POSSIBLE_FORMATS, [
    BarcodeFormat.EAN_13,
    BarcodeFormat.EAN_8,
    BarcodeFormat.UPC_A,
    BarcodeFormat.UPC_E,
    BarcodeFormat.CODE_128,
    BarcodeFormat.CODE_39,
    BarcodeFormat.ITF,
    BarcodeFormat.QR_CODE,
]);
const reader = new BrowserMultiFormatReader(hints, { delayBetweenScanAttempts: 150 });

const stop = () => {
    controls?.stop();
    controls = null;
};

const start = async () => {
    stop();
    done = false;
    error.value = null;

    if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
        error.value = 'La cámara solo está disponible en HTTPS o localhost.';
        return;
    }

    starting.value = true;
    try {
        await nextTick();
        // Preferimos la cámara trasera en celulares.
        const constraints = deviceId.value
            ? { video: { deviceId: { exact: deviceId.value } } }
            : { video: { facingMode: { ideal: 'environment' } } };

        controls = await reader.decodeFromConstraints(constraints, video.value, (result) => {
            // Una sola lectura por apertura: evita registrar dos movimientos de stock.
            if (!result || done) return;
            done = true;
            navigator.vibrate?.(80);
            emit('detected', result.getText());
            close();
        });

        // Si se detectó (o se cerró el diálogo) antes de que terminara de iniciar.
        if (done || !props.modelValue) stop();

        if (!devices.value.length) {
            devices.value = await BrowserMultiFormatReader.listVideoInputDevices();
        }
    } catch (e) {
        error.value = e?.name === 'NotAllowedError'
            ? 'Permiso de cámara denegado. Habilítalo en la configuración del navegador.'
            : 'No se pudo iniciar la cámara.';
    } finally {
        starting.value = false;
    }
};

const close = () => {
    stop();
    emit('update:modelValue', false);
};

watch(() => props.modelValue, (open) => (open ? start() : stop()));
watch(deviceId, () => props.modelValue && start());
onBeforeUnmount(stop);
</script>

<template>
    <el-dialog :model-value="modelValue" :title="title" width="min(520px, 95vw)" @close="close" destroy-on-close>
        <div class="camera">
            <video ref="video" muted playsinline class="camera__video"></video>
            <div class="camera__frame" aria-hidden="true"></div>
        </div>
        <el-alert v-if="error" :title="error" type="error" show-icon :closable="false" class="mt" />
        <p v-else class="muted mt">{{ starting ? 'Iniciando cámara…' : 'Apunta al código de barras y mantén el dispositivo firme.' }}</p>
        <el-select v-if="devices.length > 1" v-model="deviceId" placeholder="Cambiar cámara" class="mt" style="width: 100%">
            <el-option v-for="d in devices" :key="d.deviceId" :label="d.label || 'Cámara'" :value="d.deviceId" />
        </el-select>
    </el-dialog>
</template>

<style scoped>
.camera {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    background: #000;
    aspect-ratio: 4 / 3;
}
.camera__video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.camera__frame {
    position: absolute;
    inset: 28% 10%;
    border: 2px solid rgba(255, 255, 255, 0.85);
    border-radius: 10px;
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.35);
}
</style>
