import { router } from '@inertiajs/vue3';
import { PUBLIC_ROUTES, AUTH_ROUTES, ADMIN_ROUTES, buildUrl, isCurrentRoute } from '@/routes';

export function useNavigation() {
    // Navegación programática
    const navigate = (url, options = {}) => {
        router.visit(url, {
            preserveState: true,
            preserveScroll: true,
            ...options
        });
    };

    // Navegación con reemplazo (útil para redirecciones)
    const replace = (url, options = {}) => {
        router.visit(url, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            ...options
        });
    };

    // Navegación con método POST
    const post = (url, data = {}, options = {}) => {
        router.post(url, data, {
            preserveState: true,
            preserveScroll: true,
            ...options
        });
    };

    // Navegación con método PUT
    const put = (url, data = {}, options = {}) => {
        router.put(url, data, {
            preserveState: true,
            preserveScroll: true,
            ...options
        });
    };

    // Navegación con método DELETE
    const destroy = (url, options = {}) => {
        router.delete(url, {
            preserveState: true,
            preserveScroll: true,
            ...options
        });
    };

    // Verificar si una ruta está activa
    const isActive = (path) => {
        return isCurrentRoute(path);
    };

    // Obtener la ruta actual
    const getCurrentPath = () => {
        return window.location.pathname;
    };

    return {
        // Rutas
        routes: {
            public: PUBLIC_ROUTES,
            auth: AUTH_ROUTES,
            admin: ADMIN_ROUTES,
        },
        // Métodos de navegación
        navigate,
        replace,
        post,
        put,
        destroy,
        // Helpers
        isActive,
        getCurrentPath,
        buildUrl,
    };
} 