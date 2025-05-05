<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';

// Props recibidos del controlador
const props = defineProps({
    matches: Object,
    error: String
});

// Estado local
const isLoading = ref(false);
const searchQuery = ref('');
const selectedLeague = ref('');
const selectedStatus = ref('');
const currentTab = ref('all');
const showModal = ref(false);
const analisis = ref('');
const loadingAnalisis = ref(false);
const partidoSeleccionado = ref(null);

// Lista de estados posibles para filtro
const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'Not Started', label: 'No iniciado' },
    { value: 'Match Finished', label: 'Finalizado' },
    { value: 'In Progress', label: 'En juego' },
    { value: 'Suspended', label: 'Suspendido' },
    { value: 'Postponed', label: 'Pospuesto' }
];

// Obtener ligas únicas para filtro
const leagueOptions = computed(() => {
    const leagues = new Set();
    
    if (props.matches && props.matches.data) {
        props.matches.data.forEach(match => {
            leagues.add(match.league_name);
        });
    }
    
    const result = Array.from(leagues).map(league => ({
        value: league,
        label: league
    }));
    
    return [{ value: '', label: 'Todas las ligas' }, ...result];
});

// Partidos filtrados
const filteredMatches = computed(() => {
    if (!props.matches || !props.matches.data) return [];
    
    return props.matches.data.filter(match => {
        // Filtrar por búsqueda
        const searchLower = searchQuery.value.toLowerCase();
        const matchesSearch = 
            searchLower === '' || 
            match.home_team_name.toLowerCase().includes(searchLower) ||
            match.away_team_name.toLowerCase().includes(searchLower) ||
            match.league_name.toLowerCase().includes(searchLower);
        
        // Filtrar por liga
        const matchesLeague = 
            selectedLeague.value === '' || 
            match.league_name === selectedLeague.value;
        
        // Filtrar por estado
        const matchesStatus = 
            selectedStatus.value === '' || 
            match.status === selectedStatus.value;
        
        // Filtrar por pestaña actual
        if (currentTab.value === 'today') {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const matchDate = new Date(match.match_date);
            matchDate.setHours(0, 0, 0, 0);
            
            return matchesSearch && matchesLeague && matchesStatus && 
                   today.getTime() === matchDate.getTime();
        } else if (currentTab.value === 'live') {
            return matchesSearch && matchesLeague && matchesStatus && 
                   match.status === 'In Progress';
        } else if (currentTab.value === 'finished') {
            return matchesSearch && matchesLeague && matchesStatus && 
                   match.status === 'Match Finished';
        }
        
        return matchesSearch && matchesLeague && matchesStatus;
    });
});

// Formatear fecha
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { 
        year: 'numeric',
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Intl.DateTimeFormat('es-ES', options).format(date);
}

// Determinar el color de fondo según el estado del partido
function getStatusClass(status) {
    switch(status) {
        case 'Match Finished':
            return 'bg-green-500/20 text-green-400 border-green-500/30';
        case 'In Progress':
            return 'bg-blue-500/20 text-blue-400 border-blue-500/30 animate-pulse';
        case 'Not Started':
            return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
        case 'Suspended':
        case 'Postponed':
            return 'bg-red-500/20 text-red-400 border-red-500/30';
        default:
            return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
    }
}

// Obtener clase de color para el tiempo transcurrido
function getElapsedTimeClass(status) {
    if (status === 'In Progress') {
        return 'text-blue-400';
    } else if (status === 'Match Finished') {
        return 'text-green-400';
    }
    return 'text-gray-400';
}

async function analizarPartido(match) {
    showModal.value = true;
    loadingAnalisis.value = true;
    analisis.value = '';
    partidoSeleccionado.value = match;
    try {
        const response = await axios.post('/analizar-partido', {
            local: match.home_team_name,
            visitante: match.away_team_name,
            fecha: match.match_date
        });
        analisis.value = response.data?.candidates?.[0]?.content?.parts?.[0]?.text || 'No se pudo obtener el análisis.';
    } catch (e) {
        analisis.value = 'Error al analizar el partido.';
    } finally {
        loadingAnalisis.value = false;
    }
}
</script>

<template>
    <Head title="Partidos de Fútbol" />

    <AdminLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="backdrop-blur-sm bg-black/60 border border-zinc-800 overflow-hidden shadow-xl rounded-xl p-6 relative">
                    <!-- Efecto de resplandor en la esquina -->
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-red-500/20 rounded-full blur-3xl opacity-30"></div>
                    
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                        <h1 class="text-3xl font-bold text-white mb-4 md:mb-0 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Partidos de Fútbol
                        </h1>
                    </div>
                    
                    <!-- Mensaje de error si lo hay -->
                    <div v-if="error" class="mb-6 p-4 bg-red-500/20 border border-red-500/40 rounded-lg">
                        <p class="text-red-300">{{ error }}</p>
                    </div>
                    
                    <!-- Filtros y búsqueda -->
                    <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="col-span-1">
                            <input 
                                type="text" 
                                v-model="searchQuery" 
                                placeholder="Buscar partidos..." 
                                class="w-full px-4 py-2 bg-black/50 border border-zinc-800 rounded-lg text-white placeholder-gray-500 focus:border-red-500 focus:outline-none"
                            />
                        </div>
                        <div class="col-span-1">
                            <select 
                                v-model="selectedLeague" 
                                class="w-full px-4 py-2 bg-black/50 border border-zinc-800 rounded-lg text-white focus:border-red-500 focus:outline-none"
                            >
                                <option v-for="option in leagueOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                        <div class="col-span-1">
                            <select 
                                v-model="selectedStatus" 
                                class="w-full px-4 py-2 bg-black/50 border border-zinc-800 rounded-lg text-white focus:border-red-500 focus:outline-none"
                            >
                                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                        <div class="col-span-1 flex items-center justify-end">
                            <span class="text-gray-400 mr-2">{{ filteredMatches.length }} partidos</span>
                        </div>
                    </div>
                    
                    <!-- Pestañas de filtrado rápido -->
                    <div class="mb-6 flex space-x-2 border-b border-zinc-800 pb-2">
                        <button 
                            @click="currentTab = 'all'" 
                            :class="['px-4 py-2 rounded-t-lg font-medium transition-colors duration-200', 
                                    currentTab === 'all' ? 'text-white border-b-2 border-red-500' : 'text-gray-400 hover:text-white']"
                        >
                            Todos
                        </button>
                        <button 
                            @click="currentTab = 'today'" 
                            :class="['px-4 py-2 rounded-t-lg font-medium transition-colors duration-200', 
                                    currentTab === 'today' ? 'text-white border-b-2 border-red-500' : 'text-gray-400 hover:text-white']"
                        >
                            Hoy
                        </button>
                        <button 
                            @click="currentTab = 'live'" 
                            :class="['px-4 py-2 rounded-t-lg font-medium transition-colors duration-200', 
                                    currentTab === 'live' ? 'text-white border-b-2 border-red-500' : 'text-gray-400 hover:text-white']"
                        >
                            En vivo
                        </button>
                        <button 
                            @click="currentTab = 'finished'" 
                            :class="['px-4 py-2 rounded-t-lg font-medium transition-colors duration-200', 
                                    currentTab === 'finished' ? 'text-white border-b-2 border-red-500' : 'text-gray-400 hover:text-white']"
                        >
                            Finalizados
                        </button>
                    </div>
                    
                    <!-- Cargando... -->
                    <div v-if="isLoading" class="flex justify-center items-center py-20">
                        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-red-500"></div>
                    </div>
                    
                    <!-- Sin resultados -->
                    <div v-else-if="filteredMatches.length === 0" class="py-20 text-center">
                        <div class="text-5xl text-gray-600 mb-4">⚽</div>
                        <h3 class="text-xl font-semibold text-gray-400">No hay partidos que coincidan con tu búsqueda</h3>
                        <p class="text-gray-500 mt-2">Intenta con otros filtros o verifica la conexión con la API.</p>
                    </div>
                    
                    <!-- Lista de partidos -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div 
                            v-for="match in filteredMatches" 
                            :key="match.id" 
                            class="backdrop-blur-sm bg-black/40 border border-zinc-800 hover:border-red-500 rounded-lg overflow-hidden transition-all duration-300 shadow-lg hover:shadow-xl group"
                        >
                            <!-- Cabecera con liga -->
                            <div class="px-4 py-3 flex items-center border-b border-zinc-800 bg-black/30">
                                <img 
                                    :src="match.league_logo" 
                                    alt="Liga" 
                                    class="h-6 w-6 object-contain mr-2"
                                    onerror="this.src='https://via.placeholder.com/30?text=🏆'; this.onerror=null;"
                                />
                                <div class="flex-1">
                                    <h3 class="font-medium text-white truncate">{{ match.league_name }}</h3>
                                    <p class="text-xs text-gray-400">{{ match.league_country }}</p>
                                </div>
                                <div 
                                    :class="['text-xs px-2 py-1 rounded border', getStatusClass(match.status)]"
                                >
                                    {{ match.status }}
                                </div>
                            </div>
                            
                            <!-- Cuerpo con equipos y resultado -->
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="text-xs text-gray-400">{{ formatDate(match.match_date) }}</div>
                                    <div 
                                        v-if="match.status === 'In Progress'" 
                                        :class="['text-xs font-bold py-1 px-2 rounded-full', getElapsedTimeClass(match.status)]"
                                    >
                                        {{ match.elapsed_time }}' min
                                    </div>
                                </div>
                                
                                <!-- Equipos y resultado -->
                                <div class="flex items-center justify-between">
                                    <!-- Equipo local -->
                                    <div class="flex flex-col items-center w-2/5">
                                        <div class="h-16 w-16 bg-black/40 rounded-full p-2 flex items-center justify-center mb-2 border border-zinc-800">
                                            <img 
                                                :src="match.home_team_logo" 
                                                :alt="match.home_team_name" 
                                                class="max-h-12 max-w-12 object-contain"
                                                onerror="this.src='https://via.placeholder.com/70?text=🏠'; this.onerror=null;"
                                            />
                                        </div>
                                        <h3 class="text-sm font-medium text-white text-center">{{ match.home_team_name }}</h3>
                                    </div>
                                    
                                    <!-- Marcador -->
                                    <div class="flex flex-col items-center justify-center w-1/5">
                                        <div class="flex items-center justify-center">
                                            <div class="text-2xl font-bold text-white">{{ match.home_goals }}</div>
                                            <div class="mx-2 text-gray-500">-</div>
                                            <div class="text-2xl font-bold text-white">{{ match.away_goals }}</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Equipo visitante -->
                                    <div class="flex flex-col items-center w-2/5">
                                        <div class="h-16 w-16 bg-black/40 rounded-full p-2 flex items-center justify-center mb-2 border border-zinc-800">
                                            <img 
                                                :src="match.away_team_logo" 
                                                :alt="match.away_team_name" 
                                                class="max-h-12 max-w-12 object-contain"
                                                onerror="this.src='https://via.placeholder.com/70?text=🏃'; this.onerror=null;"
                                            />
                                        </div>
                                        <h3 class="text-sm font-medium text-white text-center">{{ match.away_team_name }}</h3>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-center">
                                    <button @click="analizarPartido(match)" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded shadow transition-all duration-200">
                                        Analizar partido
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Pie con detalles -->
                            <div class="px-4 py-3 border-t border-zinc-800 bg-black/30">
                                <div class="flex justify-between items-center">
                                    <div class="text-xs text-gray-400">
                                        <span v-if="match.venue">🏟️ {{ match.venue }}</span>
                                        <span v-else>Sin sede asignada</span>
                                    </div>
                                    <div 
                                        class="text-xs bg-black/40 px-2 py-1 rounded-full text-gray-400 border border-zinc-800 group-hover:border-red-500 transition-colors duration-200"
                                    >
                                        Ver detalles
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Paginación -->
                    <div v-if="props.matches && props.matches.links && props.matches.links.length > 3" class="mt-6">
                        <div class="flex justify-center">
                            <div v-for="(link, index) in props.matches.links" :key="index" class="mx-1">
                                <a
                                    v-if="link.url"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="[
                                        'px-4 py-2 rounded border',
                                        link.active 
                                            ? 'bg-red-500/20 border-red-500 text-white' 
                                            : 'border-zinc-800 text-gray-400 hover:text-white hover:border-red-500'
                                    ]"
                                ></a>
                                <span 
                                    v-else
                                    v-html="link.label" 
                                    class="px-4 py-2 text-gray-600"
                                ></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
    <Modal :show="showModal" @close="showModal = false">
        <div class="p-6">
            <h2 class="text-xl font-bold mb-4 text-white">Análisis del partido</h2>
            <div v-if="partidoSeleccionado" class="mb-2 text-gray-300">
                <span class="font-semibold">{{ partidoSeleccionado.home_team_name }}</span>
                vs
                <span class="font-semibold">{{ partidoSeleccionado.away_team_name }}</span>
                <span class="block text-xs text-gray-400">{{ formatDate(partidoSeleccionado.match_date) }}</span>
            </div>
            <div v-if="loadingAnalisis" class="text-gray-400 flex items-center"><span class="animate-spin mr-2">⏳</span> Analizando partido...</div>
            <div v-else class="whitespace-pre-line text-gray-200">{{ analisis }}</div>
            <div class="mt-6 text-right">
                <button @click="showModal = false" class="px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded">Cerrar</button>
            </div>
        </div>
    </Modal>
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
</style>
