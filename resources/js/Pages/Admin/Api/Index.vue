<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';

// Helper para obtener rutas directas
const getDirectRoute = (name, params = {}) => {
    const baseURL = window.location.origin;
    const routes = {
        'admin.api.action': `${baseURL}/admin/api/action/${params.action || ''}`,
    };
    return routes[name] ? routes[name] : '#';
};

// Estados
const isLoading = ref(false);
const apiStatus = ref(null);
const leaguesData = ref([]);
const teamsData = ref([]);
const matchesData = ref([]);
const configStatus = ref(null);
const activeTab = ref('overview');

// Estado para las pruebas de API
const testingConnection = ref(false);
const connectionStatus = ref(null);
const connectionMessage = ref('');
const connectionDetails = ref(null);

// Estado para sincronización de datos
const syncingLeagues = ref(false);
const syncingTeams = ref(false);
const syncingMatches = ref(false);
const syncResult = ref(null);

// Prueba la conexión con la API
const testConnection = async () => {
    connectionStatus.value = null;
    connectionMessage.value = '';
    connectionDetails.value = null;
    testingConnection.value = true;
    
    try {
        const response = await axios.get(getDirectRoute('admin.api.action', { action: 'test-connection' }));
        connectionStatus.value = response.data.success;
        connectionMessage.value = response.data.message;
        connectionDetails.value = response.data.details;
    } catch (error) {
        connectionStatus.value = false;
        connectionMessage.value = 'Error al conectar con la API';
        connectionDetails.value = error.response?.data?.message || error.message;
    } finally {
        testingConnection.value = false;
    }
};

// Syncronizar ligas desde la API
const syncLeagues = async () => {
    syncingLeagues.value = true;
    syncResult.value = null;
    
    try {
        const response = await axios.get(getDirectRoute('admin.api.action', { action: 'sync-leagues' }));
        syncResult.value = response.data;
        if (response.data.success) {
            leaguesData.value = response.data.data || [];
        }
    } catch (error) {
        syncResult.value = {
            success: false,
            message: 'Error al sincronizar ligas',
            details: error.response?.data?.message || error.message
        };
    } finally {
        syncingLeagues.value = false;
    }
};

// Sincronizar equipos desde la API
const syncTeams = async () => {
    syncingTeams.value = true;
    syncResult.value = null;
    
    try {
        const response = await axios.get(getDirectRoute('admin.api.action', { action: 'sync-teams' }));
        syncResult.value = response.data;
        if (response.data.success) {
            teamsData.value = response.data.data || [];
        }
    } catch (error) {
        syncResult.value = {
            success: false,
            message: 'Error al sincronizar equipos',
            details: error.response?.data?.message || error.message
        };
    } finally {
        syncingTeams.value = false;
    }
};

// Sincronizar partidos desde la API
const syncMatches = async () => {
    syncingMatches.value = true;
    syncResult.value = null;
    
    try {
        const response = await axios.get(getDirectRoute('admin.api.action', { action: 'fetch-today-matches' }));
        syncResult.value = response.data;
        if (response.data.success !== false) {
            matchesData.value = response.data.data || response.data;
        }
    } catch (error) {
        syncResult.value = {
            success: false,
            message: 'Error al sincronizar partidos',
            details: error.response?.data?.message || error.message
        };
    } finally {
        syncingMatches.value = false;
    }
};

// Guardar partidos en la base de datos
const saveMatches = async () => {
    if (!matchesData.value || matchesData.value.length === 0) {
        syncResult.value = {
            success: false,
            message: 'No hay partidos para guardar. Primero sincronice los partidos.'
        };
        return;
    }
    
    isLoading.value = true;
    syncResult.value = null;
    
    // Logging para debug en el cliente
    console.log('Saving matches data:', {
        count: matchesData.value.length,
        firstMatch: matchesData.value[0]
    });
    
    try {
        // Asegurarnos de que los datos están en formato de array
        const matchesToSave = Array.isArray(matchesData.value) ? 
            matchesData.value : 
            (matchesData.value.data || []);
        
        // Obtener el token CSRF manualmente del meta tag
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        const response = await axios.post(getDirectRoute('admin.api.action', { action: 'save-matches' }), {
            matches: matchesToSave,
            _token: csrfToken
        }, {
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        syncResult.value = response.data;
    } catch (error) {
        console.error('Error saving matches:', error);
        syncResult.value = {
            success: false,
            message: 'Error al guardar partidos',
            details: error.response?.data?.message || error.message
        };
    } finally {
        isLoading.value = false;
    }
};

// Configurar la API (mostrar modal de configuración)
const configureApi = () => {
    activeTab.value = 'config';
};
</script>

<template>
    <Head title="API de Fútbol" />

    <AdminLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="backdrop-blur-sm bg-black/60 border border-zinc-800 overflow-hidden shadow-xl rounded-xl p-6 relative">
                    <!-- Efecto de resplandor en la esquina -->
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-red-500/20 rounded-full blur-3xl opacity-30"></div>
                    
                    <div class="mb-6">
                        <h1 class="text-3xl font-bold text-white flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                            </svg>
                            API de Fútbol
                        </h1>
                        <p class="text-gray-400 mt-1">
                            Sincroniza datos de la API de fútbol para mantener al día los equipos, partidos y ligas en la plataforma.
                        </p>
                    </div>

                    <!-- Notificación de resultado -->
                    <div v-if="syncResult" :class="[
                        'mb-6 p-4 rounded-lg border',
                        syncResult.success 
                            ? 'bg-green-900/30 border-green-700 text-green-300' 
                            : 'bg-red-900/30 border-red-700 text-red-300'
                    ]">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg v-if="syncResult.success" class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <svg v-else class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium">{{ syncResult.message }}</h3>
                                <div v-if="syncResult.details" class="mt-2 text-sm">
                                    <p>{{ syncResult.details }}</p>
                                </div>
                                <div v-if="syncResult.data && syncResult.data.length" class="mt-2">
                                    <p class="text-sm">{{ syncResult.data.length }} elementos procesados.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones principales de acción -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                        <!-- Probar conexión -->
                        <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg overflow-hidden">
                            <button 
                                @click="testConnection"
                                :disabled="testingConnection"
                                class="w-full h-full p-4 text-left flex items-center space-x-3 hover:bg-zinc-900/30 transition-colors"
                            >
                                <div :class="[
                                    'flex-shrink-0 p-2 rounded-lg', 
                                    testingConnection ? 'bg-blue-900/30' : 'bg-red-900/30'
                                ]">
                                    <svg v-if="testingConnection" class="h-6 w-6 text-blue-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-6 w-6 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-white">Probar conexión</h3>
                                    <p class="text-sm text-gray-400">Verifica el estado de la API de fútbol</p>
                                </div>
                            </button>
                        </div>

                        <!-- Sincronizar ligas -->
                        <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg overflow-hidden">
                            <button 
                                @click="syncLeagues"
                                :disabled="syncingLeagues"
                                class="w-full h-full p-4 text-left flex items-center space-x-3 hover:bg-zinc-900/30 transition-colors"
                            >
                                <div :class="[
                                    'flex-shrink-0 p-2 rounded-lg', 
                                    syncingLeagues ? 'bg-blue-900/30' : 'bg-red-900/30'
                                ]">
                                    <svg v-if="syncingLeagues" class="h-6 w-6 text-blue-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-6 w-6 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-white">Sincronizar ligas</h3>
                                    <p class="text-sm text-gray-400">Actualizar ligas desde la API</p>
                                </div>
                            </button>
                        </div>

                        <!-- Sincronizar equipos -->
                        <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg overflow-hidden">
                            <button 
                                @click="syncTeams"
                                :disabled="syncingTeams"
                                class="w-full h-full p-4 text-left flex items-center space-x-3 hover:bg-zinc-900/30 transition-colors"
                            >
                                <div :class="[
                                    'flex-shrink-0 p-2 rounded-lg', 
                                    syncingTeams ? 'bg-blue-900/30' : 'bg-red-900/30'
                                ]">
                                    <svg v-if="syncingTeams" class="h-6 w-6 text-blue-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-6 w-6 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-white">Sincronizar equipos</h3>
                                    <p class="text-sm text-gray-400">Actualizar equipos desde la API</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Segunda fila de botones -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                        <!-- Sincronizar partidos -->
                        <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg overflow-hidden">
                            <button 
                                @click="syncMatches"
                                :disabled="syncingMatches"
                                class="w-full h-full p-4 text-left flex items-center space-x-3 hover:bg-zinc-900/30 transition-colors"
                            >
                                <div :class="[
                                    'flex-shrink-0 p-2 rounded-lg', 
                                    syncingMatches ? 'bg-blue-900/30' : 'bg-red-900/30'
                                ]">
                                    <svg v-if="syncingMatches" class="h-6 w-6 text-blue-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-6 w-6 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-white">Sincronizar partidos</h3>
                                    <p class="text-sm text-gray-400">Obtener partidos de hoy desde la API</p>
                                </div>
                            </button>
                        </div>

                        <!-- Configurar API -->
                        <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg overflow-hidden">
                            <button 
                                @click="configureApi"
                                class="w-full h-full p-4 text-left flex items-center space-x-3 hover:bg-zinc-900/30 transition-colors"
                            >
                                <div class="flex-shrink-0 p-2 rounded-lg bg-red-900/30">
                                    <svg class="h-6 w-6 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-white">Configurar API</h3>
                                    <p class="text-sm text-gray-400">Ajustar configuración de la API</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Resultados y contenido dinámico -->
                    <div v-if="connectionStatus !== null" class="mb-8 border border-zinc-800 rounded-lg overflow-hidden">
                        <div class="bg-zinc-900/50 px-4 py-3 border-b border-zinc-800">
                            <h3 class="font-medium text-white">Estado de conexión</h3>
                        </div>
                        <div class="p-4">
                            <div v-if="connectionStatus" class="rounded-md bg-green-900/30 p-4 border border-green-800">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-green-300">{{ connectionMessage }}</h3>
                                        <div v-if="connectionDetails" class="mt-2 text-sm text-green-200">
                                            <p>{{ connectionDetails }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="rounded-md bg-red-900/30 p-4 border border-red-800">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-300">{{ connectionMessage }}</h3>
                                        <div v-if="connectionDetails" class="mt-2 text-sm text-red-200">
                                            <p>{{ connectionDetails }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de partidos sincronizados -->
                    <div v-if="matchesData && matchesData.length > 0" class="mb-8 border border-zinc-800 rounded-lg overflow-hidden">
                        <div class="bg-zinc-900/50 px-4 py-3 border-b border-zinc-800 flex justify-between items-center">
                            <h3 class="font-medium text-white">Partidos sincronizados ({{ matchesData.length }})</h3>
                            <button 
                                @click="saveMatches" 
                                :disabled="isLoading"
                                class="inline-flex items-center px-3 py-1 rounded-md text-sm font-medium bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition"
                            >
                                <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Guardar en base de datos
                            </button>
                        </div>
                        <div class="p-4 max-h-96 overflow-y-auto">
                            <div class="grid grid-cols-1 gap-4">
                                <div 
                                    v-for="(match, index) in matchesData" 
                                    :key="match.id || index"
                                    class="border border-zinc-800 rounded-md p-3 bg-zinc-900/30 hover:bg-zinc-900/50 transition-colors"
                                >
                                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                                        <div class="mb-2 md:mb-0">
                                            <div class="text-xs text-red-400 mb-1">
                                                {{ match.league?.name || 'Liga no especificada' }} - {{ match.fixture?.date ? new Date(match.fixture.date).toLocaleDateString() : 'Fecha no especificada' }}
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="font-medium text-white">{{ match.teams?.home?.name || 'Equipo local' }}</span>
                                                <span class="text-gray-500">vs</span>
                                                <span class="font-medium text-white">{{ match.teams?.away?.name || 'Equipo visitante' }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-4">
                                            <div class="text-sm text-gray-400">
                                                <span>Estado: {{ match.fixture?.status || 'No iniciado' }}</span>
                                            </div>
                                            <div class="flex items-center bg-zinc-800 px-2 py-1 rounded">
                                                <span class="text-white font-medium">{{ match.goals?.home ?? '0' }}</span>
                                                <span class="text-gray-500 mx-1">-</span>
                                                <span class="text-white font-medium">{{ match.goals?.away ?? '0' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Animaciones y efectos adicionales */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>
