<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BarcodeInput from '@/Components/BarcodeInput.vue';
import MoneyInput from '@/Components/MoneyInput.vue';
import ProductImage from '@/Components/ProductImage.vue';

const props = defineProps({ product: Object, categories: Array, prefill: Object, defaultMinStock: Number });

const editing = computed(() => !!props.product);
const preview = ref(props.product?.image_url ?? null);

const form = useForm({
    name: props.product?.name ?? '',
    sku: props.product?.sku ?? '',
    barcode: props.product?.barcode ?? props.prefill?.barcode ?? '',
    category_id: props.product?.category_id ?? null,
    description: props.product?.description ?? '',
    price: props.product?.price ?? 0,
    min_stock: props.product?.min_stock ?? props.defaultMinStock,
    is_active: props.product?.is_active ?? true,
    is_featured: props.product?.is_featured ?? false,
    initial_stock: 0,
    image: null,
    remove_image: false,
});

const onImage = (file) => {
    form.image = file.raw;
    form.remove_image = false;
    preview.value = URL.createObjectURL(file.raw);
    return false;
};
const removeImage = () => {
    form.image = null;
    form.remove_image = true;
    preview.value = null;
};

const submit = () => {
    if (editing.value) {
        // PUT con archivo: se envía como POST + _method (multipart).
        form.transform((data) => {
            const { initial_stock, ...rest } = data;
            return { ...rest, _method: 'put' };
        }).post(route('admin.products.update', props.product.id), { forceFormData: true });
    } else {
        form.transform((d) => d).post(route('admin.products.store'), { forceFormData: true });
    }
};
</script>

<template>
    <Head :title="editing ? 'Editar producto' : 'Nuevo producto'" />
    <AdminLayout :title="editing ? 'Editar producto' : 'Nuevo producto'">
        <el-form label-position="top" class="layout" @submit.prevent="submit">
            <div class="card">
                <el-form-item label="Nombre" :error="form.errors.name">
                    <el-input v-model="form.name" maxlength="255" />
                </el-form-item>
                <div class="row" style="align-items: flex-start">
                    <el-form-item label="SKU (opcional)" :error="form.errors.sku" style="flex: 1; min-width: 160px">
                        <el-input v-model="form.sku" class="mono" placeholder="Se genera solo, ej. PRD-0014" clearable />
                    </el-form-item>
                    <el-form-item label="Categoría" :error="form.errors.category_id" style="flex: 1; min-width: 160px">
                        <el-select v-model="form.category_id" clearable placeholder="Sin categoría" style="width: 100%">
                            <el-option v-for="c in categories" :key="c.id" :label="c.name" :value="c.id" />
                        </el-select>
                    </el-form-item>
                </div>
                <el-form-item label="Código de barras">
                    <BarcodeInput v-model="form.barcode" :product-id="product?.id ?? null" :error="form.errors.barcode" />
                </el-form-item>
                <el-form-item label="Descripción" :error="form.errors.description">
                    <el-input v-model="form.description" type="textarea" :rows="5" maxlength="5000" show-word-limit />
                </el-form-item>
            </div>

            <div class="stack">
                <div class="card">
                    <el-form-item label="Precio (CLP)" :error="form.errors.price">
                        <MoneyInput v-model="form.price" />
                    </el-form-item>
                    <el-form-item v-if="!editing" label="Stock inicial" :error="form.errors.initial_stock">
                        <el-input-number v-model="form.initial_stock" :min="0" style="width: 100%" />
                    </el-form-item>
                    <el-form-item v-else label="Stock actual">
                        <div>
                            <strong>{{ product.stock }}</strong>
                            <span class="muted small"> · se modifica con movimientos desde el detalle o el escáner</span>
                        </div>
                    </el-form-item>
                    <el-form-item label="Alerta de stock mínimo" :error="form.errors.min_stock">
                        <el-input-number v-model="form.min_stock" :min="0" style="width: 100%" />
                    </el-form-item>
                    <el-form-item label="Visible en la tienda">
                        <el-switch v-model="form.is_active" />
                    </el-form-item>
                    <el-form-item label="Destacado en la portada" :error="form.errors.is_featured">
                        <el-switch v-model="form.is_featured" :disabled="!form.is_active" />
                    </el-form-item>
                </div>
                <div class="card">
                    <el-form-item label="Imagen" :error="form.errors.image">
                        <div class="stack" style="width: 100%">
                            <ProductImage :src="preview" size="160" />
                            <div class="row">
                                <el-upload :auto-upload="false" :show-file-list="false" accept="image/*" :on-change="onImage">
                                    <el-button size="small">Subir imagen</el-button>
                                </el-upload>
                                <el-button v-if="preview" size="small" text type="danger" @click="removeImage">Quitar</el-button>
                            </div>
                        </div>
                    </el-form-item>
                </div>
                <div class="row">
                    <Link :href="editing ? route('admin.products.show', product.id) : route('admin.products.index')"><el-button>Cancelar</el-button></Link>
                    <el-button type="primary" native-type="submit" :loading="form.processing">{{ editing ? 'Guardar cambios' : 'Crear producto' }}</el-button>
                </div>
            </div>
        </el-form>
    </AdminLayout>
</template>

<style scoped>
.layout { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 20px; align-items: start; }
@media (max-width: 960px) { .layout { grid-template-columns: 1fr; } }
</style>
