<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import BrandLine from '@/Components/BrandLine.vue';
import ProductImage from '@/Components/ProductImage.vue';
import AddToCart from '@/Components/AddToCart.vue';
import { money, whatsappUrl } from '@/utils';

const props = defineProps({
    hero: { type: Object, default: null },
    featured: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    description: { type: String, default: '' },
});

const page = usePage();
const site = computed(() => page.props.site);
const contact = computed(() => site.value.contact);
const whatsapp = computed(() => whatsappUrl(contact.value));

const serviceAction = (service) => {
    switch (service.action) {
        case 'shop':
            return { label: 'Ver productos', href: route('shop.index') };
        case 'support':
            return { label: 'Agendar un turno', href: route('support.index') };
        case 'whatsapp':
            return whatsapp.value
                ? { label: 'Cotizar por WhatsApp', href: whatsappUrl(contact.value, `Hola, quiero cotizar: ${service.title}.`), external: true }
                : { label: 'Escríbenos', href: '#contacto', anchor: true };
        default:
            return null;
    }
};

const form = useForm({ name: '', email: '', phone: '', message: '', website: '' });
const send = () => form.post(route('contact.store'), { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
    <Head>
        <title>{{ site.name }}</title>
        <meta name="description" :content="description" />
    </Head>

    <ShopLayout bare>
        <!-- Portada -->
        <section class="hero">
            <div class="container hero__grid">
                <div class="hero__copy">
                    <h1 class="hero__title">{{ site.tagline }}</h1>
                    <p class="hero__lead">{{ description }}</p>
                    <div class="row hero__actions">
                        <Link :href="route('shop.index')"><el-button type="primary" size="large">Ver la tienda</el-button></Link>
                        <Link :href="route('support.index')"><el-button size="large">Agendar soporte técnico</el-button></Link>
                    </div>
                    <a v-if="whatsapp" :href="whatsapp" target="_blank" rel="noopener" class="hero__whatsapp">
                        ¿Dudas? Escríbenos por WhatsApp
                    </a>
                </div>

                <article v-if="hero" class="spotlight">
                    <Link :href="route('shop.product', hero.slug)" class="spotlight__image" tabindex="-1">
                        <ProductImage :src="hero.image_url" :alt="hero.name" size="400" />
                    </Link>
                    <div class="spotlight__body">
                        <p class="spotlight__kicker">Destacado<template v-if="hero.category"> en {{ hero.category }}</template></p>
                        <Link :href="route('shop.product', hero.slug)" class="spotlight__name">{{ hero.name }}</Link>
                        <div class="row spread">
                            <span class="price spotlight__price">{{ money(hero.price) }}</span>
                            <span class="small" :class="hero.stock > 0 ? 'success' : 'danger'">
                                {{ hero.stock > 0 ? 'Disponible' : 'Sin stock' }}
                            </span>
                        </div>
                        <AddToCart :product="hero" />
                    </div>
                </article>
            </div>
        </section>

        <BrandLine />

        <!-- Servicios -->
        <section id="servicios" class="section">
            <div class="container">
                <div class="section__head">
                    <h2>Qué hacemos</h2>
                    <p class="muted">Te ayudamos a elegir el equipo, lo dejamos funcionando y seguimos disponibles cuando algo falla.</p>
                </div>
                <ul class="services">
                    <li v-for="service in services" :key="service.title" class="service">
                        <span class="service__icon"><el-icon :size="22"><component :is="`Icon${service.icon}`" /></el-icon></span>
                        <div>
                            <h3>{{ service.title }}</h3>
                            <p>{{ service.text }}</p>
                            <template v-if="serviceAction(service)">
                                <a v-if="serviceAction(service).external" :href="serviceAction(service).href" target="_blank" rel="noopener" class="service__link">{{ serviceAction(service).label }}</a>
                                <a v-else-if="serviceAction(service).anchor" :href="serviceAction(service).href" class="service__link">{{ serviceAction(service).label }}</a>
                                <Link v-else :href="serviceAction(service).href" class="service__link">{{ serviceAction(service).label }}</Link>
                            </template>
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <!-- Productos destacados -->
        <section v-if="featured.length || categories.length" class="section section--tinted">
            <div class="container">
                <div class="section__head row spread">
                    <h2>Productos destacados</h2>
                    <Link :href="route('shop.index')">Ver toda la tienda</Link>
                </div>

                <div v-if="categories.length" class="row categories">
                    <Link v-for="c in categories" :key="c.id" :href="route('shop.index', { category: c.slug })" class="category">
                        {{ c.name }} <span class="muted">{{ c.products_count }}</span>
                    </Link>
                </div>

                <div class="products">
                    <article v-for="p in featured" :key="p.id" class="product">
                        <Link :href="route('shop.product', p.slug)" class="product__image" tabindex="-1">
                            <ProductImage :src="p.image_url" :alt="p.name" size="200" />
                        </Link>
                        <div class="product__body">
                            <span class="muted small">{{ p.category }}</span>
                            <Link :href="route('shop.product', p.slug)" class="product__name">{{ p.name }}</Link>
                            <div class="row spread product__foot">
                                <span class="price">{{ money(p.price) }}</span>
                                <AddToCart :product="p" size="small" />
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- Soporte técnico: es un proceso en pasos, por eso va numerado -->
        <section class="section support">
            <div class="container support__grid">
                <div>
                    <h2>Soporte técnico con hora reservada</h2>
                    <p class="support__lead">Sin esperar en línea ni ir a dejar el equipo a ciegas. Reservas el horario que te acomoda y llegamos sabiendo qué revisar.</p>
                    <Link :href="route('support.index')"><el-button type="primary" size="large" class="support__cta">Agendar un turno</el-button></Link>
                </div>
                <ol class="steps">
                    <li>
                        <h3>Elige día y hora</h3>
                        <p>Ves los horarios libres de la semana y reservas el que te sirva.</p>
                    </li>
                    <li>
                        <h3>Cuéntanos qué pasa</h3>
                        <p>Describe el problema y, si compraste aquí, asocia el pedido y el producto.</p>
                    </li>
                    <li>
                        <h3>Te atendemos a esa hora</h3>
                        <p>Confirmamos el turno y un técnico se hace cargo. Puedes cancelar con anticipación.</p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- Contacto -->
        <section id="contacto" class="section">
            <div class="container contact">
                <div>
                    <h2>Contacto</h2>
                    <p class="muted">Respondemos consultas de productos, cotizaciones de instalación y dudas de pedidos.</p>

                    <ul class="channels">
                        <li v-if="whatsapp">
                            <el-icon><IconChatDotRound /></el-icon>
                            <a :href="whatsapp" target="_blank" rel="noopener">WhatsApp</a>
                        </li>
                        <li v-if="contact.phone">
                            <el-icon><IconPhone /></el-icon>
                            <a :href="`tel:${contact.phone.replace(/\s/g, '')}`">{{ contact.phone }}</a>
                        </li>
                        <li v-if="contact.email">
                            <el-icon><IconMessage /></el-icon>
                            <a :href="`mailto:${contact.email}`">{{ contact.email }}</a>
                        </li>
                        <li v-if="contact.address">
                            <el-icon><IconLocation /></el-icon>
                            <a v-if="contact.maps_url" :href="contact.maps_url" target="_blank" rel="noopener">{{ contact.address }}</a>
                            <span v-else>{{ contact.address }}</span>
                        </li>
                        <li v-if="contact.hours">
                            <el-icon><IconClock /></el-icon>
                            <span>{{ contact.hours }}</span>
                        </li>
                    </ul>

                    <div v-if="site.social.length" class="social">
                        <a v-for="s in site.social" :key="s.name" :href="s.url" target="_blank" rel="noopener" class="social__link">{{ s.name }}</a>
                    </div>
                </div>

                <el-form class="contact__form" label-position="top" @submit.prevent="send">
                    <div class="row contact__pair">
                        <el-form-item label="Nombre" :error="form.errors.name">
                            <el-input v-model="form.name" autocomplete="name" />
                        </el-form-item>
                        <el-form-item label="Teléfono (opcional)" :error="form.errors.phone">
                            <el-input v-model="form.phone" autocomplete="tel" />
                        </el-form-item>
                    </div>
                    <el-form-item label="Correo" :error="form.errors.email">
                        <el-input v-model="form.email" type="email" autocomplete="email" />
                    </el-form-item>
                    <el-form-item label="Mensaje" :error="form.errors.message">
                        <el-input v-model="form.message" type="textarea" :rows="5" maxlength="3000" placeholder="¿En qué te podemos ayudar?" />
                    </el-form-item>
                    <!-- Campo trampa para bots: oculto para personas y lectores de pantalla -->
                    <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" class="trap" aria-hidden="true" />
                    <el-button type="primary" native-type="submit" :loading="form.processing">Enviar mensaje</el-button>
                </el-form>
            </div>
        </section>
    </ShopLayout>
</template>

<style scoped>
.hero { background: var(--surface); }
.hero__grid {
    display: grid;
    grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
    gap: 48px;
    align-items: center;
    padding-block: 64px 56px;
}
.hero__title {
    font-size: clamp(2.1rem, 4.6vw, 3.6rem);
    font-weight: 700;
    line-height: 1.05;
    letter-spacing: -0.035em;
    max-width: 17ch;
    margin-bottom: 20px;
}
.hero__lead { font-size: 1.15rem; line-height: 1.55; color: var(--muted); max-width: 46ch; margin: 0 0 28px; }
.hero__actions { gap: 12px; }
.hero__actions .el-button + .el-button { margin-left: 0; }
.hero__whatsapp { display: inline-block; margin-top: 20px; font-weight: 600; }

.spotlight { border: 1px solid var(--line); border-radius: 14px; overflow: hidden; background: var(--surface); }
.spotlight__image :deep(.thumb) { width: 100% !important; height: 260px !important; border: 0; border-radius: 0; }
.spotlight__body { padding: 18px 20px 20px; display: grid; gap: 10px; }
.spotlight__kicker { margin: 0; font-size: 0.85rem; color: var(--logo-purple); font-weight: 600; }
.spotlight__name { color: var(--ink); font-weight: 600; font-size: 1.15rem; letter-spacing: -0.015em; }
.spotlight__price { font-size: 1.5rem; }

.section { padding-block: 72px; }
.section--tinted { background: var(--paper); border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
.section__head { margin-bottom: 28px; align-items: baseline; }
.section__head h2 { font-size: clamp(1.6rem, 2.6vw, 2.1rem); margin: 0; }
.section__head p { max-width: 60ch; margin: 8px 0 0; line-height: 1.5; }

.services { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 48px; }
.service { display: flex; gap: 16px; padding-block: 24px; border-top: 1px solid var(--line); }
.service__icon {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    background: var(--brand-tint);
    color: var(--brand);
}
.service h3 { font-size: 1.1rem; margin-bottom: 6px; }
.service p { margin: 0 0 10px; line-height: 1.55; color: var(--muted); max-width: 52ch; }
.service__link { font-weight: 600; }

.categories { gap: 8px; margin-bottom: 20px; }
.category {
    padding: 6px 14px;
    border: 1px solid var(--line);
    border-radius: 999px;
    background: var(--surface);
    color: var(--ink);
    text-decoration: none;
    font-size: 0.92rem;
}
.category:hover { border-color: var(--brand); text-decoration: none; }

.products { display: grid; gap: 16px; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); }
.product { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius); overflow: hidden; display: flex; flex-direction: column; }
.product__image :deep(.thumb) { width: 100% !important; height: 170px !important; border: 0; border-radius: 0; }
.product__body { padding: 12px 14px 14px; display: flex; flex-direction: column; flex: 1; gap: 2px; }
.product__name { color: var(--ink); font-weight: 600; }
.product__foot { margin-top: auto; padding-top: 10px; }

.support { background: var(--brand-dark); color: #dbe9ef; }
.support__grid { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: 56px; align-items: start; }
.support h2 { color: #fff; font-size: clamp(1.6rem, 2.6vw, 2.1rem); }
.support__lead { line-height: 1.6; max-width: 42ch; margin-bottom: 24px; }
.support__cta { --el-button-bg-color: #fff; --el-button-text-color: var(--brand-dark); --el-button-border-color: #fff; --el-button-hover-bg-color: var(--brand-tint); --el-button-hover-text-color: var(--brand-dark); --el-button-hover-border-color: var(--brand-tint); }
.steps { list-style: none; counter-reset: step; margin: 0; padding: 0; display: grid; gap: 4px; }
.steps li { counter-increment: step; display: grid; grid-template-columns: 56px 1fr; padding-block: 16px; border-top: 1px solid rgba(255, 255, 255, 0.16); }
.steps li::before {
    content: counter(step);
    grid-row: span 2;
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
    color: var(--logo-green);
}
.steps h3 { color: #fff; font-size: 1.1rem; margin: 0 0 4px; }
.steps p { margin: 0; line-height: 1.5; }

.contact { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: 56px; }
.contact h2 { font-size: clamp(1.6rem, 2.6vw, 2.1rem); }
.channels { list-style: none; padding: 0; margin: 24px 0; display: grid; gap: 12px; }
.channels li { display: flex; gap: 10px; align-items: center; }
.channels .el-icon { color: var(--brand); }
.social { display: flex; flex-wrap: wrap; gap: 8px; }
.social__link { padding: 6px 14px; border: 1px solid var(--line); border-radius: 999px; color: var(--ink); text-decoration: none; }
.social__link:hover { border-color: var(--brand); text-decoration: none; }
.contact__form { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; padding: 24px; }
.contact__pair { align-items: flex-start; flex-wrap: nowrap; }
.contact__pair > * { flex: 1; min-width: 0; }
.trap { position: absolute; left: -10000px; width: 1px; height: 1px; opacity: 0; }

@media (max-width: 900px) {
    .hero__grid, .support__grid, .contact { grid-template-columns: 1fr; gap: 32px; }
    .hero__grid { padding-block: 40px; }
    .services { grid-template-columns: 1fr; }
    .section { padding-block: 52px; }
}
@media (max-width: 560px) {
    .contact__pair { flex-wrap: wrap; }
    .contact__pair > * { flex-basis: 100%; }
}
</style>
