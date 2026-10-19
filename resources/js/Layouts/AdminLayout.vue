<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import FlashMessages from '@/Components/FlashMessages.vue';
import BrandLogo from '@/Components/BrandLogo.vue';

defineProps({ title: { type: String, default: '' } });

const page = usePage();
const drawer = ref(false);

const items = [
    { route: 'admin.dashboard', match: 'admin.dashboard', label: 'Resumen', icon: 'IconDataLine' },
    { route: 'admin.products.index', match: 'admin.products.*', label: 'Productos', icon: 'IconGoods' },
    { route: 'admin.scanner', match: 'admin.scanner', label: 'Escáner / Stock', icon: 'IconAim' },
    { route: 'admin.categories.index', match: 'admin.categories.*', label: 'Categorías', icon: 'IconCollectionTag' },
    { route: 'admin.orders.index', match: 'admin.orders.*', label: 'Órdenes', icon: 'IconTickets' },
    { route: 'admin.appointments.index', match: 'admin.appointments.*', label: 'Turnos de soporte', icon: 'IconCalendar' },
    { route: 'admin.messages.index', match: 'admin.messages.*', label: 'Mensajes de contacto', icon: 'IconMessage' },
    { route: 'admin.settings.index', match: 'admin.settings.*', label: 'Configuración', icon: 'IconSetting' },
];

const active = computed(() => items.find((i) => route().current(i.match))?.route);
const go = (name) => {
    drawer.value = false;
    router.visit(route(name));
};
</script>

<template>
    <div class="admin">
        <FlashMessages />

        <aside class="sidebar hide-md">
            <Link :href="route('admin.dashboard')" class="logo" aria-label="Backoffice, inicio">
                <BrandLogo :height="26" />
                <span class="logo__label">Backoffice</span>
            </Link>
            <el-menu :default-active="active" class="menu" @select="go">
                <el-menu-item v-for="item in items" :key="item.route" :index="item.route">
                    <el-icon><component :is="item.icon" /></el-icon>
                    <span>{{ item.label }}</span>
                </el-menu-item>
            </el-menu>
        </aside>

        <el-drawer v-model="drawer" direction="ltr" size="260px" :with-header="false">
            <div class="logo"><BrandLogo :height="26" /><span class="logo__label">Backoffice</span></div>
            <el-menu :default-active="active" class="menu" @select="go">
                <el-menu-item v-for="item in items" :key="item.route" :index="item.route">
                    <el-icon><component :is="item.icon" /></el-icon>
                    <span>{{ item.label }}</span>
                </el-menu-item>
            </el-menu>
        </el-drawer>

        <div class="main">
            <header class="bar">
                <el-button class="show-md" text @click="drawer = true" aria-label="Menú">
                    <el-icon :size="20"><IconMenu /></el-icon>
                </el-button>
                <strong class="bar__title">{{ title }}</strong>
                <div class="row" style="margin-left: auto">
                    <Link :href="route('home')" class="small">Ver sitio</Link>
                    <span class="muted small hide-sm">{{ page.props.auth.user.name }}</span>
                    <el-button text size="small" @click="router.post(route('logout'))">Salir</el-button>
                </div>
            </header>
            <main class="content">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.admin { display: flex; min-height: 100vh; }
.sidebar {
    width: 240px;
    flex-shrink: 0;
    background: var(--surface);
    border-right: 1px solid var(--line);
    position: sticky;
    top: 0;
    height: 100vh;
}
.logo { display: grid; gap: 6px; padding: 20px; color: var(--ink); text-decoration: none; }
.logo:hover { text-decoration: none; }
.logo__label { font-size: 0.8rem; color: var(--muted); font-weight: 600; }
.menu { border-right: 0; }
.main { flex: 1; min-width: 0; }
.bar {
    height: 56px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding-inline: 20px;
    background: var(--surface);
    border-bottom: 1px solid var(--line);
    position: sticky;
    top: 0;
    z-index: 5;
}
.content { padding: 24px; max-width: 1280px; }
.show-md { display: none; }

@media (max-width: 960px) {
    .hide-md { display: none; }
    .show-md { display: inline-flex; }
    .content { padding: 16px; }
}
@media (max-width: 640px) {
    .hide-sm { display: none; }
}
</style>
