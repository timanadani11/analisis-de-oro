<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { ADMIN_ROUTES, PUBLIC_ROUTES } from '@/routes';

const showingMobileMenu = ref(false);
const user = computed(() => usePage().props.auth.user);

const toggleMobileMenu = () => {
    showingMobileMenu.value = !showingMobileMenu.value;
};

// Helper para verificar la ruta actual
const isCurrentRoute = (route) => window.location.pathname === route;

// Menú de navegación del administrador
const adminNavItems = [
    { name: 'Dashboard', route: ADMIN_ROUTES.DASHBOARD, icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Usuarios', route: ADMIN_ROUTES.USERS, icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { name: 'Partidos', route: ADMIN_ROUTES.MATCHES, icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { name: 'Estadísticas', route: ADMIN_ROUTES.TEAM_STATS, icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
    { name: 'API Fútbol', route: ADMIN_ROUTES.API, icon: 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4' },
    { name: 'Test Football-Data', route: ADMIN_ROUTES.FOOTBALL_DATA_TEST, icon: 'M3 15a4 4 0 004 4h9a5 5 0 10-4.5-8.5' },
    { name: 'Importar Datos', route: ADMIN_ROUTES.FOOTBALL_DATA_IMPORT, icon: 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10' },
];

// Menú del sistema
const systemNavItems = [
    { name: 'Ver Sitio', route: PUBLIC_ROUTES.DASHBOARD, icon: 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14' },
    { name: 'Cerrar Sesión', route: ADMIN_ROUTES.LOGOUT, method: 'post', icon: 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1' },
];
</script>

<template>
    <div class="min-h-screen bg-zinc-950 text-white">
        <!-- Partículas animadas de fondo -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <!-- Partícula 1 -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-red-500/20 rounded-full blur-3xl opacity-30 animate-blob"></div>
            <!-- Partícula 2 -->
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-red-500/10 rounded-full blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
            <!-- Partícula 3 -->
            <div class="absolute top-1/2 left-1/3 w-96 h-96 bg-red-500/10 rounded-full blur-3xl opacity-20 animate-blob animation-delay-4000"></div>
        </div>
        
        <!-- Barra de navegación superior -->
        <div class="fixed top-0 left-0 right-0 h-16 backdrop-blur-md bg-black/60 border-b border-zinc-800 z-50 flex items-center justify-between px-4 lg:px-6">
            <div class="flex items-center">
                <!-- Botón de menú móvil -->
                <button @click="toggleMobileMenu" class="lg:hidden mr-2 text-gray-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                
                <!-- Logo y título -->
                <div class="flex items-center">
                    <ApplicationLogo class="h-10 w-10 text-white" />
                    <div class="ml-3">
                        <div class="text-xl font-bold">Sport <span class="text-red-500">Mind</span></div>
                        <div class="text-xs text-gray-400">Panel de Administración</div>
                    </div>
                </div>
            </div>
            
            <!-- Información del usuario -->
            <div class="flex items-center" v-if="user">
                <div class="mr-3 text-right hidden md:block">
                    <div class="text-sm font-medium">{{ user.name }}</div>
                    <div class="text-xs text-gray-400">Administrador</div>
                </div>
                <div class="h-10 w-10 rounded-full bg-red-500/20 flex items-center justify-center text-red-500 font-bold">
                    {{ user.name.charAt(0).toUpperCase() }}
                </div>
            </div>
        </div>
        
        <!-- Contenedor principal -->
        <div class="pt-16 lg:pt-0 flex h-screen">
            <!-- Sidebar para escritorio (fijo a la izquierda) -->
            <div class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0 pt-16">
                <div class="h-full backdrop-blur-sm bg-black/60 border-r border-zinc-800 overflow-y-auto">
                    <!-- Menú de navegación -->
                    <nav class="px-4 py-6 space-y-1">
                        <div class="text-xs text-gray-500 uppercase px-3 pb-2">Gestión</div>
                        
                        <div v-for="item in adminNavItems" :key="item.name" class="relative">
                            <Link 
                                :href="item.route"
                                class="group flex items-center w-full px-3 py-2 text-gray-300 hover:text-white rounded-lg transition-all duration-200 hover:bg-zinc-800/50 border border-transparent hover:border-zinc-800"
                                :class="{ 'bg-red-500/10 border-red-500/20 text-white': isCurrentRoute(item.route) }"
                            >
                                <svg 
                                    xmlns="http://www.w3.org/2000/svg" 
                                    class="h-5 w-5 mr-3 transition-colors duration-200"
                                    :class="isCurrentRoute(item.route) ? 'text-red-500' : 'text-gray-400 group-hover:text-red-500'"
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    stroke="currentColor" 
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                </svg>
                                <span>{{ item.name }}</span>
                                <div v-if="isCurrentRoute(item.route)" class="absolute right-0 h-full w-1 bg-red-500 rounded-l-md"></div>
                            </Link>
                        </div>
                        
                        <div class="border-t border-zinc-800 my-3"></div>
                        <div class="text-xs text-gray-500 uppercase px-3 pb-2">Sistema</div>
                        
                        <div v-for="item in systemNavItems" :key="item.name" class="relative">
                            <Link 
                                v-if="item.method === 'post'"
                                :href="item.route"
                                method="post"
                                as="button"
                                class="group flex items-center w-full px-3 py-2 text-gray-300 hover:text-white rounded-lg transition-all duration-200 hover:bg-zinc-800/50 border border-transparent hover:border-zinc-800"
                            >
                                <svg 
                                    xmlns="http://www.w3.org/2000/svg" 
                                    class="h-5 w-5 mr-3 text-gray-400 group-hover:text-red-500 transition-colors duration-200"
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    stroke="currentColor" 
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                </svg>
                                <span>{{ item.name }}</span>
                            </Link>
                            <Link 
                                v-else
                                :href="item.route"
                                class="group flex items-center w-full px-3 py-2 text-gray-300 hover:text-white rounded-lg transition-all duration-200 hover:bg-zinc-800/50 border border-transparent hover:border-zinc-800"
                            >
                                <svg 
                                    xmlns="http://www.w3.org/2000/svg" 
                                    class="h-5 w-5 mr-3 text-gray-400 group-hover:text-red-500 transition-colors duration-200"
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    stroke="currentColor" 
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                </svg>
                                <span>{{ item.name }}</span>
                            </Link>
                        </div>
                    </nav>
                </div>
            </div>
            
            <!-- Menú móvil (slide over) -->
            <div 
                v-if="showingMobileMenu" 
                class="lg:hidden fixed inset-0 z-50"
            >
                <!-- Overlay de fondo -->
                <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="toggleMobileMenu"></div>
                
                <!-- Contenido del menú móvil -->
                <div class="fixed inset-y-0 left-0 max-w-xs w-full backdrop-blur-sm bg-black/80 border-r border-zinc-800 overflow-y-auto transform transition-all ease-in-out duration-300">
                    <!-- Botón para cerrar -->
                    <div class="absolute top-4 right-4">
                        <button @click="toggleMobileMenu" class="text-gray-400 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Logo y nombre -->
                    <div class="pt-8 pb-6 flex flex-col items-center">
                        <ApplicationLogo class="h-16 w-16 text-white" />
                        <div class="mt-4 text-xl font-bold">Sport <span class="text-red-500">Mind</span></div>
                        <div class="text-xs text-gray-400">Panel de Administración</div>
                    </div>
                    
                    <!-- Menú de navegación móvil -->
                    <nav class="px-4 py-4 space-y-1">
                        <div class="text-xs text-gray-500 uppercase px-3 pb-2">Gestión</div>
                        
                        <div v-for="item in adminNavItems" :key="item.name" class="relative">
                            <Link 
                                :href="item.route"
                                class="group flex items-center w-full px-3 py-2 text-gray-300 hover:text-white rounded-lg transition-all duration-200 hover:bg-zinc-800/50 border border-transparent hover:border-zinc-800"
                                :class="{ 'bg-red-500/10 border-red-500/20 text-white': isCurrentRoute(item.route) }"
                                @click="toggleMobileMenu"
                            >
                                <svg 
                                    xmlns="http://www.w3.org/2000/svg" 
                                    class="h-5 w-5 mr-3 transition-colors duration-200"
                                    :class="isCurrentRoute(item.route) ? 'text-red-500' : 'text-gray-400 group-hover:text-red-500'"
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    stroke="currentColor" 
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                </svg>
                                <span>{{ item.name }}</span>
                            </Link>
                        </div>
                        
                        <div class="border-t border-zinc-800 my-3"></div>
                        <div class="text-xs text-gray-500 uppercase px-3 pb-2">Sistema</div>
                        
                        <div v-for="item in systemNavItems" :key="item.name" class="relative">
                            <Link 
                                v-if="item.method === 'post'"
                                :href="item.route"
                                method="post"
                                as="button"
                                class="group flex items-center w-full px-3 py-2 text-gray-300 hover:text-white rounded-lg transition-all duration-200 hover:bg-zinc-800/50 border border-transparent hover:border-zinc-800"
                                @click="toggleMobileMenu"
                            >
                                <svg 
                                    xmlns="http://www.w3.org/2000/svg" 
                                    class="h-5 w-5 mr-3 text-gray-400 group-hover:text-red-500 transition-colors duration-200"
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    stroke="currentColor" 
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                </svg>
                                <span>{{ item.name }}</span>
                            </Link>
                            <Link 
                                v-else
                                :href="item.route"
                                class="group flex items-center w-full px-3 py-2 text-gray-300 hover:text-white rounded-lg transition-all duration-200 hover:bg-zinc-800/50 border border-transparent hover:border-zinc-800"
                                @click="toggleMobileMenu"
                            >
                                <svg 
                                    xmlns="http://www.w3.org/2000/svg" 
                                    class="h-5 w-5 mr-3 text-gray-400 group-hover:text-red-500 transition-colors duration-200"
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    stroke="currentColor" 
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                </svg>
                                <span>{{ item.name }}</span>
                            </Link>
                        </div>
                    </nav>
                </div>
            </div>
            
            <!-- Contenido principal -->
            <div class="lg:pl-64 flex-1 overflow-auto">
                <main class="py-6 px-4 sm:px-6 lg:px-8">
                    <slot></slot>
                </main>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-blob {
    animation: blob 7s infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

@keyframes blob {
    0% {
        transform: translate(0px, 0px) scale(1);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
    100% {
        transform: translate(0px, 0px) scale(1);
    }
}
</style>
