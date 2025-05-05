import '../css/app.css';
import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

// Mapa de rutas global
window.routeMap = {
    'dashboard': '/',
    'predictions': '/predictions',
    'matches': '/matches',
    'analysis': '/analysis',
    'teams': '/teams',
    'statistics': '/statistics',
    'premium': '/premium',
    'profile.edit': '/profile',
    'login': '/login',
    'register': '/register',
    'admin.dashboard': '/admin/dashboard',
    'admin.users': '/admin/users',
    'admin.matches': '/admin/matches',
    'admin.api': '/admin/api',
    'admin.login': '/admin/login',
    'logout': '/logout'
};

// Helper de navegación global
window.navigateTo = (name, params = {}) => {
    const route = window.routeMap[name];
    if (!route) return '#';
    
    let url = route;
    // Reemplazar parámetros si existen
    if (Object.keys(params).length > 0) {
        Object.keys(params).forEach(key => {
            url = url.replace(`:${key}`, params[key]);
        });
    }
    return window.location.origin + url;
};

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        app.config.globalProperties.$route = (name, params) => window.navigateTo(name, params);
        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
