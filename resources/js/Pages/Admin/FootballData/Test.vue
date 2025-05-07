<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import axios from 'axios';

// Estado local
const isLoading = ref(false);
const connectionStatus = ref(null);
const championsTeams = ref([]);
const selectedHomeTeam = ref(null);
const selectedAwayTeam = ref(null);
const teamStats = ref(null);
const matchupStats = ref(null);
const loadingStats = ref(false);
const errorMessage = ref('');

// Probar la conexión con la API
async function testConnection() {
    isLoading.value = true;
    connectionStatus.value = null;
    errorMessage.value = '';
    
    try {
        const response = await axios.get('/football-data/test');
        connectionStatus.value = response.data;
    } catch (error) {
        errorMessage.value = 'Error al conectar con la API: ' + (error.message || 'Error desconocido');
    } finally {
        isLoading.value = false;
    }
}

// Obtener equipos de la Champions League
async function getChampionsTeams() {
    isLoading.value = true;
    championsTeams.value = [];
    errorMessage.value = '';
    
    try {
        const response = await axios.get('/football-data/champions-teams');
        if (response.data.success && response.data.data) {
            championsTeams.value = response.data.data;
        } else {
            errorMessage.value = response.data.message || 'No se pudieron obtener los equipos';
        }
    } catch (error) {
        errorMessage.value = 'Error al obtener equipos: ' + (error.message || 'Error desconocido');
    } finally {
        isLoading.value = false;
    }
}

// Obtener estadísticas de un equipo
async function getTeamStats(teamId) {
    loadingStats.value = true;
    teamStats.value = null;
    errorMessage.value = '';
    
    try {
        const response = await axios.post('/football-data/team-stats', {
            team_id: teamId
        });
        
        if (response.data.success && response.data.data) {
            teamStats.value = response.data.data;
        } else {
            errorMessage.value = response.data.message || 'No se pudieron obtener las estadísticas del equipo';
        }
    } catch (error) {
        errorMessage.value = 'Error al obtener estadísticas: ' + (error.message || 'Error desconocido');
    } finally {
        loadingStats.value = false;
    }
}

// Obtener estadísticas de un enfrentamiento
async function getMatchupStats() {
    if (!selectedHomeTeam.value || !selectedAwayTeam.value) {
        errorMessage.value = 'Debes seleccionar ambos equipos';
        return;
    }
    
    loadingStats.value = true;
    matchupStats.value = null;
    errorMessage.value = '';
    
    try {
        const response = await axios.post('/football-data/matchup-stats', {
            home_team_id: selectedHomeTeam.value.id,
            away_team_id: selectedAwayTeam.value.id
        });
        
        if (response.data.success && response.data.data) {
            matchupStats.value = response.data.data;
        } else {
            errorMessage.value = response.data.message || 'No se pudieron obtener las estadísticas del enfrentamiento';
        }
    } catch (error) {
        errorMessage.value = 'Error al obtener estadísticas: ' + (error.message || 'Error desconocido');
    } finally {
        loadingStats.value = false;
    }
}

// Seleccionar equipo local
function selectHomeTeam(team) {
    selectedHomeTeam.value = team;
    if (selectedHomeTeam.value && selectedHomeTeam.value.id === selectedAwayTeam.value?.id) {
        selectedAwayTeam.value = null;
    }
}

// Seleccionar equipo visitante
function selectAwayTeam(team) {
    selectedAwayTeam.value = team;
    if (selectedAwayTeam.value && selectedAwayTeam.value.id === selectedHomeTeam.value?.id) {
        selectedHomeTeam.value = null;
    }
}

// Ejecutar automáticamente al cargar
onMounted(() => {
    testConnection();
});
</script>

<template>
    <Head title="Test Football-Data API" />

    <AdminLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="backdrop-blur-sm bg-black/60 border border-zinc-800 overflow-hidden shadow-xl rounded-xl p-6 relative">
                    <!-- Efecto de resplandor en la esquina -->
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-blue-500/20 rounded-full blur-3xl opacity-30"></div>
                    
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                        <h1 class="text-3xl font-bold text-white mb-4 md:mb-0 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-4.5-8.5" />
                            </svg>
                            Test Football-Data API
                        </h1>
                    </div>
                    
                    <!-- Mensaje de error global -->
                    <div v-if="errorMessage" class="mb-6 p-4 bg-red-500/20 border border-red-500/40 rounded-lg">
                        <p class="text-red-300">{{ errorMessage }}</p>
                    </div>
                    
                    <!-- Sección de prueba de conexión -->
                    <div class="mb-8 p-4 border border-zinc-700 rounded-lg">
                        <h2 class="text-xl font-bold text-white mb-4">1. Probar Conexión</h2>
                        
                        <div class="mb-4">
                            <button 
                                @click="testConnection" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded shadow transition-all duration-200"
                                :disabled="isLoading"
                            >
                                <span v-if="isLoading">Conectando...</span>
                                <span v-else>Probar conexión</span>
                            </button>
                        </div>
                        
                        <div v-if="connectionStatus" class="mt-2">
                            <div :class="[
                                'p-4 rounded-lg',
                                connectionStatus.success ? 'bg-green-500/20 border border-green-500/30' : 'bg-red-500/20 border border-red-500/30'
                            ]">
                                <p :class="[
                                    'font-medium',
                                    connectionStatus.success ? 'text-green-400' : 'text-red-400'
                                ]">
                                    {{ connectionStatus.message }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de equipos de Champions League -->
                    <div class="mb-8 p-4 border border-zinc-700 rounded-lg">
                        <h2 class="text-xl font-bold text-white mb-4">2. Equipos de Champions League</h2>
                        
                        <div class="mb-4">
                            <button 
                                @click="getChampionsTeams" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded shadow transition-all duration-200"
                                :disabled="isLoading"
                            >
                                <span v-if="isLoading">Cargando equipos...</span>
                                <span v-else>Obtener equipos</span>
                            </button>
                        </div>
                        
                        <div v-if="championsTeams.length > 0" class="mt-4">
                            <h3 class="text-lg font-semibold text-white mb-2">Equipos disponibles ({{ championsTeams.length }})</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                                <div 
                                    v-for="team in championsTeams" 
                                    :key="team.id"
                                    class="backdrop-blur-sm bg-black/40 border border-zinc-800 hover:border-blue-500 rounded-lg overflow-hidden transition-all duration-300 shadow-lg hover:shadow-xl p-4 flex items-center"
                                >
                                    <img 
                                        :src="team.crest" 
                                        :alt="team.name" 
                                        class="h-12 w-12 object-contain mr-3"
                                        onerror="this.src='https://via.placeholder.com/48?text=⚽'; this.onerror=null;"
                                    />
                                    <div>
                                        <h4 class="font-medium text-white">{{ team.name }}</h4>
                                        <p class="text-xs text-gray-400">{{ team.country || 'País no disponible' }}</p>
                                        
                                        <div class="mt-2 flex space-x-2">
                                            <button 
                                                @click="selectHomeTeam(team)"
                                                :class="[
                                                    'text-xs px-2 py-1 rounded',
                                                    selectedHomeTeam && selectedHomeTeam.id === team.id
                                                        ? 'bg-blue-500 text-white'
                                                        : 'bg-black/40 text-gray-300 hover:bg-blue-500/20'
                                                ]"
                                            >
                                                Local
                                            </button>
                                            <button 
                                                @click="selectAwayTeam(team)"
                                                :class="[
                                                    'text-xs px-2 py-1 rounded',
                                                    selectedAwayTeam && selectedAwayTeam.id === team.id
                                                        ? 'bg-blue-500 text-white'
                                                        : 'bg-black/40 text-gray-300 hover:bg-blue-500/20'
                                                ]"
                                            >
                                                Visitante
                                            </button>
                                            <button 
                                                @click="getTeamStats(team.id)"
                                                class="text-xs px-2 py-1 rounded bg-black/40 text-gray-300 hover:bg-green-500/20"
                                            >
                                                Stats
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de enfrentamiento -->
                    <div class="mb-8 p-4 border border-zinc-700 rounded-lg">
                        <h2 class="text-xl font-bold text-white mb-4">3. Estadísticas de Enfrentamiento</h2>
                        
                        <div v-if="selectedHomeTeam || selectedAwayTeam" class="mb-4 flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-4">
                            <div v-if="selectedHomeTeam" class="backdrop-blur-sm bg-black/40 border border-blue-500 rounded-lg p-2 flex items-center">
                                <img 
                                    :src="selectedHomeTeam.crest" 
                                    :alt="selectedHomeTeam.name" 
                                    class="h-10 w-10 mr-2"
                                    onerror="this.src='https://via.placeholder.com/40?text=🏠'; this.onerror=null;"
                                />
                                <span class="text-white">{{ selectedHomeTeam.name }}</span>
                            </div>
                            
                            <div v-if="selectedHomeTeam && selectedAwayTeam" class="text-white">vs</div>
                            
                            <div v-if="selectedAwayTeam" class="backdrop-blur-sm bg-black/40 border border-blue-500 rounded-lg p-2 flex items-center">
                                <img 
                                    :src="selectedAwayTeam.crest" 
                                    :alt="selectedAwayTeam.name" 
                                    class="h-10 w-10 mr-2"
                                    onerror="this.src='https://via.placeholder.com/40?text=🏃'; this.onerror=null;"
                                />
                                <span class="text-white">{{ selectedAwayTeam.name }}</span>
                            </div>
                            
                            <button 
                                v-if="selectedHomeTeam && selectedAwayTeam"
                                @click="getMatchupStats" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded shadow transition-all duration-200"
                                :disabled="loadingStats"
                            >
                                <span v-if="loadingStats">Obteniendo estadísticas...</span>
                                <span v-else>Analizar enfrentamiento</span>
                            </button>
                        </div>
                        
                        <div v-else class="text-gray-400 mb-4">
                            Selecciona un equipo local y un equipo visitante para analizar el enfrentamiento.
                        </div>
                        
                        <!-- Resultados del matchup -->
                        <div v-if="matchupStats" class="mt-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Equipo Local -->
                                <div class="bg-black/30 border border-zinc-800 rounded-lg p-4">
                                    <div class="flex items-center mb-4">
                                        <img 
                                            :src="matchupStats.homeTeam.crest" 
                                            :alt="matchupStats.homeTeam.name" 
                                            class="h-12 w-12 mr-3"
                                            onerror="this.src='https://via.placeholder.com/48?text=🏠'; this.onerror=null;"
                                        />
                                        <div>
                                            <h3 class="text-lg font-bold text-white">{{ matchupStats.homeTeam.name }}</h3>
                                            <p class="text-xs text-gray-400">{{ matchupStats.homeTeam.country }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <div class="text-sm">
                                            <span class="text-gray-400">Fundado:</span> 
                                            <span class="text-white">{{ matchupStats.homeTeam.founded || 'N/A' }}</span>
                                        </div>
                                        
                                        <div class="text-sm">
                                            <span class="text-gray-400">Estadio:</span> 
                                            <span class="text-white">{{ matchupStats.homeTeam.venue || 'N/A' }}</span>
                                        </div>
                                        
                                        <div class="text-sm">
                                            <span class="text-gray-400">Entrenador:</span> 
                                            <span class="text-white">{{ matchupStats.homeTeam.coach?.name || 'N/A' }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Últimos partidos -->
                                    <div v-if="matchupStats.homeTeamMatches.length > 0" class="mt-4">
                                        <h4 class="font-medium text-white mb-2">Últimos partidos</h4>
                                        <div class="space-y-2">
                                            <div 
                                                v-for="(match, index) in matchupStats.homeTeamMatches.slice(0, 5)" 
                                                :key="index"
                                                class="text-xs flex justify-between border-b border-zinc-800 pb-1"
                                            >
                                                <div>
                                                    <span>{{ match.homeTeam.name }}</span>
                                                    <span class="mx-1">{{ match.score.fullTime.home }}-{{ match.score.fullTime.away }}</span>
                                                    <span>{{ match.awayTeam.name }}</span>
                                                </div>
                                                <div class="text-gray-400">
                                                    {{ new Date(match.utcDate).toLocaleDateString() }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Equipo Visitante -->
                                <div class="bg-black/30 border border-zinc-800 rounded-lg p-4">
                                    <div class="flex items-center mb-4">
                                        <img 
                                            :src="matchupStats.awayTeam.crest" 
                                            :alt="matchupStats.awayTeam.name" 
                                            class="h-12 w-12 mr-3"
                                            onerror="this.src='https://via.placeholder.com/48?text=🏃'; this.onerror=null;"
                                        />
                                        <div>
                                            <h3 class="text-lg font-bold text-white">{{ matchupStats.awayTeam.name }}</h3>
                                            <p class="text-xs text-gray-400">{{ matchupStats.awayTeam.country }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <div class="text-sm">
                                            <span class="text-gray-400">Fundado:</span> 
                                            <span class="text-white">{{ matchupStats.awayTeam.founded || 'N/A' }}</span>
                                        </div>
                                        
                                        <div class="text-sm">
                                            <span class="text-gray-400">Estadio:</span> 
                                            <span class="text-white">{{ matchupStats.awayTeam.venue || 'N/A' }}</span>
                                        </div>
                                        
                                        <div class="text-sm">
                                            <span class="text-gray-400">Entrenador:</span> 
                                            <span class="text-white">{{ matchupStats.awayTeam.coach?.name || 'N/A' }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Últimos partidos -->
                                    <div v-if="matchupStats.awayTeamMatches.length > 0" class="mt-4">
                                        <h4 class="font-medium text-white mb-2">Últimos partidos</h4>
                                        <div class="space-y-2">
                                            <div 
                                                v-for="(match, index) in matchupStats.awayTeamMatches.slice(0, 5)" 
                                                :key="index"
                                                class="text-xs flex justify-between border-b border-zinc-800 pb-1"
                                            >
                                                <div>
                                                    <span>{{ match.homeTeam.name }}</span>
                                                    <span class="mx-1">{{ match.score.fullTime.home }}-{{ match.score.fullTime.away }}</span>
                                                    <span>{{ match.awayTeam.name }}</span>
                                                </div>
                                                <div class="text-gray-400">
                                                    {{ new Date(match.utcDate).toLocaleDateString() }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Head-to-Head -->
                                <div v-if="matchupStats.headToHead && matchupStats.headToHead.length > 0" class="col-span-1 md:col-span-2 bg-black/30 border border-zinc-800 rounded-lg p-4">
                                    <h3 class="font-bold text-white mb-2">Enfrentamientos directos</h3>
                                    
                                    <div class="space-y-2">
                                        <div 
                                            v-for="(match, index) in matchupStats.headToHead" 
                                            :key="index"
                                            class="text-sm flex justify-between border-b border-zinc-800 pb-2"
                                        >
                                            <div>
                                                <span class="text-gray-300 mr-1">{{ match.homeTeam.name }}</span>
                                                <span class="text-white mx-1">{{ match.score.fullTime.home }}-{{ match.score.fullTime.away }}</span>
                                                <span class="text-gray-300 ml-1">{{ match.awayTeam.name }}</span>
                                            </div>
                                            <div class="text-gray-400">
                                                {{ new Date(match.utcDate).toLocaleDateString() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-else class="col-span-1 md:col-span-2 bg-black/30 border border-zinc-800 rounded-lg p-4">
                                    <h3 class="font-bold text-white mb-2">Enfrentamientos directos</h3>
                                    <p class="text-gray-400">No se encontraron enfrentamientos directos recientes entre estos equipos.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de estadísticas de un equipo -->
                    <div v-if="teamStats" class="mb-8 p-4 border border-zinc-700 rounded-lg">
                        <h2 class="text-xl font-bold text-white mb-4">4. Estadísticas del Equipo</h2>
                        
                        <div class="bg-black/30 border border-zinc-800 rounded-lg p-4">
                            <div class="flex items-center mb-4">
                                <img 
                                    :src="teamStats.team.crest" 
                                    :alt="teamStats.team.name" 
                                    class="h-16 w-16 mr-4"
                                    onerror="this.src='https://via.placeholder.com/64?text=⚽'; this.onerror=null;"
                                />
                                <div>
                                    <h3 class="text-xl font-bold text-white">{{ teamStats.team.name }}</h3>
                                    <p class="text-sm text-gray-400">{{ teamStats.team.country }} | Fundado: {{ teamStats.team.founded || 'N/A' }}</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <!-- Información general -->
                                <div>
                                    <h4 class="font-medium text-white mb-3 border-b border-zinc-700 pb-2">Información general</h4>
                                    
                                    <div class="space-y-3">
                                        <div class="text-sm">
                                            <span class="text-gray-400">Nombre completo:</span> 
                                            <span class="text-white">{{ teamStats.team.name }}</span>
                                        </div>
                                        
                                        <div class="text-sm">
                                            <span class="text-gray-400">Estadio:</span> 
                                            <span class="text-white">{{ teamStats.team.venue || 'No disponible' }}</span>
                                        </div>
                                        
                                        <div class="text-sm">
                                            <span class="text-gray-400">Entrenador:</span> 
                                            <span class="text-white">{{ teamStats.team.coach?.name || 'No disponible' }}</span>
                                        </div>
                                        
                                        <div class="text-sm">
                                            <span class="text-gray-400">Nacionalidad del entrenador:</span> 
                                            <span class="text-white">{{ teamStats.team.coach?.nationality || 'No disponible' }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Plantilla -->
                                <div v-if="teamStats.team.squad && teamStats.team.squad.length > 0">
                                    <h4 class="font-medium text-white mb-3 border-b border-zinc-700 pb-2">Plantilla ({{ teamStats.team.squad.length }} jugadores)</h4>
                                    
                                    <div class="max-h-60 overflow-y-auto pr-2">
                                        <div 
                                            v-for="(player, index) in teamStats.team.squad" 
                                            :key="index"
                                            class="text-sm border-b border-zinc-800 py-1"
                                        >
                                            <div class="flex justify-between">
                                                <span class="text-white">{{ player.name }}</span>
                                                <span class="text-gray-400">{{ player.position || 'N/A' }}</span>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ player.nationality || 'N/A' }} | {{ player.dateOfBirth ? new Date(player.dateOfBirth).toLocaleDateString() : 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Últimos partidos -->
                                <div v-if="teamStats.matches && teamStats.matches.matches && teamStats.matches.matches.length > 0" class="col-span-1 md:col-span-2">
                                    <h4 class="font-medium text-white mb-3 border-b border-zinc-700 pb-2">Últimos partidos</h4>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div 
                                            v-for="(match, index) in teamStats.matches.matches.slice(0, 8)" 
                                            :key="index"
                                            class="backdrop-blur-sm bg-black/30 border border-zinc-800 rounded-lg p-3"
                                        >
                                            <div class="flex justify-between items-center mb-2">
                                                <span class="text-white text-xs">{{ match.competition.name }}</span>
                                                <span class="text-gray-400 text-xs">{{ new Date(match.utcDate).toLocaleDateString() }}</span>
                                            </div>
                                            
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <img 
                                                        :src="match.homeTeam.crest" 
                                                        :alt="match.homeTeam.name" 
                                                        class="h-6 w-6 mr-2"
                                                        onerror="this.src='https://via.placeholder.com/24?text=🏠'; this.onerror=null;"
                                                    />
                                                    <span class="text-sm text-white">{{ match.homeTeam.name }}</span>
                                                </div>
                                                
                                                <div class="px-3 text-sm font-bold">
                                                    <span class="text-white">{{ match.score.fullTime.home ?? '-' }}</span>
                                                    <span class="text-gray-400 mx-1">-</span>
                                                    <span class="text-white">{{ match.score.fullTime.away ?? '-' }}</span>
                                                </div>
                                                
                                                <div class="flex items-center">
                                                    <span class="text-sm text-white">{{ match.awayTeam.name }}</span>
                                                    <img 
                                                        :src="match.awayTeam.crest" 
                                                        :alt="match.awayTeam.name" 
                                                        class="h-6 w-6 ml-2"
                                                        onerror="this.src='https://via.placeholder.com/24?text=🏃'; this.onerror=null;"
                                                    />
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
        </div>
    </AdminLayout>
</template> 