<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
    stats: Object,
    leagues: Array,
});

// Estado local
const isLoading = ref(false);
const selectedLeagueCode = ref('CL');
const selectedSeason = ref(new Date().getFullYear());
const operationStatus = ref(null);
const errorMessage = ref('');

// Ligas populares para fácil selección
const popularLeagues = [
    { code: 'CL', name: 'UEFA Champions League' },
    { code: 'PL', name: 'Premier League' },
    { code: 'PD', name: 'LaLiga' },
    { code: 'BL1', name: 'Bundesliga' },
    { code: 'SA', name: 'Serie A' },
    { code: 'FL1', name: 'Ligue 1' },
    { code: 'WC', name: 'FIFA World Cup' },
    { code: 'DED', name: 'Eredivisie' },
    { code: 'BSA', name: 'Brasileirao' },
    { code: 'CLI', name: 'Copa Libertadores' },
];

// Función para obtener un código de liga válido para la API
function getValidLeagueCode(leagueId) {
    // Convertir el ID numérico a string si es necesario
    const leagueIdStr = String(leagueId);
    
    // Verificar si el ID es numérico y convertirlo a un formato de código si es necesario
    if (!isNaN(leagueId) && leagueIdStr.length > 0) {
        // Si es un ID numérico, usar un prefijo + ID limitado a 8 caracteres para mantener bajo 10 chars
        return 'L' + leagueIdStr.substring(0, 8);
    }
    
    // Si ya es un código válido o no podemos procesarlo, devolverlo como está
    return leagueIdStr.substring(0, 10); // Limitamos a 10 caracteres por la validación
}

// Temporadas disponibles
const availableSeasons = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);

// Importar ligas/competiciones
async function importLeagues() {
    isLoading.value = true;
    operationStatus.value = null;
    errorMessage.value = '';
    
    try {
        const response = await axios.post('/admin/football-data/import/leagues');
        operationStatus.value = response.data;
    } catch (error) {
        errorMessage.value = 'Error al importar ligas: ' + (error.message || 'Error desconocido');
    } finally {
        isLoading.value = false;
    }
}

// Importar equipos
async function importTeams() {
    if (!selectedLeagueCode.value) {
        errorMessage.value = 'Selecciona una liga primero';
        return;
    }
    
    isLoading.value = true;
    operationStatus.value = null;
    errorMessage.value = '';
    
    console.log('Importando equipos con league_code:', selectedLeagueCode.value);
    
    try {
        const response = await axios.post('/admin/football-data/import/teams', {
            league_code: selectedLeagueCode.value,
            season: selectedSeason.value
        });
        operationStatus.value = response.data;
    } catch (error) {
        console.error('Error importando equipos:', error.response || error);
        
        if (error.response && error.response.data) {
            if (error.response.data.errors && error.response.data.errors.league_code) {
                errorMessage.value = 'Error en código de liga: ' + error.response.data.errors.league_code.join(', ');
            } else {
                errorMessage.value = 'Error al importar equipos: ' + (error.response.data.message || 'Error desconocido');
            }
        } else {
            errorMessage.value = 'Error al importar equipos: ' + (error.message || 'Error desconocido');
        }
    } finally {
        isLoading.value = false;
    }
}

// Importar estadísticas
async function importTeamStats() {
    if (!selectedLeagueCode.value) {
        errorMessage.value = 'Selecciona una liga primero';
        return;
    }
    
    isLoading.value = true;
    operationStatus.value = null;
    errorMessage.value = '';
    
    console.log('Importando estadísticas con league_code:', selectedLeagueCode.value);
    
    try {
        const response = await axios.post('/admin/football-data/import/team-stats', {
            league_code: selectedLeagueCode.value
        });
        operationStatus.value = response.data;
    } catch (error) {
        console.error('Error importando estadísticas:', error.response || error);
        
        if (error.response && error.response.data) {
            if (error.response.data.errors && error.response.data.errors.league_code) {
                errorMessage.value = 'Error en código de liga: ' + error.response.data.errors.league_code.join(', ');
            } else {
                errorMessage.value = 'Error al importar estadísticas: ' + (error.response.data.message || 'Error desconocido');
            }
        } else {
            errorMessage.value = 'Error al importar estadísticas: ' + (error.message || 'Error desconocido');
        }
    } finally {
        isLoading.value = false;
    }
}

// Importar todo (ligas, equipos, estadísticas)
async function importAll() {
    if (!selectedLeagueCode.value) {
        errorMessage.value = 'Selecciona una liga primero';
        return;
    }
    
    isLoading.value = true;
    operationStatus.value = null;
    errorMessage.value = '';
    
    console.log('Importando todo con league_code:', selectedLeagueCode.value);
    
    try {
        const response = await axios.post('/admin/football-data/import/all', {
            league_code: selectedLeagueCode.value,
            season: selectedSeason.value
        });
        operationStatus.value = response.data;
    } catch (error) {
        console.error('Error importando datos:', error.response || error);
        
        if (error.response && error.response.data) {
            if (error.response.data.errors && error.response.data.errors.league_code) {
                errorMessage.value = 'Error en código de liga: ' + error.response.data.errors.league_code.join(', ');
            } else {
                errorMessage.value = 'Error al importar datos: ' + (error.response.data.message || 'Error desconocido');
            }
        } else {
            errorMessage.value = 'Error al importar datos: ' + (error.message || 'Error desconocido');
        }
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <Head title="Importar Datos de Football-Data.org" />

    <AdminLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="backdrop-blur-sm bg-black/60 border border-zinc-800 overflow-hidden shadow-xl rounded-xl p-6 relative">
                    <!-- Efecto de resplandor en la esquina -->
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-red-500/20 rounded-full blur-3xl opacity-30"></div>
                    
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                        <h1 class="text-3xl font-bold text-white mb-4 md:mb-0 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                            </svg>
                            Importar Datos de Football-Data.org
                        </h1>
                    </div>
                    
                    <!-- Mensaje de error global -->
                    <div v-if="errorMessage" class="mb-6 p-4 bg-red-500/20 border border-red-500/40 rounded-lg">
                        <p class="text-red-300">{{ errorMessage }}</p>
                    </div>
                    
                    <!-- Estado de la operación -->
                    <div v-if="operationStatus" class="mb-6 p-4 bg-green-500/20 border border-green-500/40 rounded-lg">
                        <p class="text-green-300">{{ operationStatus.message }}</p>
                        <div v-if="operationStatus.data" class="mt-2 text-sm text-green-200">
                            <div v-if="operationStatus.data.leagues_count !== undefined">
                                Ligas: {{ operationStatus.data.leagues_count }}
                            </div>
                            <div v-if="operationStatus.data.teams_count !== undefined">
                                Equipos: {{ operationStatus.data.teams_count }}
                            </div>
                            <div v-if="operationStatus.data.stats_count !== undefined">
                                Estadísticas: {{ operationStatus.data.stats_count }}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contadores actuales -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                        <div class="bg-black/30 border border-zinc-800 p-4 rounded-lg">
                            <h2 class="text-lg font-semibold text-white mb-2">Ligas</h2>
                            <div class="text-3xl text-red-500 font-bold">{{ stats.leagues }}</div>
                            <p class="text-sm text-gray-400">Total de ligas en la base de datos</p>
                        </div>
                        
                        <div class="bg-black/30 border border-zinc-800 p-4 rounded-lg">
                            <h2 class="text-lg font-semibold text-white mb-2">Equipos</h2>
                            <div class="text-3xl text-red-500 font-bold">{{ stats.teams }}</div>
                            <p class="text-sm text-gray-400">Total de equipos en la base de datos</p>
                        </div>
                        
                        <div class="bg-black/30 border border-zinc-800 p-4 rounded-lg">
                            <h2 class="text-lg font-semibold text-white mb-2">Estadísticas</h2>
                            <div class="text-3xl text-red-500 font-bold">{{ stats.teamStats }}</div>
                            <p class="text-sm text-gray-400">Total de registros de estadísticas</p>
                        </div>
                    </div>
                    
                    <!-- Formulario de importación -->
                    <div class="bg-black/30 border border-zinc-800 rounded-lg overflow-hidden">
                        <div class="p-5 border-b border-zinc-800">
                            <h2 class="text-xl font-bold text-white">Opciones de Importación</h2>
                            <p class="text-sm text-gray-400 mt-1">Selecciona los datos que deseas importar desde football-data.org</p>
                        </div>
                        
                        <div class="p-5">
                            <!-- Selección de liga -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-300 mb-2">Liga/Competición</label>
                                
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <button 
                                        v-for="league in popularLeagues" 
                                        :key="league.code"
                                        @click="selectedLeagueCode = league.code"
                                        :class="[
                                            'px-4 py-2 text-sm rounded-lg',
                                            selectedLeagueCode === league.code 
                                                ? 'bg-red-500 text-white' 
                                                : 'bg-black/40 text-gray-300 hover:bg-zinc-800'
                                        ]"
                                    >
                                        {{ league.name }}
                                    </button>
                                </div>
                                
                                <!-- Mostrar botón para seleccionar otra liga -->
                                <button
                                    @click="selectedLeagueCode = ''"
                                    :class="[
                                        'px-4 py-2 text-sm rounded-lg mb-4 w-full',
                                        selectedLeagueCode === '' 
                                            ? 'bg-red-500 text-white' 
                                            : 'bg-red-600/20 text-red-300 hover:bg-red-600/30 border border-red-600/30'
                                    ]"
                                >
                                    -- Seleccionar otra liga --
                                </button>
                                
                                <!-- Ligas adicionales -->
                                <div class="flex flex-wrap gap-2">
                                    <button 
                                        v-for="league in leagues" 
                                        :key="league.id"
                                        @click="selectedLeagueCode = getValidLeagueCode(league.api_league_id)"
                                        :class="[
                                            'px-4 py-2 text-sm rounded-lg',
                                            selectedLeagueCode === getValidLeagueCode(league.api_league_id) 
                                                ? 'bg-red-500 text-white' 
                                                : 'bg-black/40 text-gray-300 hover:bg-zinc-800'
                                        ]"
                                    >
                                        {{ league.name }} ({{ league.country }})
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Selección de temporada -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-300 mb-2">Temporada</label>
                                <div class="relative">
                                    <select 
                                        v-model="selectedSeason"
                                        class="block w-full bg-zinc-900 border border-zinc-700 text-white rounded-md py-2 px-3 appearance-none focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:border-red-500"
                                    >
                                        <option 
                                            v-for="season in availableSeasons" 
                                            :key="season" 
                                            :value="season"
                                        >
                                            {{ season }}/{{ season + 1 }}
                                        </option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-5 bg-black/30 border-t border-zinc-800 flex flex-wrap gap-4">
                            <button 
                                @click="importLeagues"
                                :disabled="isLoading"
                                class="px-5 py-2 bg-zinc-800 hover:bg-zinc-700 text-white rounded-md shadow transition-all duration-200 flex items-center disabled:opacity-50 disabled:pointer-events-none"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <span v-if="isLoading && operationStatus === null">Importando ligas...</span>
                                <span v-else>1. Importar Ligas</span>
                            </button>
                            
                            <button 
                                @click="importTeams"
                                :disabled="isLoading || !selectedLeagueCode"
                                class="px-5 py-2 bg-zinc-800 hover:bg-zinc-700 text-white rounded-md shadow transition-all duration-200 flex items-center disabled:opacity-50 disabled:pointer-events-none"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span v-if="isLoading && operationStatus === null">Importando equipos...</span>
                                <span v-else>2. Importar Equipos</span>
                            </button>
                            
                            <button 
                                @click="importTeamStats"
                                :disabled="isLoading || !selectedLeagueCode"
                                class="px-5 py-2 bg-zinc-800 hover:bg-zinc-700 text-white rounded-md shadow transition-all duration-200 flex items-center disabled:opacity-50 disabled:pointer-events-none"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <span v-if="isLoading && operationStatus === null">Importando estadísticas...</span>
                                <span v-else>3. Importar Estadísticas</span>
                            </button>
                            
                            <button 
                                @click="importAll"
                                :disabled="isLoading || !selectedLeagueCode"
                                class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md shadow transition-all duration-200 flex items-center disabled:opacity-50 disabled:pointer-events-none"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span v-if="isLoading && operationStatus === null">Importando todo...</span>
                                <span v-else>Importar Todo</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Consejos y notas -->
                    <div class="mt-8 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                        <h3 class="font-medium text-yellow-300 mb-2">Notas importantes</h3>
                        <ul class="list-disc list-inside text-sm text-gray-300 space-y-1">
                            <li>El proceso de importación puede tardar varios minutos, especialmente para estadísticas.</li>
                            <li>La API de football-data.org tiene límites de uso. No realices importaciones frecuentes.</li>
                            <li>Primero importa las ligas, luego los equipos y finalmente las estadísticas.</li>
                            <li>Para obtener los mejores resultados, selecciona ligas específicas en lugar de importar todo.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template> 