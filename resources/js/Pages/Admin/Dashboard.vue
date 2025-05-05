<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';

defineProps({
    stats: Object,
});

// Estado para las funciones del dashboard
const isLoading = ref(false);
const resultMessage = ref(null);
const resultStatus = ref(null);

// Helper para obtener rutas directas
const getDirectRoute = (name, params = {}) => {
    const baseURL = window.location.origin;
    const routes = {
        'admin.dashboard': `${baseURL}/admin/dashboard`,
        'admin.users': `${baseURL}/admin/users`,
        'admin.matches': `${baseURL}/admin/matches`,
        'admin.api': `${baseURL}/admin/api`,
        'admin.api.action': `${baseURL}/admin/api/action/${params.action || ''}`,
    };
    return routes[name] || '#';
};

// Función para llamar a las acciones de la API
const callApiAction = async (action, method = 'get', data = null) => {
    isLoading.value = true;
    resultMessage.value = null;
    resultStatus.value = null;
    
    try {
        const url = getDirectRoute('admin.api.action', { action });
        const response = method.toLowerCase() === 'post' 
            ? await axios.post(url, data)
            : await axios.get(url);
            
        resultStatus.value = response.data.success !== false;
        resultMessage.value = {
            text: response.data.message || (resultStatus.value ? 'Operación completada con éxito' : 'Error en la operación'),
            details: response.data.details || null
        };
        
        return response.data;
    } catch (error) {
        resultStatus.value = false;
        resultMessage.value = {
            text: 'Error al procesar la solicitud',
            details: error.response?.data?.message || error.message
        };
        return null;
    } finally {
        isLoading.value = false;
    }
};

// Métodos para acciones específicas
const testApiConnection = () => callApiAction('test-connection');
const fetchTodayMatches = () => callApiAction('fetch-today-matches');

// Datos para el dashboard (simulados)
const ligas = [
    { nombre: 'Premier League', partidos: 32, porcentaje: 25 },
    { nombre: 'La Liga', partidos: 28, porcentaje: 22 },
    { nombre: 'Serie A', partidos: 24, porcentaje: 19 },
    { nombre: 'Bundesliga', partidos: 21, porcentaje: 17 },
    { nombre: 'Ligue 1', partidos: 18, porcentaje: 14 },
    { nombre: 'Otras', partidos: 4, porcentaje: 3 },
];

// Estados de partidos (simulados)
const estados = [
    { nombre: 'Finalizados', cantidad: 65, color: 'green' },
    { nombre: 'Programados', cantidad: 42, color: 'blue' },
    { nombre: 'En juego', cantidad: 12, color: 'yellow' },
    { nombre: 'Suspendidos', cantidad: 8, color: 'red' },
];

// Partidos recientes (simulados)
const partidosRecientes = [
    { local: 'Barcelona', visitante: 'Real Madrid', resultado: '2-1', liga: 'La Liga', fecha: '2023-05-03' },
    { local: 'Liverpool', visitante: 'Manchester City', resultado: '1-1', liga: 'Premier League', fecha: '2023-05-02' },
    { local: 'Bayern Munich', visitante: 'Dortmund', resultado: '3-2', liga: 'Bundesliga', fecha: '2023-05-01' },
    { local: 'PSG', visitante: 'Lyon', resultado: '2-0', liga: 'Ligue 1', fecha: '2023-04-30' },
];
</script>

<template>
    <Head title="Panel de Administración" />

    <AdminLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="backdrop-blur-sm bg-black/60 border border-zinc-800 overflow-hidden shadow-xl rounded-xl p-6 relative">
                    <!-- Efecto de resplandor en la esquina -->
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-red-500/20 rounded-full blur-3xl opacity-30"></div>
                    
                    <h1 class="text-3xl font-bold text-white mb-8 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        Panel de Administración
                    </h1>

                    <!-- Aviso sobre tablas faltantes -->
                    <div v-if="stats?.partidos === 0" class="mb-6 p-4 rounded-lg bg-yellow-900/30 border border-yellow-700 text-yellow-300">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium">Configuración incompleta</h3>
                                <div class="mt-2 text-sm">
                                    <p>La tabla de partidos no existe en la base de datos. Por favor, ejecute las migraciones para crear la tabla football_matches.</p>
                                    <p class="mt-1">Sugerencia: utilice el comando <code class="bg-black/40 px-2 py-1 rounded">php artisan migrate</code> para crear las tablas.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notificación de resultado -->
                    <div v-if="resultMessage" :class="[
                        'mb-6 p-4 rounded-lg border flex',
                        resultStatus 
                            ? 'bg-green-900/30 border-green-700 text-green-300' 
                            : 'bg-red-900/30 border-red-700 text-red-300'
                    ]">
                        <div class="flex-shrink-0 mr-3">
                            <svg v-if="resultStatus" class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium">{{ resultMessage.text }}</h3>
                            <div v-if="resultMessage.details" class="mt-2 text-sm">
                                <p>{{ resultMessage.details }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tarjeta de Usuarios -->
                        <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 hover:border-red-500 rounded-lg p-4 transition-all duration-300 relative overflow-hidden">
                            <div class="absolute -bottom-16 -right-16 w-32 h-32 bg-red-500/10 rounded-full blur-3xl opacity-30"></div>
                            <div class="flex items-center mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-500 mr-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <div>
                                    <h2 class="text-white text-lg font-semibold">Usuarios</h2>
                                    <p class="text-gray-400 text-sm">Total registrados</p>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-white">{{ stats?.usuarios || 0 }}</p>
                            <a :href="getDirectRoute('admin.users')" class="inline-block mt-4 text-red-500 hover:text-red-400 text-sm font-medium transition-colors duration-300">
                                Ver usuarios →
                            </a>
                        </div>

                        <!-- Tarjeta de Partidos -->
                        <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 hover:border-red-500 rounded-lg p-4 transition-all duration-300 relative overflow-hidden">
                            <div class="absolute -bottom-16 -right-16 w-32 h-32 bg-red-500/10 rounded-full blur-3xl opacity-30"></div>
                            <div class="flex items-center mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-500 mr-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <div>
                                    <h2 class="text-white text-lg font-semibold">Partidos</h2>
                                    <p class="text-gray-400 text-sm">Total partidos</p>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-white">{{ stats?.partidos || 0 }}</p>
                            <div v-if="stats?.partidos === 0" class="mt-4 text-xs text-yellow-500">
                                Tabla no disponible
                            </div>
                            <a v-else :href="getDirectRoute('admin.matches')" class="inline-block mt-4 text-red-500 hover:text-red-400 text-sm font-medium transition-colors duration-300">
                                Ver partidos →
                            </a>
                        </div>
                    </div>

                    <!-- Panel de Estadísticas Deportivas -->
                    <div class="mt-10">
                        <h2 class="text-xl font-semibold text-white mb-4">Estadísticas Deportivas</h2>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Distribución de partidos por liga -->
                        <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg p-6 relative overflow-hidden">
                            <div class="absolute -top-20 -right-20 w-40 h-40 bg-red-500/10 rounded-full blur-3xl opacity-30"></div>
                            
                                <h3 class="text-lg font-medium text-white mb-4 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                                    </svg>
                                    Distribución por Ligas
                                </h3>
                                
                                <!-- Gráfico de barras horizontal simplificado -->
                                <div class="space-y-3">
                                    <div v-for="liga in ligas" :key="liga.nombre" class="flex items-center">
                                        <div class="w-24 text-sm text-gray-400 truncate mr-2">{{ liga.nombre }}</div>
                                        <div class="flex-1 h-6 bg-black/30 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full bg-gradient-to-r from-red-500/40 to-red-500/70 rounded-full"
                                                :style="{ width: liga.porcentaje + '%' }"
                                            ></div>
                                        </div>
                                        <div class="ml-2 text-sm text-gray-400 w-16 text-right">
                                            {{ liga.partidos }} ({{ liga.porcentaje }}%)
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4 text-xs text-gray-500 text-right">
                                    Total: 127 partidos
                                </div>
                            </div>
                            
                            <!-- Estado de partidos -->
                            <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg p-6 relative overflow-hidden">
                                <div class="absolute -top-20 -right-20 w-40 h-40 bg-blue-500/10 rounded-full blur-3xl opacity-30"></div>
                                
                                <h3 class="text-lg font-medium text-white mb-4 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                    Estado de Partidos
                                </h3>
                                
                                <!-- Gráfico de donas simplificado -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div v-for="estado in estados" :key="estado.nombre" 
                                        class="backdrop-blur-sm bg-black/30 border border-zinc-800 rounded-lg p-3 text-center">
                                        <div class="flex justify-center mb-2">
                                            <div :class="`h-10 w-10 rounded-full bg-${estado.color}-500/30 flex items-center justify-center`">
                                                <span :class="`text-${estado.color}-400 font-bold`">{{ estado.cantidad }}</span>
                                            </div>
                                        </div>
                                        <div class="text-sm text-gray-300">{{ estado.nombre }}</div>
                                    </div>
                                </div>
                                
                                <div class="mt-6 flex justify-center">
                                    <div class="h-2 w-full max-w-xs bg-black/30 rounded-full overflow-hidden flex">
                                        <div class="h-full bg-green-500/70" style="width: 51%"></div>
                                        <div class="h-full bg-blue-500/70" style="width: 33%"></div>
                                        <div class="h-full bg-yellow-500/70" style="width: 10%"></div>
                                        <div class="h-full bg-red-500/70" style="width: 6%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Partidos Recientes -->
                        <div class="mt-6 backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg p-6 relative overflow-hidden">
                            <div class="absolute -bottom-20 -left-20 w-40 h-40 bg-green-500/10 rounded-full blur-3xl opacity-30"></div>
                            
                            <h3 class="text-lg font-medium text-white mb-4 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Partidos Recientes Destacados
                            </h3>
                            
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Fecha</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Liga</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Equipos</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Resultado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-800">
                                        <tr v-for="partido in partidosRecientes" :key="partido.local + partido.visitante" class="hover:bg-zinc-900/30">
                                            <td class="px-4 py-3 text-sm text-gray-400">{{ partido.fecha }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-300">{{ partido.liga }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm text-white">{{ partido.local }} <span class="text-gray-500">vs</span> {{ partido.visitante }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-black/40 text-white border border-zinc-700">
                                                    {{ partido.resultado }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-4 text-center">
                                <a :href="getDirectRoute('admin.matches')" class="inline-flex items-center text-red-500 hover:text-red-400 text-sm font-medium transition-colors duration-300">
                                    <span>Ver todos los partidos</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Panel de acciones rápidas -->
                        <div class="mt-10">
                            <div class="flex flex-wrap items-center justify-between mb-4">
                                <h2 class="text-xl font-semibold text-white">Panel de la API</h2>
                            
                                <div class="flex items-center space-x-3 mt-2 sm:mt-0">
                                    <button @click="testApiConnection" 
                                        class="px-3 py-1.5 text-xs bg-zinc-800 hover:bg-zinc-700 text-white rounded border border-zinc-700 transition-colors duration-200 flex items-center"
                                        :disabled="isLoading"
                                    >
                                        <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Probar Conexión API
                                    </button>
                                    
                                    <button @click="fetchTodayMatches" 
                                        class="px-3 py-1.5 text-xs bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded border border-red-500/30 transition-colors duration-200 flex items-center"
                                        :disabled="isLoading"
                                    >
                                        <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Obtener Partidos de Hoy
                                    </button>
                                </div>
                            </div>
                        
                            <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg p-4 mb-6">
                                <p class="text-sm text-gray-300 mb-6">Utilice la API de fútbol para obtener información actualizada sobre partidos, equipos y ligas. Puede configurar y gestionar la conexión a la API desde el panel de administración.</p>
                                
                                <a :href="getDirectRoute('admin.matches')" class="inline-flex items-center text-red-500 hover:text-red-400 text-sm font-medium transition-colors duration-300">
                                    Ir a gestión de partidos →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
