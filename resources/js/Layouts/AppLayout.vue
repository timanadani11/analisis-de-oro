<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useNavigation } from '@/composables/useNavigation';
import { PUBLIC_ROUTES, AUTH_ROUTES } from '@/routes';

const mobileMenuOpen = ref(false);
const { isActive } = useNavigation();

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

// Helper para verificar la ruta actual
const isCurrentRoute = (name) => {
    const path = window.location.pathname;
    const routes = {
        'dashboard': '/dashboard',
        'predictions': '/predictions',
        'matches': '/matches',
        'login': '/login',
        'register': '/register'
    };
    return path === routes[name];
};

// Enlaces de navegación principal
const navLinks = [
    { name: 'Dashboard', route: PUBLIC_ROUTES.DASHBOARD, icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Predicciones', route: PUBLIC_ROUTES.PREDICTIONS, icon: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7' },
    { name: 'Partidos', route: PUBLIC_ROUTES.MATCHES, icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
];

// Enlaces de cuenta (cuando no hay sesión)
const authLinks = [
    { name: 'Iniciar Sesión', route: PUBLIC_ROUTES.LOGIN, icon: 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1' },
    { name: 'Registrarse', route: PUBLIC_ROUTES.REGISTER, icon: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z' },
];

// Verificar si el usuario está autenticado
const auth = usePage().props.auth;
const user = auth?.user;

// Helper para obtener rutas directas
const getDirectRoute = (name) => {
    const baseURL = window.location.origin;
    const routes = {
        'profile.edit': `${baseURL}/profile`,
        'logout': `${baseURL}/logout`,
    };
    return routes[name] || '#';
};
</script>

<template>
    <div class="min-h-screen bg-zinc-950 text-white relative">
        <!-- Efectos de fondo simplificados -->
        <div class="absolute inset-0 pointer-events-none opacity-40">
            <!-- Partículas animadas más simples -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-red-500/5 rounded-full blur-3xl opacity-30"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-red-500/5 rounded-full blur-3xl opacity-20"></div>
        </div>
        
        <!-- Barra de navegación lateral -->
        <div class="flex h-screen">
            <!-- Sidebar para escritorio (fijo a la izquierda) -->
            <div class="hidden md:flex md:flex-col md:w-64 md:bg-black/60 md:backdrop-blur-sm md:border-r md:border-zinc-800 md:fixed md:inset-y-0">
                <!-- Logo y título -->
                <div class="p-6 border-b border-zinc-800 flex flex-col items-center justify-center">
                    <div class="relative w-20 h-20 mb-3">
                        <div class="absolute inset-0 rounded-full bg-gradient-to-r from-red-500 to-red-700 opacity-50 blur-lg"></div>
                        <div class="relative z-10 w-full h-full bg-zinc-900 rounded-full flex items-center justify-center border-2 border-red-500/50">
                            <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <h1 class="text-xl font-bold">Sport <span class="text-red-500">Mind</span></h1>
                    <p class="text-xs text-gray-400 text-center mt-1">Análisis deportivos avanzados</p>
                </div>
                
                <!-- Enlaces principales -->
                <nav class="mt-6 px-4 flex-1 overflow-y-auto">
                    <div class="space-y-1">
                        <div v-for="link in navLinks" :key="link.name">
                            <Link 
                                :href="link.route" 
                                :class="[
                                    'flex items-center px-4 py-3 text-sm transition-colors duration-200 rounded-lg',
                                    isActive(link.route)
                                        ? 'bg-red-500/10 text-white border border-red-500/20'
                                        : 'text-gray-400 hover:text-white hover:bg-zinc-800/50'
                                ]"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" :class="[
                                    'mr-3 h-5 w-5 transition-colors duration-200',
                                    isActive(link.route) ? 'text-red-500' : 'text-gray-500'
                                ]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon" />
                                </svg>
                                {{ link.name }}
                            </Link>
                        </div>
                    </div>
                </nav>
                
                <!-- Sección de cuenta -->
                <div class="px-4 py-6 border-t border-zinc-800">
                    <h5 class="text-xs uppercase text-gray-500 font-medium tracking-wider mb-2">Tu cuenta</h5>
                    
                    <!-- Menu para usuarios autenticados -->
                    <div v-if="user" class="space-y-1">
                        <Link
                            :href="getDirectRoute('profile.edit')"
                            class="flex items-center px-4 py-2 text-sm text-gray-400 hover:text-white rounded-lg hover:bg-zinc-800/50 transition-colors duration-200"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Mi perfil
                        </Link>
                        <Link
                            :href="PUBLIC_ROUTES.LOGOUT"
                            method="post"
                            as="button"
                            class="flex items-center px-4 py-2 text-sm text-gray-400 hover:text-white rounded-lg hover:bg-zinc-800/50 transition-colors duration-200 w-full text-left"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Cerrar sesión
                        </Link>
                    </div>
                    
                    <!-- Menu para usuarios no autenticados -->
                    <div v-else class="space-y-1">
                        <div v-for="link in authLinks" :key="link.name">
                            <Link
                                :href="link.route" 
                                class="flex items-center px-4 py-2 text-sm text-gray-400 hover:text-white rounded-lg hover:bg-zinc-800/50 transition-colors duration-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon" />
                                </svg>
                                {{ link.name }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Menú móvil -->
            <div class="md:hidden fixed top-0 inset-x-0 z-50 bg-black/60 backdrop-blur-sm border-b border-zinc-800">
                <div class="flex items-center justify-between px-4 py-3">
                    <div class="flex items-center">
                        <div class="h-8 w-8 bg-gradient-to-r from-red-500 to-red-700 rounded-lg flex items-center justify-center mr-2">
                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h1 class="text-lg font-bold">Sport <span class="text-red-500">Mind</span></h1>
                    </div>
                    
                    <button @click="toggleMobileMenu" class="p-2 rounded-lg text-gray-400 hover:text-white hover:bg-zinc-800/50">
                        <svg v-if="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- Menú desplegable móvil -->
                <div v-if="mobileMenuOpen" class="border-t border-zinc-800 py-2">
                    <div class="px-4 py-2 space-y-1">
                        <div v-for="link in navLinks" :key="link.name">
                            <Link 
                                :href="link.route" 
                                :class="[
                                    'flex items-center px-4 py-3 text-sm transition-colors duration-200 rounded-lg',
                                    isActive(link.route)
                                        ? 'bg-red-500/10 text-white border border-red-500/20'
                                        : 'text-gray-400 hover:text-white hover:bg-zinc-800/50'
                                ]"
                                @click="mobileMenuOpen = false"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" :class="[
                                    'mr-3 h-5 w-5 transition-colors duration-200',
                                    isActive(link.route) ? 'text-red-500' : 'text-gray-500'
                                ]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon" />
                                </svg>
                                {{ link.name }}
                            </Link>
                        </div>
                    </div>
                    
                    <div class="mt-4 border-t border-zinc-800 px-4 py-2">
                        <!-- Usuario autenticado (móvil) -->
                        <div v-if="user" class="space-y-1">
                            <Link
                                :href="getDirectRoute('profile.edit')"
                                class="flex items-center px-4 py-3 text-sm text-gray-400 hover:text-white rounded-lg hover:bg-zinc-800/50 transition-colors duration-200"
                                @click="mobileMenuOpen = false"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Mi perfil
                            </Link>
                            <Link
                                :href="PUBLIC_ROUTES.LOGOUT"
                                method="post"
                                as="button"
                                class="flex items-center px-4 py-3 text-sm text-gray-400 hover:text-white rounded-lg hover:bg-zinc-800/50 transition-colors duration-200 w-full text-left"
                                @click="mobileMenuOpen = false"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Cerrar sesión
                            </Link>
                        </div>
                        
                        <!-- Usuario no autenticado (móvil) -->
                        <div v-else class="space-y-1">
                            <div v-for="link in authLinks" :key="link.name">
                                <Link 
                                    :href="link.route" 
                                    class="flex items-center px-4 py-3 text-sm text-gray-400 hover:text-white rounded-lg hover:bg-zinc-800/50 transition-colors duration-200"
                                    @click="mobileMenuOpen = false"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon" />
                                    </svg>
                                    {{ link.name }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contenido principal -->
            <div class="flex-1 md:ml-64">
                <main class="pt-16 md:pt-0">
                    <slot></slot>
                </main>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Estilos mínimos requeridos, eliminados los demás para rendimiento */
@keyframes pulse {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 0.1; }
}
</style>