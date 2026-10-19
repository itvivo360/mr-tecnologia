<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import FlashMessages from '@/Components/FlashMessages.vue';
import BrandLogo from '@/Components/BrandLogo.vue';
import { whatsappUrl } from '@/utils';

// bare: la página maneja su propio ancho (portada con secciones a todo el ancho).
defineProps({ bare: { type: Boolean, default: false } });

const page = usePage();
const user = computed(() => page.props.auth.user);
const cartCount = computed(() => page.props.cartCount);
const site = computed(() => page.props.site);
const whatsapp = computed(() => whatsappUrl(site.value.contact));

const logout = () => router.post(route('logout'));
const year = new Date().getFullYear();
</script>

<template>
    <div class="site">
        <FlashMessages />
        <header class="topbar">
            <div class="container topbar__inner">
                <Link :href="route('home')" class="brand" :aria-label="`${site.name}, inicio`">
                    <BrandLogo :height="34" />
                </Link>

                <nav class="nav" aria-label="Principal">
                    <Link :href="route('home')" :class="{ active: route().current('home') }">Inicio</Link>
                    <Link :href="route('shop.index')" :class="{ active: route().current('shop.*') }">Tienda</Link>
                    <Link :href="`${route('home')}#servicios`">Servicios</Link>
                    <Link :href="route('support.index')" :class="{ active: route().current('support.*') }">Soporte técnico</Link>
                    <Link :href="`${route('home')}#contacto`">Contacto</Link>
                </nav>

                <div class="row actions">
                    <Link :href="route('cart.index')" class="cart-link" aria-label="Carrito">
                        <el-badge :value="cartCount" :hidden="!cartCount" :max="99">
                            <el-icon :size="22"><IconShoppingCart /></el-icon>
                        </el-badge>
                    </Link>

                    <el-dropdown v-if="user" trigger="click">
                        <el-button text>
                            <el-icon class="el-icon--left"><IconUser /></el-icon>
                            <span class="hide-sm">{{ user.name }}</span>
                        </el-button>
                        <template #dropdown>
                            <el-dropdown-menu>
                                <el-dropdown-item v-if="user.is_admin" @click="router.visit(route('admin.dashboard'))">Backoffice</el-dropdown-item>
                                <el-dropdown-item @click="router.visit(route('orders.index'))">Mis compras</el-dropdown-item>
                                <el-dropdown-item @click="router.visit(route('support.index'))">Mis turnos</el-dropdown-item>
                                <el-dropdown-item divided @click="logout">Cerrar sesión</el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                    <template v-else>
                        <Link :href="route('login')"><el-button text>Ingresar</el-button></Link>
                        <Link :href="route('register')" class="hide-sm"><el-button type="primary">Crear cuenta</el-button></Link>
                    </template>
                </div>
            </div>
            <nav class="nav nav--mobile container" aria-label="Principal (móvil)">
                <Link :href="route('home')">Inicio</Link>
                <Link :href="route('shop.index')">Tienda</Link>
                <Link :href="route('support.index')">Soporte</Link>
                <Link :href="`${route('home')}#contacto`">Contacto</Link>
            </nav>
        </header>

        <main v-if="bare"><slot /></main>
        <main v-else class="container page"><slot /></main>

        <footer class="footer">
            <div class="container footer__grid">
                <div class="footer__brand">
                    <Link :href="route('home')" class="brand" :aria-label="`${site.name}, inicio`"><BrandLogo light :height="32" /></Link>
                    <p>{{ site.tagline }}</p>
                </div>

                <nav class="footer__col" aria-label="Sitio">
                    <h3>Sitio</h3>
                    <Link :href="route('shop.index')">Tienda</Link>
                    <Link :href="`${route('home')}#servicios`">Servicios</Link>
                    <Link :href="route('support.index')">Agendar soporte</Link>
                    <Link v-if="user" :href="route('orders.index')">Mis compras</Link>
                </nav>

                <div class="footer__col">
                    <h3>Contacto</h3>
                    <a v-if="whatsapp" :href="whatsapp" target="_blank" rel="noopener">WhatsApp</a>
                    <a v-if="site.contact.phone" :href="`tel:${site.contact.phone.replace(/\s/g, '')}`">{{ site.contact.phone }}</a>
                    <a v-if="site.contact.email" :href="`mailto:${site.contact.email}`">{{ site.contact.email }}</a>
                    <span v-if="site.contact.address">{{ site.contact.address }}</span>
                    <span v-if="site.contact.hours">{{ site.contact.hours }}</span>
                </div>

                <div v-if="site.social.length" class="footer__col">
                    <h3>Síguenos</h3>
                    <a v-for="s in site.social" :key="s.name" :href="s.url" target="_blank" rel="noopener">{{ s.name }}</a>
                </div>
            </div>
            <div class="container footer__legal">© {{ year }} {{ site.name }}</div>
        </footer>
    </div>
</template>

<style scoped>
.site { min-height: 100vh; display: flex; flex-direction: column; }
.site > main { flex: 1; }
.topbar {
    background: var(--surface);
    border-bottom: 1px solid var(--line);
    position: sticky;
    top: 0;
    z-index: 10;
}
.topbar__inner { display: flex; align-items: center; gap: 28px; height: 64px; }
.brand { display: inline-flex; align-items: center; flex-shrink: 0; }
.nav { display: flex; gap: 22px; flex: 1; }
.nav a { color: var(--muted); font-weight: 500; text-decoration: none; padding-block: 4px; border-bottom: 2px solid transparent; }
.nav a:hover, .nav a.active { color: var(--ink); border-bottom-color: var(--brand); }
.nav--mobile { display: none; }
.actions { margin-left: auto; gap: 8px; }
.cart-link { color: var(--ink); display: inline-flex; padding: 6px 10px; }

.footer { background: var(--ink); color: #c9d1d6; margin-top: 64px; }
.footer__grid {
    display: grid;
    grid-template-columns: 1.4fr repeat(3, 1fr);
    gap: 32px;
    padding-block: 48px 32px;
}
.footer__brand p { max-width: 32ch; line-height: 1.5; margin-top: 12px; }
.footer__col { display: flex; flex-direction: column; gap: 8px; }
.footer__col h3 { color: #fff; font-size: 0.95rem; margin-bottom: 4px; }
.footer__col a { color: #c9d1d6; text-decoration: none; }
.footer__col a:hover { color: #fff; text-decoration: underline; }
.footer__legal { border-top: 1px solid rgba(255, 255, 255, 0.12); padding-block: 16px 24px; font-size: 0.85rem; color: #93a0a8; }

@media (max-width: 900px) {
    .topbar__inner > .nav { display: none; }
    .nav--mobile { display: flex; padding-bottom: 10px; gap: 18px; }
    .footer__grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 560px) {
    .hide-sm { display: none; }
    .footer__grid { grid-template-columns: 1fr; }
}
</style>
