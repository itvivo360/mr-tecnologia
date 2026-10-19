<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({ status: Number });

const messages = {
    403: ['Acceso denegado', 'No tienes permiso para ver esta página.'],
    404: ['Página no encontrada', 'Lo que buscas no existe o fue movido.'],
    500: ['Error del servidor', 'Algo salió mal de nuestro lado. Intenta nuevamente.'],
    503: ['En mantención', 'Volvemos en unos minutos.'],
};
const [title, description] = computed(() => messages[props.status] ?? messages[500]).value;
</script>

<template>
    <Head :title="title" />
    <div class="container" style="padding-block: 15vh; text-align: center">
        <p class="muted mono">{{ status }}</p>
        <h1>{{ title }}</h1>
        <p class="muted">{{ description }}</p>
        <Link :href="route('home')"><el-button type="primary" class="mt">Ir al inicio</el-button></Link>
    </div>
</template>
