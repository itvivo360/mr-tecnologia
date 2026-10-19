<script setup>
import { ref } from 'vue';
import axios from 'axios';
import CameraScanner from '@/Components/CameraScanner.vue';
import { useBarcodeScanner } from '@/Composables/useBarcodeScanner';

/**
 * Campo de código de barras: acepta escritura manual, lector USB/Bluetooth
 * (con el campo enfocado) o la cámara. Avisa si el código ya pertenece a otro producto.
 */
const props = defineProps({
    modelValue: { type: String, default: '' },
    productId: { type: Number, default: null },
    error: { type: String, default: null },
});
const emit = defineEmits(['update:modelValue']);

const camera = ref(false);
const conflict = ref(null);

const check = async (code) => {
    conflict.value = null;
    if (!code) return;
    const { data } = await axios.get(route('admin.products.lookup'), { params: { code } });
    if (data.product && data.product.id !== props.productId && data.product.barcode === code) {
        conflict.value = data.product;
    }
};

const set = (code) => {
    emit('update:modelValue', code);
    check(code);
};

// El lector "tipea" dentro del campo; aquí solo evitamos el Enter y verificamos.
useBarcodeScanner((code) => set(code));
</script>

<template>
    <div style="width: 100%" data-barcode-capture>
        <el-input
            :model-value="modelValue"
            placeholder="Escanea o escribe el código"
            clearable
            class="mono"
            @update:model-value="(v) => emit('update:modelValue', v)"
            @change="check"
            @keydown.enter.prevent
        >
            <template #prefix><el-icon><IconAim /></el-icon></template>
            <template #append>
                <el-button @click="camera = true" aria-label="Escanear con cámara"><el-icon><IconCamera /></el-icon></el-button>
            </template>
        </el-input>
        <div class="small muted" style="margin-top: 4px">Con el campo seleccionado, usa el lector USB/Bluetooth o la cámara.</div>
        <div v-if="error" class="small danger">{{ error }}</div>
        <el-alert v-if="conflict" type="warning" :closable="false" class="mt" show-icon
            :title="`Este código ya está asociado a «${conflict.name}» (${conflict.sku}).`" />
        <CameraScanner v-model="camera" @detected="set" />
    </div>
</template>
