<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    modelValue: Boolean,
    product: { type: Object, required: true },
    initialType: { type: String, default: 'in' },
});
const emit = defineEmits(['update:modelValue']);

const form = useForm({ type: props.initialType, quantity: 1, reason: '' });

watch(() => props.modelValue, (open) => {
    if (open) {
        form.reset();
        form.clearErrors();
        form.type = props.initialType;
        form.quantity = props.initialType === 'count' ? props.product.stock : 1;
    }
});
watch(() => form.type, (t) => (form.quantity = t === 'count' ? props.product.stock : 1));

const resulting = computed(() => ({
    in: props.product.stock + (form.quantity || 0),
    out: props.product.stock - (form.quantity || 0),
    count: form.quantity || 0,
})[form.type]);

const submit = () =>
    form.post(route('admin.products.stock', props.product.id), {
        preserveScroll: true,
        onSuccess: () => emit('update:modelValue', false),
    });
</script>

<template>
    <el-dialog :model-value="modelValue" title="Movimiento de stock" width="min(440px, 95vw)" @close="emit('update:modelValue', false)">
        <el-form label-position="top" @submit.prevent="submit">
            <el-form-item>
                <el-radio-group v-model="form.type">
                    <el-radio-button value="in">Ingreso</el-radio-button>
                    <el-radio-button value="out">Salida</el-radio-button>
                    <el-radio-button value="count">Conteo</el-radio-button>
                </el-radio-group>
            </el-form-item>
            <el-form-item :label="form.type === 'count' ? 'Unidades contadas' : 'Cantidad'" :error="form.errors.quantity">
                <el-input-number v-model="form.quantity" :min="form.type === 'count' ? 0 : 1" style="width: 100%" />
            </el-form-item>
            <el-form-item label="Motivo (opcional)" :error="form.errors.reason">
                <el-input v-model="form.reason" maxlength="255" :placeholder="form.type === 'in' ? 'Ej.: factura proveedor #123' : 'Ej.: merma, devolución'" />
            </el-form-item>
            <p class="small">
                Stock actual <strong>{{ product.stock }}</strong> → quedará en
                <strong :class="{ danger: resulting < 0 }">{{ resulting }}</strong>
            </p>
        </el-form>
        <template #footer>
            <el-button @click="emit('update:modelValue', false)">Cancelar</el-button>
            <el-button type="primary" :loading="form.processing" :disabled="resulting < 0" @click="submit">Registrar</el-button>
        </template>
    </el-dialog>
</template>
