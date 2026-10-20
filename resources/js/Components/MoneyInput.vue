<script setup>
// Campo de monto en pesos chilenos: muestra "$ 549.990" mientras se escribe
// y entrega al formulario un entero (549990), que es como se guarda.
const props = defineProps({
    modelValue: { type: Number, default: null },
    max: { type: Number, default: 999999999 },
    placeholder: { type: String, default: '$ 0' },
});
const emit = defineEmits(['update:modelValue']);

const format = (value) => {
    const digits = String(value ?? '').replace(/\D/g, '');
    return digits === '' ? '' : `$ ${digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;
};

const parse = (value) => String(value ?? '').replace(/\D/g, '');

const update = (value) => {
    const digits = parse(value);
    emit('update:modelValue', digits === '' ? null : Math.min(Number(digits), props.max));
};
</script>

<template>
    <el-input
        :model-value="modelValue === null ? '' : String(modelValue)"
        :formatter="format"
        :parser="parse"
        :placeholder="placeholder"
        inputmode="numeric"
        autocomplete="off"
        class="money-input"
        @update:model-value="update"
    />
</template>

<style scoped>
.money-input :deep(input) { font-variant-numeric: tabular-nums; }
</style>
