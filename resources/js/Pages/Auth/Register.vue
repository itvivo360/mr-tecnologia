<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';

const form = useForm({ name: '', email: '', phone: '', password: '', password_confirmation: '' });

const submit = () => form.post(route('register'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="Crear cuenta" />
    <ShopLayout>
        <div class="auth card">
            <h1>Crear cuenta</h1>
            <el-form label-position="top" @submit.prevent="submit">
                <el-form-item label="Nombre" :error="form.errors.name">
                    <el-input v-model="form.name" autocomplete="name" autofocus />
                </el-form-item>
                <el-form-item label="Correo" :error="form.errors.email">
                    <el-input v-model="form.email" type="email" autocomplete="email" />
                </el-form-item>
                <el-form-item label="Teléfono (opcional)" :error="form.errors.phone">
                    <el-input v-model="form.phone" autocomplete="tel" placeholder="+56 9 1234 5678" />
                </el-form-item>
                <el-form-item label="Contraseña" :error="form.errors.password">
                    <el-input v-model="form.password" type="password" show-password autocomplete="new-password" />
                </el-form-item>
                <el-form-item label="Repetir contraseña">
                    <el-input v-model="form.password_confirmation" type="password" show-password autocomplete="new-password" />
                </el-form-item>
                <el-button type="primary" native-type="submit" :loading="form.processing" style="width: 100%">Crear cuenta</el-button>
            </el-form>
            <p class="muted small mt">¿Ya tienes cuenta? <Link :href="route('login')">Ingresa</Link></p>
        </div>
    </ShopLayout>
</template>

<style scoped>
.auth { max-width: 440px; margin: 3vh auto 0; }
</style>
