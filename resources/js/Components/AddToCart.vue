<script setup>
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    product: { type: Object, required: true },
    withQuantity: { type: Boolean, default: false },
    size: { type: String, default: 'default' },
});

const page = usePage();
const quantity = ref(1);
const loading = ref(false);

const add = () => {
    if (!page.props.auth.user) {
        router.visit(route('login'));
        return;
    }
    loading.value = true;
    router.post(
        route('cart.store'),
        { product_id: props.product.id, quantity: quantity.value },
        { preserveScroll: true, onFinish: () => (loading.value = false) },
    );
};
</script>

<template>
    <div class="row">
        <el-input-number
            v-if="withQuantity && product.stock > 0"
            v-model="quantity"
            :min="1"
            :max="Math.min(product.stock, 99)"
            :size="size"
        />
        <el-button type="primary" :size="size" :disabled="product.stock < 1" :loading="loading" @click="add">
            <el-icon class="el-icon--left"><IconShoppingCart /></el-icon>
            {{ product.stock < 1 ? 'Sin stock' : 'Agregar' }}
        </el-button>
    </div>
</template>
