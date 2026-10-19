<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { dateTime } from '@/utils';

const props = defineProps({
    tab: String,
    values: Object,
    customized: Object,
    networks: Array,
    icons: Array,
});

const active = ref(props.tab);
watch(active, (t) => {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', t);
    window.history.replaceState(window.history.state, '', url);
});

// Tras guardar, lo guardado pasa a ser el nuevo estado "sin cambios" del formulario.
const save = (form, section) =>
    form.put(route('admin.settings.update', section), { preserveScroll: true, onSuccess: () => form.defaults() });

// General
const site = useForm({ ...props.values.site });

// Contacto
const contact = useForm({ ...props.values.contact });
const whatsappPreview = computed(() => {
    const digits = String(contact.whatsapp ?? '').replace(/\D/g, '');
    return digits ? `https://wa.me/${digits}?text=${encodeURIComponent(contact.whatsapp_message ?? '')}` : null;
});

// Redes
const social = useForm({ ...props.values.social });
const placeholders = {
    instagram: 'https://instagram.com/tu_cuenta',
    facebook: 'https://facebook.com/tu_pagina',
    tiktok: 'https://tiktok.com/@tu_cuenta',
    youtube: 'https://youtube.com/@tu_canal',
    linkedin: 'https://linkedin.com/company/tu_empresa',
    x: 'https://x.com/tu_cuenta',
};

// Servicios
const services = useForm({ items: props.values.services.map((s) => ({ ...s })) });
const actions = [
    { value: 'shop', label: 'Ver productos (tienda)' },
    { value: 'support', label: 'Agendar un turno' },
    { value: 'whatsapp', label: 'Cotizar por WhatsApp' },
    { value: 'none', label: 'Sin botón' },
];
const addService = () => services.items.push({ title: '', text: '', icon: 'Monitor', action: 'none' });
const moveService = (i, dir) => {
    const items = services.items;
    const j = i + dir;
    [items[i], items[j]] = [items[j], items[i]];
};
const serviceError = (i, field) => services.errors[`items.${i}.${field}`];

// Agenda de soporte
const support = useForm({ ...props.values.support, working_days: [...props.values.support.working_days] });
const days = [
    { value: 1, label: 'Lun' }, { value: 2, label: 'Mar' }, { value: 3, label: 'Mié' }, { value: 4, label: 'Jue' },
    { value: 5, label: 'Vie' }, { value: 6, label: 'Sáb' }, { value: 7, label: 'Dom' },
];
const hourLabel = (h) => `${String(h).padStart(2, '0')}:00`;
const slotPreview = computed(() => {
    const slots = [];
    const end = support.close_hour * 60;
    for (let m = support.open_hour * 60; m + support.slot_minutes <= end; m += support.slot_minutes) {
        slots.push(`${String(Math.floor(m / 60)).padStart(2, '0')}:${String(m % 60).padStart(2, '0')}`);
    }
    return slots;
});

const savedAt = (section) => props.customized?.[section];
</script>

<template>
    <Head title="Configuración" />
    <AdminLayout title="Configuración del sitio">
        <el-tabs v-model="active" class="settings">
            <!-- General -->
            <el-tab-pane label="General" name="site">
                <el-form class="card panel" label-position="top" @submit.prevent="save(site, 'site')">
                    <p class="muted small status">
                        <template v-if="savedAt('site')">Guardado el {{ dateTime(savedAt('site')) }}.</template>
                        <template v-else>Usando los valores iniciales del archivo .env.</template>
                    </p>
                    <el-form-item label="Nombre del sitio" :error="site.errors.name">
                        <el-input v-model="site.name" maxlength="80" />
                    </el-form-item>
                    <el-form-item label="Titular de la portada" :error="site.errors.tagline">
                        <el-input v-model="site.tagline" maxlength="160" show-word-limit />
                    </el-form-item>
                    <el-form-item label="Descripción" :error="site.errors.description">
                        <el-input v-model="site.description" type="textarea" :rows="3" maxlength="400" show-word-limit />
                        <div class="hint">Aparece bajo el titular y cuando se comparte el enlace en WhatsApp o redes.</div>
                    </el-form-item>
                    <el-button type="primary" native-type="submit" :loading="site.processing" :disabled="!site.isDirty">Guardar</el-button>
                </el-form>
            </el-tab-pane>

            <!-- Contacto -->
            <el-tab-pane label="Contacto y horario" name="contact">
                <el-form class="card panel" label-position="top" @submit.prevent="save(contact, 'contact')">
                    <p class="muted small status">
                        <template v-if="savedAt('contact')">Guardado el {{ dateTime(savedAt('contact')) }}.</template>
                        <template v-else>Usando los valores iniciales del archivo .env.</template>
                        Los campos vacíos no se muestran en el sitio.
                    </p>
                    <div class="two">
                        <el-form-item label="WhatsApp" :error="contact.errors.whatsapp">
                            <el-input v-model="contact.whatsapp" placeholder="56912345678" />
                            <div class="hint">
                                Con código de país, sin + ni espacios.
                                <a v-if="whatsappPreview" :href="whatsappPreview" target="_blank" rel="noopener">Probar enlace</a>
                            </div>
                        </el-form-item>
                        <el-form-item label="Mensaje inicial de WhatsApp" :error="contact.errors.whatsapp_message">
                            <el-input v-model="contact.whatsapp_message" maxlength="200" />
                        </el-form-item>
                        <el-form-item label="Teléfono" :error="contact.errors.phone">
                            <el-input v-model="contact.phone" placeholder="+56 2 2345 6789" />
                        </el-form-item>
                        <el-form-item label="Correo" :error="contact.errors.email">
                            <el-input v-model="contact.email" type="email" />
                        </el-form-item>
                    </div>
                    <el-form-item label="Dirección" :error="contact.errors.address">
                        <el-input v-model="contact.address" placeholder="Av. Ejemplo 123, Santiago" />
                    </el-form-item>
                    <el-form-item label="Enlace a Google Maps" :error="contact.errors.maps_url">
                        <el-input v-model="contact.maps_url" placeholder="https://maps.app.goo.gl/..." />
                    </el-form-item>
                    <el-form-item label="Horario de atención (texto visible)" :error="contact.errors.hours">
                        <el-input v-model="contact.hours" placeholder="Lunes a viernes, 9:00 a 18:00 · Sábados, 10:00 a 14:00" />
                        <div class="hint">Es el texto que ven los clientes. Los horarios para agendar soporte se configuran en "Agenda de soporte".</div>
                    </el-form-item>
                    <el-button type="primary" native-type="submit" :loading="contact.processing" :disabled="!contact.isDirty">Guardar</el-button>
                </el-form>
            </el-tab-pane>

            <!-- Redes -->
            <el-tab-pane label="Redes sociales" name="social">
                <el-form class="card panel" label-position="top" @submit.prevent="save(social, 'social')">
                    <p class="muted small status">Deja vacía una red para que no aparezca en el sitio.</p>
                    <el-form-item v-for="n in networks" :key="n.key" :label="n.label" :error="social.errors[n.key]">
                        <el-input v-model="social[n.key]" :placeholder="placeholders[n.key]" clearable />
                    </el-form-item>
                    <el-button type="primary" native-type="submit" :loading="social.processing" :disabled="!social.isDirty">Guardar</el-button>
                </el-form>
            </el-tab-pane>

            <!-- Servicios -->
            <el-tab-pane label="Servicios" name="services">
                <el-form class="panel" label-position="top" @submit.prevent="save(services, 'services')">
                    <p class="muted small status">Se muestran en la sección "Qué hacemos" de la portada, en este orden.</p>
                    <div v-for="(item, i) in services.items" :key="i" class="card service">
                        <div class="row spread service__head">
                            <strong>Servicio {{ i + 1 }}</strong>
                            <div class="row" style="gap: 4px">
                                <el-button text size="small" :disabled="i === 0" aria-label="Subir" @click="moveService(i, -1)"><el-icon><IconTop /></el-icon></el-button>
                                <el-button text size="small" :disabled="i === services.items.length - 1" aria-label="Bajar" @click="moveService(i, 1)"><el-icon><IconBottom /></el-icon></el-button>
                                <el-button text size="small" type="danger" aria-label="Quitar" @click="services.items.splice(i, 1)"><el-icon><IconDelete /></el-icon></el-button>
                            </div>
                        </div>
                        <div class="service__grid">
                            <el-form-item label="Ícono" :error="serviceError(i, 'icon')">
                                <el-select v-model="item.icon" style="width: 100%">
                                    <template #prefix><el-icon><component :is="`Icon${item.icon}`" /></el-icon></template>
                                    <el-option v-for="icon in icons" :key="icon" :value="icon" :label="icon">
                                        <span class="row" style="gap: 8px"><el-icon><component :is="`Icon${icon}`" /></el-icon> {{ icon }}</span>
                                    </el-option>
                                </el-select>
                            </el-form-item>
                            <el-form-item label="Título" :error="serviceError(i, 'title')">
                                <el-input v-model="item.title" maxlength="80" />
                            </el-form-item>
                            <el-form-item label="Botón" :error="serviceError(i, 'action')">
                                <el-select v-model="item.action" style="width: 100%">
                                    <el-option v-for="a in actions" :key="a.value" :value="a.value" :label="a.label" />
                                </el-select>
                            </el-form-item>
                        </div>
                        <el-form-item label="Descripción" :error="serviceError(i, 'text')">
                            <el-input v-model="item.text" type="textarea" :rows="2" maxlength="300" show-word-limit />
                        </el-form-item>
                    </div>
                    <div class="row">
                        <el-button :disabled="services.items.length >= 8" @click="addService"><el-icon class="el-icon--left"><IconPlus /></el-icon>Agregar servicio</el-button>
                        <el-button type="primary" native-type="submit" :loading="services.processing" :disabled="!services.isDirty">Guardar</el-button>
                    </div>
                </el-form>
            </el-tab-pane>

            <!-- Agenda -->
            <el-tab-pane label="Agenda de soporte" name="support">
                <el-form class="card panel" label-position="top" @submit.prevent="save(support, 'support')">
                    <el-alert type="info" :closable="false" show-icon class="mb"
                        title="Los cambios aplican a las reservas nuevas. Los turnos ya agendados se mantienen." />
                    <el-form-item label="Días de atención" :error="support.errors.working_days">
                        <el-checkbox-group v-model="support.working_days">
                            <el-checkbox-button v-for="d in days" :key="d.value" :value="d.value">{{ d.label }}</el-checkbox-button>
                        </el-checkbox-group>
                    </el-form-item>
                    <div class="two">
                        <el-form-item label="Abre a las" :error="support.errors.open_hour">
                            <el-select v-model="support.open_hour" style="width: 100%">
                                <el-option v-for="h in 24" :key="h - 1" :value="h - 1" :label="hourLabel(h - 1)" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="Cierra a las" :error="support.errors.close_hour">
                            <el-select v-model="support.close_hour" style="width: 100%">
                                <el-option v-for="h in 24" :key="h" :value="h" :label="hourLabel(h)" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="Duración de cada turno" :error="support.errors.slot_minutes">
                            <el-select v-model="support.slot_minutes" style="width: 100%">
                                <el-option v-for="m in [15, 20, 30, 45, 60, 90, 120]" :key="m" :value="m" :label="`${m} minutos`" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="Clientes por horario" :error="support.errors.slot_capacity">
                            <el-input-number v-model="support.slot_capacity" :min="1" :max="20" style="width: 100%" />
                            <div class="hint">Cuántos turnos se pueden tomar en el mismo bloque (por ejemplo, técnicos disponibles).</div>
                        </el-form-item>
                        <el-form-item label="Se puede reservar con hasta" :error="support.errors.booking_horizon_days">
                            <el-input-number v-model="support.booking_horizon_days" :min="1" :max="180" style="width: 100%" />
                            <div class="hint">días de anticipación.</div>
                        </el-form-item>
                        <el-form-item label="Anticipación mínima" :error="support.errors.min_notice_hours">
                            <el-input-number v-model="support.min_notice_hours" :min="0" :max="168" style="width: 100%" />
                            <div class="hint">horas antes, para reservar o cancelar.</div>
                        </el-form-item>
                    </div>
                    <div class="preview">
                        <strong>{{ slotPreview.length }} turnos por día</strong>
                        <div class="preview__slots">
                            <el-tag v-for="s in slotPreview" :key="s" size="small" type="info">{{ s }}</el-tag>
                        </div>
                    </div>
                    <el-button type="primary" native-type="submit" :loading="support.processing" :disabled="!support.isDirty">Guardar</el-button>
                </el-form>
            </el-tab-pane>
        </el-tabs>
    </AdminLayout>
</template>

<style scoped>
.panel { max-width: 760px; }
.status { margin-top: 0; }
.hint { font-size: 0.82rem; color: var(--muted); margin-top: 4px; line-height: 1.4; width: 100%; }
.two { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 16px; }
.service { margin-bottom: 12px; }
.service__head { margin-bottom: 8px; }
.service__grid { display: grid; grid-template-columns: 160px minmax(0, 1fr) 220px; gap: 12px; }
.preview { background: var(--paper); border: 1px solid var(--line); border-radius: var(--radius); padding: 12px 14px; margin-bottom: 16px; }
.preview__slots { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
@media (max-width: 760px) {
    .two, .service__grid { grid-template-columns: 1fr; }
}
</style>
