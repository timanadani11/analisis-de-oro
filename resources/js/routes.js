// Rutas públicas
export const PUBLIC_ROUTES = {
    HOME: '/',
    DASHBOARD: '/dashboard',
    PREDICTIONS: '/predictions',
    MATCHES: '/matches',
    MATCHES_BY_DATE: (date) => `/matches/date/${date}`,
    LOGIN: '/login',
    REGISTER: '/register',
    LOGOUT: '/logout',
};

// Rutas protegidas (requieren autenticación)
export const AUTH_ROUTES = {
    PROFILE: '/profile',
    ANALYSIS: '/analysis',
    TEAMS: '/teams',
    STATISTICS: '/statistics',
    PREMIUM: '/premium',
};

// Rutas de administración
export const ADMIN_ROUTES = {
    LOGIN: '/admin/login',
    DASHBOARD: '/admin/dashboard',
    USERS: '/admin/users',
    MATCHES: '/admin/matches',
    SUBSCRIPTIONS: '/admin/subscriptions',
    API: '/admin/api',
    FOOTBALL_DATA_TEST: '/admin/football-data-test',
    FOOTBALL_DATA_IMPORT: '/admin/football-data/import',
    FETCH_TODAY_MATCHES: '/admin/fetch-today-matches',
    SAVE_MATCHES: '/admin/save-matches',
    TEST_API_CONNECTION: '/admin/test-api-connection',
    LOGOUT: '/admin/logout',
};

// Helper para construir URLs con parámetros
export const buildUrl = (baseUrl, params = {}) => {
    const url = new URL(baseUrl, window.location.origin);
    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
            url.searchParams.append(key, value);
        }
    });
    return url.pathname + url.search;
};

// Helper para verificar si una ruta coincide con la actual
export const isCurrentRoute = (path) => {
    return window.location.pathname === path;
};

// Helper para obtener la ruta actual
export const getCurrentRoute = () => {
    return window.location.pathname;
};
