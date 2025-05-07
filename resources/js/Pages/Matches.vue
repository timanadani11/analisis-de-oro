<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

// Props desde el controlador
const props = defineProps({
    leagues: Array,
    days: Array,
    currentDate: String,
    stats: Object,
});

// Estado local
const selectedFilter = ref('all'); // all, live, upcoming, finished
const isLoadingStats = ref(false);
const isLoadingAnalysis = ref(false);
const statsData = ref(null);
const analysisResults = ref(null);
const showAnalysisModal = ref(false);
const currentMatch = ref(null);
const currentStep = ref(''); // stats, analysis

// Helper para obtener rutas directas
const getDirectRoute = (name, params = {}) => {
    const baseURL = window.location.origin;
    const routes = {
        'matches.by-date': `${baseURL}/matches/date/${params || ''}`,
    };
    return routes[name] ? routes[name].replace('{}', params) : '#';
};

// Partidos filtrados
const filteredLeagues = computed(() => {
    if (selectedFilter.value === 'all') {
        return props.leagues;
    }
    
    return props.leagues.map(league => {
        const statusMap = {
            'live': 'In Progress',
            'upcoming': 'Not Started',
            'finished': 'Match Finished'
        };
        
        const targetStatus = statusMap[selectedFilter.value] || '';
        
        return {
            ...league,
            matches: league.matches.filter(match => match.status === targetStatus)
        };
    }).filter(league => league.matches.length > 0);
});

// Formatear fecha y hora
function formatMatchTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
}

// Obtener clase de estado
function getStatusClass(status) {
    switch (status) {
        case 'Match Finished': return 'bg-green-500/70 text-white';
        case 'In Progress': return 'bg-blue-500/70 text-white animate-pulse';
        case 'Not Started': return 'bg-gray-500/70 text-white';
        case 'Postponed': return 'bg-red-500/70 text-white';
        case 'Suspended': return 'bg-red-500/70 text-white';
        default: return 'bg-gray-500/70 text-white';
    }
}

// Traducir estado
function translateStatus(status) {
    const statusMap = {
        'Match Finished': 'Finalizado',
        'In Progress': 'En juego',
        'Not Started': 'No comenzado',
        'Postponed': 'Pospuesto',
        'Suspended': 'Suspendido'
    };
    
    return statusMap[status] || status;
}

// Obtener estadísticas del partido
function getMatchStats(match) {
    isLoadingStats.value = true;
    currentMatch.value = match;
    statsData.value = null;
    analysisResults.value = null;
    showAnalysisModal.value = true;
    currentStep.value = 'stats';
    
    console.log('Obteniendo estadísticas del partido:', match.home_team.name, 'vs', match.away_team.name);
    
    // Verificar que los datos necesarios estén presentes
    if (!match.home_team.id || !match.away_team.id) {
        console.error('Faltan IDs de equipos:', match);
        statsData.value = { error: 'Faltan IDs de los equipos para obtener estadísticas' };
        isLoadingStats.value = false;
        return;
    }
    
    // Buscar el ID de la liga - podría estar en diferentes lugares
    let leagueId = match.league_id;
    
    // Si no está disponible directamente, intentamos extraerlo de otras propiedades
    if (!leagueId && match.league) {
        if (typeof match.league === 'object' && match.league.id) {
            leagueId = match.league.id;
        } else if (typeof match.league === 'number') {
            leagueId = match.league;
        }
    }
    
    // Si aún no tenemos leagueId, podemos intentar usar un valor por defecto o mostrar error
    if (!leagueId) {
        console.error('No se pudo determinar el ID de la liga:', match);
        statsData.value = { error: 'Falta el ID de la liga para obtener estadísticas' };
        isLoadingStats.value = false;
        return;
    }
    
    const season = new Date().getFullYear(); // Usar año actual como temporada por defecto
    
    console.log('Enviando solicitud de estadísticas con datos:', {
        home_team_id: match.home_team.id,
        away_team_id: match.away_team.id,
        league_id: leagueId,
        season: season
    });
    
    axios.post('/football-api/match-statistics', {
        home_team_id: match.home_team.id,
        away_team_id: match.away_team.id,
        league_id: leagueId,
        season: season
    })
    .then(response => {
        console.log('Respuesta de estadísticas recibida:', response.data);
        
        if (response.data.success) {
            if (!response.data.data) {
                console.error('Respuesta exitosa pero sin datos');
                statsData.value = { error: 'La API devolvió una respuesta vacía' };
                return;
            }
            
            statsData.value = response.data.data;
            console.log('Datos de estadísticas guardados:', statsData.value);
        } else {
            console.error('Error en respuesta de API de estadísticas:', response.data.message);
            statsData.value = { error: response.data.message || 'Error desconocido en la API' };
        }
    })
    .catch(error => {
        console.error('Error en solicitud de estadísticas:', error);
        const errorMessage = error.response?.data?.message || error.message || 'Error en la solicitud de estadísticas';
        statsData.value = { error: errorMessage };
    })
    .finally(() => {
        isLoadingStats.value = false;
    });
}

// Analizar estadísticas ya obtenidas
function analyzeMatchStats() {
    if (!statsData.value || statsData.value.error) {
        console.error('No hay estadísticas para analizar');
        return;
    }
    
    isLoadingAnalysis.value = true;
    currentStep.value = 'analysis';
    
    console.log('Analizando estadísticas:', statsData.value);
    
    // Generar análisis de texto usando el servicio del backend
    axios.post('/analizar-partido', { match_data: statsData.value })
    .then(response => {
        console.log('Respuesta de análisis recibida:', response.data);
        
        if (response.data.success) {
            analysisResults.value = {
                ...statsData.value,
                textAnalysis: response.data.analysis
            };
        } else {
            console.error('Error generando análisis:', response.data.message);
            analysisResults.value = { 
                error: response.data.message || 'Error desconocido al generar análisis' 
            };
        }
    })
    .catch(error => {
        console.error('Error en solicitud de análisis:', error);
        const errorMessage = error.response?.data?.message || error.message || 'Error en la solicitud de análisis';
        analysisResults.value = { error: errorMessage };
    })
    .finally(() => {
        isLoadingAnalysis.value = false;
    });
}

// Cerrar el modal de análisis
function closeAnalysisModal() {
    showAnalysisModal.value = false;
    statsData.value = null;
    analysisResults.value = null;
    currentMatch.value = null;
    currentStep.value = '';
}
</script>

<template>
    <Head title="Partidos de Fútbol" />
    
    <AppLayout>
        <div class="py-4 md:py-6">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
                <!-- Encabezado compacto -->
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-2">
                    <!-- Título -->
                    <div>
                        <h1 class="text-2xl font-bold text-white leading-tight">Partidos de Fútbol</h1>
                        <p class="text-sm text-gray-400">Consulta los partidos programados y resultados recientes</p>
                    </div>
                    
                    <!-- Estadísticas -->
                    <div class="text-right">
                        <div class="text-gray-400 text-sm">{{ new Date(currentDate).toLocaleDateString('es-ES', {weekday: 'short', day: 'numeric', month: 'long', year: 'numeric'}) }}</div>
                        <div class="text-white text-sm font-medium">{{ stats.total }} partidos</div>
                    </div>
                </div>
                
                <!-- Selector de días + Filtros en una misma fila para optimizar espacio -->
                <div class="flex flex-col sm:flex-row gap-3 items-stretch mb-5">
                    <!-- Selector de días -->
                    <div class="overflow-x-auto flex-1 py-1 -mx-1">
                        <div class="flex space-x-1 min-w-max px-1">
                            <a 
                                v-for="day in days" 
                                :key="day.date"
                                :href="getDirectRoute('matches.by-date', day.date)"
                                :class="[
                                    'flex flex-col items-center py-2 px-3 rounded-lg transition-all duration-200 min-w-[60px]',
                                    day.date === currentDate 
                                        ? 'bg-red-500/20 border border-red-500 text-white' 
                                        : 'bg-black/30 border border-zinc-800 text-gray-300 hover:border-red-500/50'
                                ]"
                            >
                                <span class="text-xs uppercase">{{ day.day_name }}</span>
                                <span class="text-lg font-bold">{{ day.day }}</span>
                                <span class="text-xs">{{ day.month }}</span>
                                <div v-if="day.is_today" class="h-1 w-3 bg-red-500 rounded-full mt-1"></div>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Filtros de partidos compactos -->
                    <div class="flex space-x-1 overflow-x-auto py-1 sm:w-auto">
                        <button 
                            @click="selectedFilter = 'all'"
                            :class="[
                                'px-3 py-2 rounded-lg text-xs whitespace-nowrap transition-all duration-200',
                                selectedFilter === 'all' 
                                    ? 'bg-red-500/20 border border-red-500 text-white' 
                                    : 'bg-black/30 border border-zinc-800 text-gray-300 hover:border-red-500/50'
                            ]"
                        >
                            Todos ({{ stats.total }})
                        </button>
                        <button 
                            @click="selectedFilter = 'live'"
                            :class="[
                                'px-3 py-2 rounded-lg text-xs whitespace-nowrap transition-all duration-200',
                                selectedFilter === 'live' 
                                    ? 'bg-blue-500/20 border border-blue-500 text-white' 
                                    : 'bg-black/30 border border-zinc-800 text-gray-300 hover:border-red-500/50'
                            ]"
                        >
                            En vivo ({{ stats.live }})
                        </button>
                        <button 
                            @click="selectedFilter = 'upcoming'"
                            :class="[
                                'px-3 py-2 rounded-lg text-xs whitespace-nowrap transition-all duration-200',
                                selectedFilter === 'upcoming' 
                                    ? 'bg-gray-500/20 border border-gray-500 text-white' 
                                    : 'bg-black/30 border border-zinc-800 text-gray-300 hover:border-red-500/50'
                            ]"
                        >
                            Próximos ({{ stats.upcoming }})
                        </button>
                        <button 
                            @click="selectedFilter = 'finished'"
                            :class="[
                                'px-3 py-2 rounded-lg text-xs whitespace-nowrap transition-all duration-200',
                                selectedFilter === 'finished' 
                                    ? 'bg-green-500/20 border border-green-500 text-white' 
                                    : 'bg-black/30 border border-zinc-800 text-gray-300 hover:border-red-500/50'
                            ]"
                        >
                            Finalizados ({{ stats.finished }})
                        </button>
                    </div>
                </div>
                
                <!-- Sin partidos -->
                <div v-if="filteredLeagues.length === 0" class="flex flex-col items-center justify-center p-6 md:p-10 bg-black/30 border border-zinc-800 rounded-lg">
                    <div class="text-4xl mb-3">⚽</div>
                    <h3 class="text-lg font-semibold text-white mb-2">No hay partidos para mostrar</h3>
                    <p class="text-gray-400 text-center max-w-md text-sm">
                        No hay partidos programados para esta fecha con los filtros seleccionados. 
                        Prueba a seleccionar otra fecha o cambiar los filtros.
                    </p>
                </div>
                
                <!-- Lista de partidos por liga (optimizada) -->
                <div v-else class="space-y-5">
                    <div v-for="league in filteredLeagues" :key="league.name" class="backdrop-blur-sm bg-black/30 border border-zinc-800 rounded-lg overflow-hidden">
                        <!-- Encabezado de la liga compacto -->
                        <div class="px-3 py-2 border-b border-zinc-800 flex items-center bg-black/30">
                            <img 
                                v-if="league.logo" 
                                :src="league.logo" 
                                :alt="league.name" 
                                class="h-5 w-5 mr-2 object-contain"
                                onerror="this.src='https://via.placeholder.com/30?text=🏆'; this.onerror=null;" 
                            />
                            <div>
                                <h3 class="text-md font-medium text-white">{{ league.name }}</h3>
                                <p class="text-xs text-gray-400">{{ league.country }}</p>
                            </div>
                        </div>
                        
                        <!-- Partidos de la liga (diseño más compacto) -->
                        <div class="divide-y divide-zinc-800/50">
                            <div 
                                v-for="match in league.matches" 
                                :key="match.id" 
                                class="px-3 py-3 transition-colors duration-200 hover:bg-black/40"
                            >
                                <!-- Información del partido con diseño más compacto -->
                                <div class="flex flex-col sm:flex-row sm:items-center">
                                    <!-- Estado y hora (más compacto) -->
                                    <div class="flex items-center space-x-2 mb-2 sm:mb-0 sm:w-[100px]">
                                        <div :class="['px-1.5 py-0.5 text-xs rounded text-center min-w-[80px]', getStatusClass(match.status)]">
                                            <span v-if="match.status === 'In Progress'">{{ match.elapsed_time }}′</span>
                                            <span v-else>{{ translateStatus(match.status) }}</span>
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            {{ formatMatchTime(match.date) }}
                                        </div>
                                    </div>
                                    
                                    <!-- Equipos y resultado (layout optimizado) -->
                                    <div class="flex-1 grid grid-cols-7 gap-1 items-center">
                                        <!-- Equipo local -->
                                        <div class="col-span-3 flex items-center justify-end">
                                            <div class="mr-2 text-right">
                                                <div class="text-sm text-white font-medium truncate max-w-[120px]">{{ match.home_team.name }}</div>
                                            </div>
                                            <div class="h-8 w-8 flex-shrink-0">
                                                <img 
                                                    :src="match.home_team.logo" 
                                                    :alt="match.home_team.name" 
                                                    class="h-full w-full object-contain"
                                                    onerror="this.src='https://via.placeholder.com/32?text=🏠'; this.onerror=null;"
                                                />
                                            </div>
                                        </div>
                                        
                                        <!-- Resultado (más compacto) -->
                                        <div class="col-span-1 text-center">
                                            <div 
                                                class="inline-flex items-center justify-center px-2 py-1 bg-black/50 border border-zinc-700 rounded text-sm"
                                                :class="{'animate-pulse': match.status === 'In Progress'}"    
                                            >
                                                <span class="font-bold text-white">{{ match.score.home }}</span>
                                                <span class="text-gray-500 mx-1">-</span>
                                                <span class="font-bold text-white">{{ match.score.away }}</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Equipo visitante -->
                                        <div class="col-span-3 flex items-center text-left">
                                            <div class="h-8 w-8 flex-shrink-0">
                                                <img 
                                                    :src="match.away_team.logo" 
                                                    :alt="match.away_team.name" 
                                                    class="h-full w-full object-contain"
                                                    onerror="this.src='https://via.placeholder.com/32?text=🏠'; this.onerror=null;"
                                                />
                                            </div>
                                            <div class="ml-2">
                                                <div class="text-sm text-white font-medium truncate max-w-[120px]">{{ match.away_team.name }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Sede del partido (más compacto) -->
                                <div v-if="match.venue" class="mt-1 text-xs text-gray-500 text-center">
                                    🏟️ {{ match.venue }}
                                </div>
                                
                                <!-- Botón de análisis -->
                                <div class="mt-2 flex justify-center">
                                    <button 
                                        @click="getMatchStats(match)"
                                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded-md transition-colors duration-200 flex items-center"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        Obtener estadísticas
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
    
    <!-- Modal de análisis -->
    <div v-if="showAnalysisModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div class="bg-zinc-900 border border-zinc-700 rounded-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto">
            <!-- Cabecera del modal -->
            <div class="p-4 border-b border-zinc-700 flex justify-between items-center">
                <h3 class="text-xl font-bold text-white" v-if="currentMatch">
                    Análisis: {{ currentMatch.home_team.name }} vs {{ currentMatch.away_team.name }}
                </h3>
                <button @click="closeAnalysisModal" class="text-gray-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <!-- Contenido del modal -->
            <div class="p-4">
                <!-- Estado de carga -->
                <div v-if="isLoadingStats || isLoadingAnalysis" class="flex flex-col items-center justify-center py-8">
                    <div v-if="isLoadingStats" class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-red-500 mb-4"></div>
                    <div v-else-if="isLoadingAnalysis" class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500 mb-4"></div>
                    <p v-if="isLoadingStats" class="text-gray-300">Obteniendo estadísticas de los equipos...</p>
                    <p v-else-if="isLoadingAnalysis" class="text-gray-300">Generando análisis del partido...</p>
                </div>
                
                <!-- Error en estadísticas -->
                <div v-else-if="statsData && statsData.error" class="bg-red-900/30 border border-red-700/50 rounded-lg p-4 text-white">
                    <h4 class="font-semibold mb-2">Error al obtener estadísticas</h4>
                    <p>{{ statsData.error }}</p>
                    
                    <div class="mt-4 border-t border-red-700/30 pt-4">
                        <h5 class="font-medium mb-2">Posibles soluciones:</h5>
                        <ul class="list-disc pl-5 text-sm">
                            <li class="mb-1">Los datos del equipo pueden no estar disponibles en la API de fútbol</li>
                            <li class="mb-1">La temporada actual puede no tener estadísticas aún para estos equipos</li>
                            <li class="mb-1">Puede haber un problema de conexión con el servicio de la API</li>
                            <li class="mb-1">La liga de este partido puede no estar soportada para análisis</li>
                        </ul>
                        
                        <div class="mt-4 flex justify-center">
                            <button 
                                @click="closeAnalysisModal" 
                                class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-white rounded-md"
                            >
                                Cerrar y volver
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Error en análisis -->
                <div v-else-if="analysisResults && analysisResults.error" class="bg-red-900/30 border border-red-700/50 rounded-lg p-4 text-white">
                    <h4 class="font-semibold mb-2">Error al generar análisis</h4>
                    <p>{{ analysisResults.error }}</p>
                    
                    <div class="mt-4 border-t border-red-700/30 pt-4">
                        <h5 class="font-medium mb-2">Posibles soluciones:</h5>
                        <ul class="list-disc pl-5 text-sm">
                            <li class="mb-1">Las estadísticas obtenidas pueden ser insuficientes para el análisis</li>
                            <li class="mb-1">Puede haber un problema con el servicio de análisis</li>
                            <li class="mb-1">Intente volver a generar el análisis</li>
                        </ul>
                        
                        <div class="mt-4 flex justify-center space-x-4">
                            <button 
                                @click="currentStep = 'stats'" 
                                class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-white rounded-md"
                            >
                                Volver a estadísticas
                            </button>
                            <button 
                                @click="closeAnalysisModal" 
                                class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-white rounded-md"
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Paso 1: Mostrar estadísticas cuando las tenemos -->
                <div v-else-if="statsData && !analysisResults && currentStep === 'stats'" class="space-y-4">
                    <div class="bg-green-900/20 border border-green-700/30 rounded-lg p-3 mb-4">
                        <p class="text-green-400 text-sm"><span class="font-semibold">Paso 1 completado:</span> Estadísticas de equipos obtenidas correctamente.</p>
                        <p class="text-white text-xs mt-1">Ahora puede generar el análisis basado en estos datos estadísticos.</p>
                    </div>
                    
                    <!-- Información general -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center">
                            <img :src="statsData.homeTeam?.team?.logo" class="h-16 w-16 mx-auto mb-2" />
                            <h4 class="text-white font-semibold">{{ statsData.homeTeam?.team?.name }}</h4>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-white">VS</div>
                            <div class="text-sm text-gray-400">{{ statsData.homeTeam?.league?.name }}</div>
                        </div>
                        <div class="text-center">
                            <img :src="statsData.awayTeam?.team?.logo" class="h-16 w-16 mx-auto mb-2" />
                            <h4 class="text-white font-semibold">{{ statsData.awayTeam?.team?.name }}</h4>
                        </div>
                    </div>
                    
                    <!-- Estadísticas básicas -->
                    <div class="bg-black/30 rounded-lg p-4 border border-zinc-700">
                        <h4 class="text-lg font-semibold text-white mb-4">Estadísticas obtenidas</h4>
                        
                        <!-- Datos estadísticos generales -->
                        <div class="grid grid-cols-3 gap-2 text-sm mb-4">
                            <div class="text-right">
                                <div class="font-semibold text-white">{{ statsData.homeTeam?.form || '-' }}</div>
                                <div class="text-xs text-gray-400">Forma reciente</div>
                            </div>
                            <div class="text-center text-gray-500">VS</div>
                            <div class="text-left">
                                <div class="font-semibold text-white">{{ statsData.awayTeam?.form || '-' }}</div>
                                <div class="text-xs text-gray-400">Forma reciente</div>
                            </div>
                            
                            <!-- Más estadísticas -->
                            <div class="text-right">
                                <div class="font-semibold text-white">{{ statsData.homeTeam?.fixtures?.wins?.total || '0' }}</div>
                                <div class="text-xs text-gray-400">Victorias</div>
                            </div>
                            <div class="text-center text-gray-500">-</div>
                            <div class="text-left">
                                <div class="font-semibold text-white">{{ statsData.awayTeam?.fixtures?.wins?.total || '0' }}</div>
                                <div class="text-xs text-gray-400">Victorias</div>
                            </div>
                            
                            <!-- Goles -->
                            <div class="text-right">
                                <div class="font-semibold text-white">{{ statsData.homeTeam?.goals?.for?.total?.total || '0' }}</div>
                                <div class="text-xs text-gray-400">Goles a favor</div>
                            </div>
                            <div class="text-center text-gray-500">-</div>
                            <div class="text-left">
                                <div class="font-semibold text-white">{{ statsData.awayTeam?.goals?.for?.total?.total || '0' }}</div>
                                <div class="text-xs text-gray-400">Goles a favor</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Enfrentamientos directos -->
                    <div v-if="statsData.headToHead && statsData.headToHead.length > 0" class="bg-black/30 rounded-lg p-4 border border-zinc-700">
                        <h4 class="text-lg font-semibold text-white mb-3">Últimos enfrentamientos directos</h4>
                        <div class="space-y-2 text-sm">
                            <div v-for="(match, index) in statsData.headToHead.slice(0, 3)" :key="index" class="grid grid-cols-3 items-center py-2 border-b border-zinc-800">
                                <div class="text-right text-white">{{ match.teams.home.name }}</div>
                                <div class="text-center font-semibold text-white">{{ match.goals.home }} - {{ match.goals.away }}</div>
                                <div class="text-left text-white">{{ match.teams.away.name }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botón para generar análisis -->
                    <div class="flex justify-center mt-6">
                        <button 
                            @click="analyzeMatchStats()"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors duration-200 flex items-center"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Generar Análisis del Partido
                        </button>
                    </div>
                </div>
                
                <!-- Paso 2: Resultados del análisis -->
                <div v-else-if="analysisResults && currentStep === 'analysis'" class="space-y-4">
                    <div class="bg-blue-900/20 border border-blue-700/30 rounded-lg p-3 mb-4">
                        <p class="text-blue-400 text-sm"><span class="font-semibold">Paso 2 completado:</span> Análisis del partido generado.</p>
                        <p class="text-white text-xs mt-1">El análisis se ha creado basado en datos estadísticos reales de los equipos.</p>
                    </div>
                    
                    <!-- Información general -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center">
                            <img :src="analysisResults.homeTeam?.team?.logo" class="h-16 w-16 mx-auto mb-2" />
                            <h4 class="text-white font-semibold">{{ analysisResults.homeTeam?.team?.name }}</h4>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-white">VS</div>
                            <div class="text-sm text-gray-400">{{ analysisResults.homeTeam?.league?.name }}</div>
                        </div>
                        <div class="text-center">
                            <img :src="analysisResults.awayTeam?.team?.logo" class="h-16 w-16 mx-auto mb-2" />
                            <h4 class="text-white font-semibold">{{ analysisResults.awayTeam?.team?.name }}</h4>
                        </div>
                    </div>
                    
                    <!-- Análisis de texto (si está disponible) -->
                    <div v-if="analysisResults.textAnalysis" class="bg-black/30 rounded-lg p-4 border border-zinc-700">
                        <div class="prose prose-invert max-w-none text-sm" v-html="analysisResults.textAnalysis"></div>
                    </div>
                    
                    <!-- Botones de navegación -->
                    <div class="flex justify-center space-x-4 mt-6">
                        <button 
                            @click="currentStep = 'stats'"
                            class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 text-white rounded transition-colors duration-200"
                        >
                            Volver a estadísticas
                        </button>
                        <button 
                            @click="closeAnalysisModal()"
                            class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-white rounded transition-colors duration-200"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
                
                <!-- Estado inicial o no válido -->
                <div v-else class="flex flex-col items-center justify-center py-8">
                    <div class="text-4xl mb-4">⚽</div>
                    <p class="text-gray-300 text-center">No hay datos disponibles. Cierre y vuelva a intentarlo.</p>
                    
                    <button 
                        @click="closeAnalysisModal" 
                        class="mt-4 px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-white rounded-md"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

/* Para asegurar que nombres largos se truncan correctamente */
.truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Estilo para análisis de texto con Markdown */
.prose h1, .prose h2, .prose h3 {
    color: white;
    margin-top: 1.5em;
    margin-bottom: 0.5em;
}

.prose h1 {
    font-size: 1.5rem;
}

.prose h2 {
    font-size: 1.25rem;
}

.prose h3 {
    font-size: 1.1rem;
}

.prose p {
    margin-bottom: 0.75em;
}

.prose ul {
    list-style-type: disc;
    padding-left: 1.5em;
    margin-bottom: 1em;
}

.prose table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1em;
}

.prose th, .prose td {
    border: 1px solid #444;
    padding: 0.5em;
    text-align: left;
}

.prose th {
    background-color: rgba(0, 0, 0, 0.3);
}
</style> 