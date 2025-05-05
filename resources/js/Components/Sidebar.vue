<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { computed, onMounted } from 'vue';
import { ROUTES } from '../routes.js';

const props = defineProps({
    showMobileMenu: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const user = computed(() => usePage().props.auth.user);

// Navegación directa sin Ziggy
const navigateTo = (routeName) => {
    console.log('Navegando a:', routeName);
    
    if (routeName === 'logout') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/logout';
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_token';
            input.value = csrfToken.content;
            form.appendChild(input);
        }
        
        document.body.appendChild(form);
        form.submit();
        return;
    }

    window.location.href = route(routeName);
};

// Verificar si la ruta actual coincide con la ruta dada
const isCurrentRoute = (routeName) => {
    return route().current(routeName);
};

// Inicializar navegación cuando el componente se monta
onMounted(() => {
    console.log('Sidebar montado, configurando navegación directa...');
});

const closeMenu = () => {
    emit('close');
};

// Menú público accesible para todos los usuarios
const publicMenuItems = [
    { name: 'Dashboard', route: ROUTES.DASHBOARD, icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Predicciones', route: ROUTES.PREDICTIONS, icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
    { name: 'Partidos', route: ROUTES.MATCHES, icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
];

// Menú de funciones que requieren autenticación
const authOnlyMenuItems = computed(() => {
    if (user.value) {
        return [
            { name: 'Análisis', route: ROUTES.ANALYSIS, icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
            { name: 'Equipos', route: ROUTES.TEAMS, icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
            { name: 'Estadísticas', route: ROUTES.STATISTICS, icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
            { name: 'Premium', route: ROUTES.PREMIUM, icon: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' },
        ];
    }
    return [];
});

// Menú de administración - Completamente eliminado en la interfaz principal
// Los administradores ingresan directamente a su panel separado
const adminMenuItems = computed(() => {
    return [];
});

// Opciones de autenticación (Iniciar sesión/Registrarse o Perfil)
const authItems = computed(() => {
    if (user.value) {
        return [
            { name: 'Perfil', route: ROUTES.PROFILE_EDIT, icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' }
        ];
    } else {
        return [
            { name: 'Iniciar Sesión', route: ROUTES.LOGIN, icon: 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1' },
            { name: 'Registrarse', route: ROUTES.REGISTER, icon: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z' }
        ];
    }
});
</script>

<template>
    <!-- Sidebar para escritorio (fijo a la izquierda) -->
    <div class="hidden lg:flex lg:w-72 lg:flex-col lg:fixed lg:inset-y-0 z-20">
        <div class="h-full backdrop-blur-sm bg-black/60 border-r border-zinc-800 overflow-y-auto">
            <!-- Logo y nombre del sitio -->
            <div class="pt-8 pb-6 flex flex-col items-center">
                <Link href="/" class="group">
                    <div class="h-20 w-20 relative mx-auto">
                        <div class="absolute inset-0 bg-[#FF2D20] rounded-md opacity-60 blur-md group-hover:opacity-100 group-hover:blur-lg transition-all duration-500 animate-pulse"></div>
                        <ApplicationLogo class="h-20 w-20 text-white relative z-10 hover:scale-105 transition-transform duration-300" />
                    </div>
                    <div class="text-2xl text-center mt-4 font-extrabold text-white tracking-tight">
                        Sport <span class="text-[#FF2D20]">Mind</span>
                    </div>
                </Link>
                <div class="text-gray-400 text-sm mt-2">Análisis deportivos avanzados</div>
            </div>
            
            <!-- Menú de navegación -->
            <nav class="px-4 space-y-2 mb-8">
                <!-- Sección del menú público -->
                <div class="pb-2">
                    <div v-for="item in publicMenuItems" :key="item.name" class="relative">
                        <Link 
                            :href="item.route" 
                            class="sidebar-nav-link group flex items-center px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-all duration-200 hover:bg-zinc-800/50 border border-transparent hover:border-zinc-800"
                            :class="{ 'bg-[#FF2D20]/10 border-[#FF2D20]/20 text-white': isCurrentRoute(item.route) }"
                            :data-route="item.route"
                            @click="navigateTo(item.route); $event.preventDefault();"
                        >
                            <!-- Icono del elemento -->
                            <svg 
                                xmlns="http://www.w3.org/2000/svg" 
                                class="h-5 w-5 mr-3 transition-colors duration-200"
                                :class="isCurrentRoute(item.route) ? 'text-[#FF2D20]' : 'text-gray-400 group-hover:text-[#FF2D20]'"
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor" 
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                            </svg>
                            
                            <!-- Nombre del elemento -->
                            <span>{{ item.name }}</span>
                            
                            <!-- Indicador de elemento activo -->
                            <div v-if="isCurrentRoute(item.route)" class="absolute right-0 h-full w-1 bg-[#FF2D20] rounded-l-md"></div>
                        </Link>
                    </div>
                </div>

                <!-- Separador si hay elementos solo para usuarios autenticados -->
                <div v-if="authOnlyMenuItems.length > 0" class="border-t border-zinc-800 my-3"></div>

                <!-- Sección del menú solo para usuarios autenticados -->
                <div v-if="authOnlyMenuItems.length > 0" class="pb-2">
                    <div class="text-xs text-gray-500 uppercase px-4 py-2">Área Premium</div>
                    <div v-for="item in authOnlyMenuItems" :key="item.name" class="relative">
                        <Link 
                            :href="item.route" 
                            class="sidebar-nav-link group flex items-center px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-all duration-200 hover:bg-zinc-800/50 border border-transparent hover:border-zinc-800"
                            :class="{ 'bg-[#FF2D20]/10 border-[#FF2D20]/20 text-white': isCurrentRoute(item.route) }"
                            :data-route="item.route"
                            @click="navigateTo(item.route); $event.preventDefault();"
                        >
                            <svg 
                                xmlns="http://www.w3.org/2000/svg" 
                                class="h-5 w-5 mr-3 transition-colors duration-200"
                                :class="isCurrentRoute(item.route) ? 'text-[#FF2D20]' : 'text-gray-400 group-hover:text-[#FF2D20]'"
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor" 
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                            </svg>
                            <span>{{ item.name }}</span>
                            <div v-if="isCurrentRoute(item.route)" class="absolute right-0 h-full w-1 bg-[#FF2D20] rounded-l-md"></div>
                        </Link>
                    </div>
                </div>

                <!-- Separador si hay elementos de administración -->
                <div v-if="adminMenuItems.length > 0" class="border-t border-zinc-800 my-3"></div>

                <!-- Sección del menú de administración -->
                <div v-if="adminMenuItems.length > 0" class="pb-2">
                    <div class="text-xs text-gray-500 uppercase px-4 py-2">Administración</div>
                    <div v-for="item in adminMenuItems" :key="item.name" class="relative">
                        <Link 
                            :href="item.route" 
                            class="sidebar-nav-link group flex items-center px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-all duration-200 hover:bg-zinc-800/50 border border-transparent hover:border-zinc-800"
                            :class="{ 'bg-[#FF2D20]/10 border-[#FF2D20]/20 text-white': isCurrentRoute(item.route) }"
                            :data-route="item.route"
                            @click="navigateTo(item.route); $event.preventDefault();"
                        >
                            <svg 
                                xmlns="http://www.w3.org/2000/svg" 
                                class="h-5 w-5 mr-3 transition-colors duration-200"
                                :class="isCurrentRoute(item.route) ? 'text-[#FF2D20]' : 'text-gray-400 group-hover:text-[#FF2D20]'"
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor" 
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                            </svg>
                            <span>{{ item.name }}</span>
                            <div v-if="isCurrentRoute(item.route)" class="absolute right-0 h-full w-1 bg-[#FF2D20] rounded-l-md"></div>
                        </Link>
                    </div>
                </div>

                <div class="border-t border-zinc-800 my-3"></div>

                <!-- Elementos de autenticación -->
                <div class="pb-2">
                    <div class="text-xs text-gray-500 uppercase px-4 py-2">Tu cuenta</div>
                    <div v-for="item in authItems" :key="item.name" class="relative">
                        <Link 
                            :href="item.route" 
                            class="sidebar-nav-link group flex items-center px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-all duration-200 hover:bg-zinc-800/50 border border-transparent hover:border-zinc-800"
                            :class="{ 'bg-[#FF2D20]/10 border-[#FF2D20]/20 text-white': isCurrentRoute(item.route) }"
                            :data-route="item.route"
                            @click="navigateTo(item.route); $event.preventDefault();"
                        >
                            <svg 
                                xmlns="http://www.w3.org/2000/svg" 
                                class="h-5 w-5 mr-3 transition-colors duration-200"
                                :class="isCurrentRoute(item.route) ? 'text-[#FF2D20]' : 'text-gray-400 group-hover:text-[#FF2D20]'"
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor" 
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                            </svg>
                            <span>{{ item.name }}</span>
                            <div v-if="isCurrentRoute(item.route)" class="absolute right-0 h-full w-1 bg-[#FF2D20] rounded-l-md"></div>
                        </Link>
                    </div>
                </div>
            </nav>
            
            <!-- Información del usuario -->
            <div class="px-6 mt-auto" v-if="user">
                <div class="py-4 border-t border-zinc-800">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-[#FF2D20]/20 flex items-center justify-center text-[#FF2D20] font-bold">
                                {{ user.name.charAt(0).toUpperCase() }}
                            </div>
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-white">{{ user.name }}</div>
                            <Link :href="ROUTES.LOGOUT" method="post" as="button" class="text-xs text-gray-400 hover:text-[#FF2D20] transition-colors duration-200">
                                Cerrar sesión
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar móvil (slide-over) -->
    <div 
        v-if="showMobileMenu" 
        class="lg:hidden fixed inset-0 z-40"
    >
        <!-- Overlay de fondo -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeMenu"></div>
        
        <!-- Contenido del sidebar -->
        <div class="fixed inset-y-0 left-0 max-w-xs w-full backdrop-blur-sm bg-black/60 border-r border-zinc-800 overflow-y-auto z-50 transform transition-all ease-in-out duration-300">
            <!-- Botón para cerrar -->
            <div class="absolute top-4 right-4">
                <button @click="closeMenu" class="text-gray-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <!-- Logo y nombre del sitio -->
            <div class="pt-8 pb-6 flex flex-col items-center">
                <Link href="/" class="group" @click="closeMenu">
                    <div class="h-20 w-20 relative mx-auto">
                        <div class="absolute inset-0 bg-[#FF2D20] rounded-md opacity-60 blur-md group-hover:opacity-100 group-hover:blur-lg transition-all duration-500 animate-pulse"></div>
                        <ApplicationLogo class="h-20 w-20 text-white relative z-10 hover:scale-105 transition-transform duration-300" />
                    </div>
                    <div class="text-2xl text-center mt-4 font-extrabold text-white tracking-tight">
                        Sport <span class="text-[#FF2D20]">Mind</span>
                    </div>
                </Link>
                <div class="text-gray-400 text-sm mt-2">Análisis deportivos avanzados</div>
            </div>
            
            <!-- Menú de navegación -->
            <nav class="px-4 space-y-2">
                <!-- Sección del menú público -->
                <div class="pb-2">
                    <div v-for="item in publicMenuItems" :key="item.name" class="relative">
                        <Link 
                            :href="item.route" 
                            class="sidebar-nav-link group flex items-center px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-all duration-200 hover:bg-zinc-800/50 border border-transparent hover:border-zinc-800"
                            :class="{ 'bg-[#FF2D20]/10 border-[#FF2D20]/20 text-white': isCurrentRoute(item.route) }"
                            :data-route="item.route"
                            @click.prevent="navigateTo(item.route)"
                        >
                            <svg 
                                xmlns="http://www.w3.org/2000/svg" 
                                class="h-5 w-5 mr-3 transition-colors duration-200"
                                :class="isCurrentRoute(item.route) ? 'text-[#FF2D20]' : 'text-gray-400 group-hover:text-[#FF2D20]'"
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor" 
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                            </svg>
                            <span>{{ item.name }}</span>
                            <div v-if="isCurrentRoute(item.route)" class="absolute right-0 h-full w-1 bg-[#FF2D20] rounded-l-md"></div>
                        </Link>
                    </div>
                </div>

                <!-- Separador si hay elementos solo para usuarios autenticados -->
                <div v-if="authOnlyMenuItems.length > 0" class="border-t border-zinc-800 my-3"></div>

                <!-- Sección del menú solo para usuarios autenticados -->
                <div v-if="authOnlyMenuItems.length > 0" class="pb-2">
                    <div class="text-xs text-gray-500 uppercase px-4 py-2">Área Premium</div>
                    <div v-for="item in authOnlyMenuItems" :key="item.name" class="relative">
                        <Link 
                            :href="item.route" 
                            class="sidebar-nav-link group flex items-center px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-all duration-200 hover:bg-zinc-800/50 border border-transparent hover:border-zinc-800"
                            :class="{ 'bg-[#FF2D20]/10 border-[#FF2D20]/20 text-white': isCurrentRoute(item.route) }"
                            :data-route="item.route"
                            @click.prevent="navigateTo(item.route)"
                        >
                            <svg 
                                xmlns="http://www.w3.org/2000/svg" 
                                class="h-5 w-5 mr-3 transition-colors duration-200"
                                :class="isCurrentRoute(item.route) ? 'text-[#FF2D20]' : 'text-gray-400 group-hover:text-[#FF2D20]'"
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor" 
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                            </svg>
                            <span>{{ item.name }}</span>
                            <div v-if="isCurrentRoute(item.route)" class="absolute right-0 h-full w-1 bg-[#FF2D20] rounded-l-md"></div>
                        </Link>
                    </div>
                </div>

                <!-- Separador si hay elementos de administración -->
                <div v-if="adminMenuItems.length > 0" class="border-t border-zinc-800 my-3"></div>

                <!-- Sección del menú de administración -->
                <div v-if="adminMenuItems.length > 0" class="pb-2">
                    <div class="text-xs text-gray-500 uppercase px-4 py-2">Administración</div>
                    <div v-for="item in adminMenuItems" :key="item.name" class="relative">
                        <Link 
                            :href="item.route" 
                            class="sidebar-nav-link group flex items-center px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-all duration-200 hover:bg-zinc-800/50 border border-transparent hover:border-zinc-800"
                            :class="{ 'bg-[#FF2D20]/10 border-[#FF2D20]/20 text-white': isCurrentRoute(item.route) }"
                            :data-route="item.route"
                            @click.prevent="navigateTo(item.route)"
                        >
                            <svg 
                                xmlns="http://www.w3.org/2000/svg" 
                                class="h-5 w-5 mr-3 transition-colors duration-200"
                                :class="isCurrentRoute(item.route) ? 'text-[#FF2D20]' : 'text-gray-400 group-hover:text-[#FF2D20]'"
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor" 
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                            </svg>
                            <span>{{ item.name }}</span>
                            <div v-if="isCurrentRoute(item.route)" class="absolute right-0 h-full w-1 bg-[#FF2D20] rounded-l-md"></div>
                        </Link>
                    </div>
                </div>

                <div class="border-t border-zinc-800 my-3"></div>

                <!-- Elementos de autenticación para móvil -->
                <div class="pb-2">
                    <div class="text-xs text-gray-500 uppercase px-4 py-2">Tu cuenta</div>
                    <div v-for="item in authItems" :key="item.name" class="relative">
                        <Link 
                            :href="item.route" 
                            class="sidebar-nav-link group flex items-center px-4 py-3 text-gray-300 hover:text-white rounded-lg transition-all duration-200 hover:bg-zinc-800/50 border border-transparent hover:border-zinc-800"
                            :class="{ 'bg-[#FF2D20]/10 border-[#FF2D20]/20 text-white': isCurrentRoute(item.route) }"
                            :data-route="item.route"
                            @click.prevent="navigateTo(item.route)"
                        >
                            <svg 
                                xmlns="http://www.w3.org/2000/svg" 
                                class="h-5 w-5 mr-3 transition-colors duration-200"
                                :class="isCurrentRoute(item.route) ? 'text-[#FF2D20]' : 'text-gray-400 group-hover:text-[#FF2D20]'"
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor" 
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                            </svg>
                            <span>{{ item.name }}</span>
                            <div v-if="isCurrentRoute(item.route)" class="absolute right-0 h-full w-1 bg-[#FF2D20] rounded-l-md"></div>
                        </Link>
                    </div>
                </div>
            </nav>
            
            <!-- Información del usuario -->
            <div class="px-6 mt-auto" v-if="user">
                <div class="py-4 border-t border-zinc-800">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-[#FF2D20]/20 flex items-center justify-center text-[#FF2D20] font-bold">
                                {{ user.name.charAt(0).toUpperCase() }}
                            </div>
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-white">{{ user.name }}</div>
                            <Link :href="ROUTES.LOGOUT" method="post" as="button" class="text-xs text-gray-400 hover:text-[#FF2D20] transition-colors duration-200">
                                Cerrar sesión
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes pulse {
  0%, 100% { opacity: 0.6; }
  50% { opacity: 1; }
}
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
