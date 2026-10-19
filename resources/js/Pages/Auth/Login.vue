<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';

const form = useForm({ email: '', password: '', remember: false });

const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Ingresar" />
    <ShopLayout>
        <div class="auth card">
            <h1>Ingresar</h1>
            <p class="muted">Accede para comprar y agendar soporte técnico.</p>
            <el-form label-position="top" @submit.prevent="submit">
                <el-form-item label="Correo" :error="form.errors.email">
                    <el-input v-model="form.email" type="email" autocomplete="username" autofocus />
                </el-form-item>
                <el-form-item label="Contraseña" :error="form.errors.password">
                    <el-input v-model="form.password" type="password" show-password autocomplete="current-password" />
                </el-form-item>
                <el-checkbox v-model="form.remember">Recordarme</el-checkbox>
                <el-button type="primary" native-type="submit" :loading="form.processing" class="mt" style="width: 100%">Ingresar</el-button>
            </el-form>
            <p class="muted small mt">¿No tienes cuenta? <Link :href="route('register')">Regístrate</Link></p>
        </div>
    </ShopLayout>
</template>

<style scoped>
.auth { max-width: 420px; margin: 4vh auto 0; }
</style>
