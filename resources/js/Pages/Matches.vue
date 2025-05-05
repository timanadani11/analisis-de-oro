<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// Props desde el controlador
const props = defineProps({
    leagues: Array,
    days: Array,
    currentDate: String,
    stats: Object,
});

// Helper para obtener rutas directas
const getDirectRoute = (name, params = {}) => {
    const baseURL = window.location.origin;
    const routes = {
        'matches.by-date': `${baseURL}/matches/date/${params || ''}`,
    };
    return routes[name] ? routes[name].replace('{}', params) : '#';
};

// Estado local
const selectedFilter = ref('all'); // all, live, upcoming, finished

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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
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
</style> 