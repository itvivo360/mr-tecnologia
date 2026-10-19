// Element Plus primero: app.css redefine sus variables (color de marca) y debe cargarse después.
import 'element-plus/dist/index.css';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import ElementPlus from 'element-plus';
import es from 'element-plus/es/locale/lang/es';
import * as ElementPlusIcons from '@element-plus/icons-vue';
import axios from 'axios';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios = axios;

const appName = import.meta.env.VITE_APP_NAME || 'Tienda';

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(ElementPlus, { locale: es });

        for (const [name, component] of Object.entries(ElementPlusIcons)) {
            app.component(`Icon${name}`, component);
        }

        app.mount(el);
    },
    progress: { color: '#0f766e' },
});
