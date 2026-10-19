<script setup>
import { Head, router } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import { money } from '@/utils';

const props = defineProps({ order: Object });

const complete = (approved) => router.post(route('payments.fake.complete', props.order.number), { approved });
</script>

<template>
    <Head title="Pago simulado" />
    <ShopLayout>
        <div class="card" style="max-width: 460px; margin: 4vh auto 0; text-align: center">
            <el-tag type="warning">Modo desarrollo</el-tag>
            <h1 class="mt">Pago simulado</h1>
            <p class="muted">Orden <span class="mono">{{ order.number }}</span></p>
            <p class="price" style="font-size: 2rem">{{ money(order.total) }}</p>
            <p class="muted small">Con <code>PAYMENT_GATEWAY=mercadopago</code> aquí se abre Checkout Pro.</p>
            <div class="row mt" style="justify-content: center">
                <el-button type="danger" plain @click="complete(false)">Rechazar pago</el-button>
                <el-button type="primary" @click="complete(true)">Aprobar pago</el-button>
            </div>
        </div>
    </ShopLayout>
</template>
