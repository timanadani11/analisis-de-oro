<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';

// Props recibidos del controlador
const props = defineProps({
    matches: Object,
    error: String,
    filters: Object, // Filters applied on the backend
    leaguesForFilter: Array, // List of leagues for the filter dropdown
});

// Estado local
const isLoading = ref(false);
const selectedLeague = ref(props.filters?.league_name || '');
const selectedStatus = ref(props.filters?.status || '');
const refreshingData = ref(false); // Nueva variable para el estado de actualización
const selectedDate = ref(props.filters?.selected_date || new Date().toISOString().split('T')[0]); // Por defecto es hoy

// Determine initial tab based on filters
const determineInitialTab = () => {
    if (props.filters?.date_filter === 'today') return 'today';
    if (props.filters?.status === 'In Progress') return 'live'; // Assuming 'In Progress' is the value for live
    if (props.filters?.status === 'Match Finished') return 'finished'; // Assuming 'Match Finished' is the value
    return 'all';
};
const currentTab = ref(determineInitialTab());

const showModal = ref(false);
const analisis = ref('');
const loadingAnalisis = ref(false);
const partidoSeleccionado = ref(null);

// Nuevas variables para estadísticas de equipos
const showStatsModal = ref(false);
const equiposStats = ref(null);
const loadingStats = ref(false);

// Lista de estados posibles para filtro
const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'scheduled', label: 'Programado' },
    { value: 'live', label: 'En vivo' },
    { value: 'halftime', label: 'Entretiempo' },
    { value: 'finished', label: 'Finalizado' },
    { value: 'postponed', label: 'Pospuesto' },
    { value: 'canceled', label: 'Cancelado' },
    { value: 'suspended', label: 'Suspendido' },
    { value: 'not started', label: 'No iniciado' },
    { value: 'abandoned', label: 'Abandonado' },
    { value: 'interrupted', label: 'Interrumpido' }
];

// Obtener ligas únicas para filtro
const leagueOptions = computed(() => {
    if (!props.leaguesForFilter) return [{ value: '', label: 'Todas las ligas' }];
    const options = props.leaguesForFilter.map(league => ({
        value: league.name, // Filter by name as per controller
        label: `${league.name} (${league.country?.name || 'N/A'})`
    }));
    return [{ value: '', label: 'Todas las ligas' }, ...options];
});

// Partidos filtrados directamente desde props
const filteredMatches = computed(() => {
    if (!props.matches || !props.matches.data) return [];
    return props.matches.data.filter(match => {
        // Filtrar por liga
        const matchesLeague = 
            selectedLeague.value === '' || 
            match.league_name === selectedLeague.value;
        
        // Filtrar por estado
        const matchesStatus = 
            selectedStatus.value === '' || 
            (match.status || '').toLowerCase() === selectedStatus.value.toLowerCase();
            
        return matchesLeague && matchesStatus;
    });
});

// Agrupar partidos por liga
const groupedMatches = computed(() => {
    const matches = filteredMatches.value;
    const grouped = {};
    
    // Ordenar primero por hora de inicio
    const sortedMatches = [...matches].sort((a, b) => {
        const dateA = new Date(a.match_date || 0);
        const dateB = new Date(b.match_date || 0);
        return dateA - dateB;
    });
    
    // Agrupar por liga
    for (const match of sortedMatches) {
        const leagueName = match.league_name || 'Sin liga';
        if (!grouped[leagueName]) {
            grouped[leagueName] = [];
        }
        grouped[leagueName].push(match);
    }
    
    return grouped;
});

// Watch for filter changes and make Inertia requests
watch([selectedLeague, selectedStatus], () => {
    const queryParams = {};

    if (selectedLeague.value) queryParams.league_name = selectedLeague.value;
    if (selectedStatus.value) queryParams.status = selectedStatus.value;
    if (selectedDate.value) queryParams.selected_date = selectedDate.value;
    
    router.get(route('admin.matches'), queryParams, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, { deep: true });

// Formatear fecha
function formatDate(dateString) {
    if (!dateString) return 'Fecha no disponible';
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

// Formatear fecha corta para mostrar en partidos
function formatShortDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES');
}

// Formatear hora corta para mostrar en partidos
function formatShortTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit'});
}

// Determinar el color de fondo según el estado del partido
function getStatusClass(status) {
    if (!status) return 'bg-gray-600/20 text-gray-300 border-gray-600/30'; // Default para null o undefined
    
    // Convertir a minúscula para hacer la comparación más flexible
    const s = status.toLowerCase();
    
    // Estados de finalizado
    if (s.includes('finish') || s === 'ft' || s === 'aet' || s === 'pen' || s === 'complete' || s === 'completed') {
        return 'bg-green-500/20 text-green-400 border-green-500/30';
    }
    
    // Estados en vivo
    if (s.includes('live') || s.includes('progress') || s === '1h' || s === '2h' || s === 'et' || s === 'bt' || s === 'in_play') {
        return 'bg-blue-500/20 text-blue-400 border-blue-500/30 animate-pulse';
    }
    
    // Estados programados
    if (s.includes('schedule') || s.includes('not start') || s === 'tbd' || s === 'ns') {
        return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
    }
    
    // Estados pospuestos
    if (s.includes('post') || s === 'pst') {
        return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
    }
    
    // Estados suspendidos
    if (s.includes('susp') || s.includes('interrupt') || s === 'int') {
        return 'bg-orange-500/20 text-orange-400 border-orange-500/30';
    }
    
    // Estados cancelados
    if (s.includes('canc') || s.includes('aband') || s === 'abd') {
        return 'bg-red-500/20 text-red-400 border-red-500/30';
    }
    
    // Estados medio tiempo
    if (s.includes('half') || s === 'ht') {
         return 'bg-indigo-500/20 text-indigo-400 border-indigo-500/30';
    }
    
    // Default para estados no reconocidos
    return 'bg-gray-600/20 text-gray-300 border-gray-600/30';
}

// Obtener clase de color para el tiempo transcurrido
function getElapsedTimeClass(status) {
    const s = status?.toLowerCase();
    if (s === 'live' || s === 'in progress' || s === '1h' || s === '2h' || s === 'et' || s === 'bt') {
        return 'text-blue-400';
    } else if (s === 'finished') {
        return 'text-green-400';
    }
    return 'text-gray-400';
}

// Función para formatear el texto con saltos de línea y elementos markdown
function formatText(text) {
    if (!text) return '';
    
    // Reemplazar saltos de línea con <br>
    let formattedText = text.replace(/\n/g, '<br>');
    
    // Formatear encabezados con estilos
    formattedText = formattedText.replace(/---+/g, '<hr class="border-zinc-700 my-3">');
    formattedText = formattedText.replace(/(ANÁLISIS |RESUMEN |PRONÓSTICO |RECOMENDACIONES |ESTADÍSTICAS |DATOS |FACTORES |MERCADOS )[^<]+/g, 
        '<h3 class="text-xl font-bold text-red-400 mt-4 mb-2">$&</h3>');
    
    // Resaltar números porcentuales
    formattedText = formattedText.replace(/(\d+)(%)/g, '<span class="text-yellow-400 font-bold">$1$2</span>');
    
    // Resaltar valores de probabilidad
    formattedText = formattedText.replace(/(probabilidad[^<]+)(\d+[.,]\d+|\d+)(%)/gi, 
        '$1<span class="text-yellow-400 font-bold">$2$3</span>');
    
    // Resaltar cuotas
    formattedText = formattedText.replace(/(cuota[^<]+)(\d+[.,]\d+)/gi, 
        '$1<span class="text-green-400 font-bold">$2</span>');
    
    // Resaltar nivel de confianza
    formattedText = formattedText.replace(/(confianza:\s*)(muy alta|alta|media|baja|muy baja)/gi, function(match, p1, p2) {
        let colorClass = 'text-red-500';
        if (p2.toLowerCase() === 'muy alta') colorClass = 'text-green-500';
        else if (p2.toLowerCase() === 'alta') colorClass = 'text-green-400';
        else if (p2.toLowerCase() === 'media') colorClass = 'text-yellow-400';
        else if (p2.toLowerCase() === 'baja') colorClass = 'text-orange-400';
        else if (p2.toLowerCase() === 'muy baja') colorClass = 'text-red-500';
        
        return p1 + '<span class="' + colorClass + ' font-bold">' + p2 + '</span>';
    });
    
    // Formatear listas
    formattedText = formattedText.replace(/(\d+\.\s*APUESTA[^<]+)/g, 
        '<div class="bg-black/40 border border-zinc-800 rounded-lg p-3 my-3"><h4 class="font-bold text-white mb-2">$1</h4>');
    formattedText = formattedText.replace(/(- Tipo de apuesta:)/g, 
        '<div class="text-gray-300"><span class="text-gray-400">$1</span>');
    formattedText = formattedText.replace(/(- Probabilidad:)/g, 
        '</div><div class="text-gray-300 mt-1"><span class="text-gray-400">$1</span>');
    formattedText = formattedText.replace(/(- Justificación:)/g, 
        '</div><div class="text-gray-300 mt-1"><span class="text-gray-400">$1</span>');
    formattedText = formattedText.replace(/(- Cuota justa:)/g, 
        '</div><div class="text-gray-300 mt-1"><span class="text-gray-400">$1</span>');
    formattedText = formattedText.replace(/(- Cuota mínima:)/g, 
        '</div><div class="text-gray-300 mt-1"><span class="text-gray-400">$1</span>');
    formattedText = formattedText.replace(/(- Nivel de confianza:)/g, 
        '</div><div class="text-gray-300 mt-1"><span class="text-gray-400">$1</span></div></div>');
    
    return formattedText;
}

async function analizarPartido(match) {
    showModal.value = true;
    loadingAnalisis.value = true;
    analisis.value = 'Recopilando información de los equipos y generando análisis detallado...';
    partidoSeleccionado.value = match;
    try {
        const response = await axios.post('/analizar-partido', {
            match_data: {
                homeTeam: {
                    team: {
                        id: match.home_team?.id, // Use DB ID
                        name: match.home_team?.name,
                        logo: match.home_team?.logo
                    },
                    league: {
                        name: match.league?.name,
                        country: match.league?.country?.name,
                        season: match.season?.year || new Date(match.match_date).getFullYear()
                    }
                },
                awayTeam: {
                    team: {
                        id: match.away_team?.id, // Use DB ID
                        name: match.away_team?.name,
                        logo: match.away_team?.logo
                    },
                    league: {
                        name: match.league?.name,
                        country: match.league?.country?.name,
                        season: match.season?.year || new Date(match.match_date).getFullYear()
                    }
                },
                match: {
                    id: match.id, // DB match ID
                    api_fixture_id: match.api_fixture_id,
                    date: match.match_date,
                    venue: match.venue || 'Desconocido',
                    status: match.status
                }
            }
        });
        
        if (response.data && response.data.success) {
            analisis.value = response.data.analysis || 'Análisis completado con éxito.';
        } else {
            analisis.value = 'No se pudo obtener el análisis: ' + (response.data?.message || 'Error desconocido');
        }
    } catch (e) {
        console.error('Error al analizar el partido:', e);
        analisis.value = 'Error al analizar el partido: ' + (e.response?.data?.message || e.message || 'Error desconocido');
    } finally {
        loadingAnalisis.value = false;
    }
}

// Función para obtener estadísticas de los equipos
async function obtenerEstadisticas(match) {
    showStatsModal.value = true;
    loadingStats.value = true;
    equiposStats.value = null;
    partidoSeleccionado.value = match;
    try {
        // This endpoint expects team names.
        const response = await axios.post('/estadisticas-equipos', {
            home_team: match.home_team?.name,
            away_team: match.away_team?.name,
            // Optionally pass league_id and season if your endpoint can use them
            // league_id: match.league?.api_league_id, // API ID for league if needed
            // season: match.season?.year 
        });
        
        if (response.data.success && response.data.data) {
            equiposStats.value = response.data.data;
        } else {
            equiposStats.value = { error: response.data.message || 'No se pudieron obtener las estadísticas.' };
        }
    } catch (e) {
        equiposStats.value = { error: 'Error al obtener las estadísticas de los equipos.' };
    } finally {
        loadingStats.value = false;
    }
}

// Function to refresh data
function refreshData() {
    refreshingData.value = true;
    
    // Recargamos la página actual
    router.reload({
        onSuccess: () => {
            refreshingData.value = false;
        },
        onError: () => {
            refreshingData.value = false;
            alert('Error al recargar los datos. Intente nuevamente.');
        }
    });
}

// Función para cambiar la fecha seleccionada
function changeDate(date) {
    selectedDate.value = date;
    
    // Actualizar la URL con la nueva fecha
    const queryParams = { ...router.page.props.filters, selected_date: date };
    
    // Mantener otros filtros si existen
    if (selectedLeague.value) queryParams.league_name = selectedLeague.value;
    if (selectedStatus.value) queryParams.status = selectedStatus.value;
    
    // Realizar la petición
    router.get(route('admin.matches'), queryParams, {
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Partidos de Fútbol" />

    <AdminLayout>
        <div class="py-6">
            <div class="max-w-full mx-auto px-4">
                <div class="backdrop-blur-sm bg-black/60 border border-zinc-800 overflow-hidden shadow-xl rounded-xl p-4 relative">
                    <!-- Efecto de resplandor en la esquina -->
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-red-500/20 rounded-full blur-3xl opacity-30"></div>
                    
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
                        <h1 class="text-2xl font-bold text-white mb-3 md:mb-0 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Partidos de Fútbol
                        </h1>
                    </div>
                    
                    <!-- Mensaje de error si lo hay -->
                    <div v-if="error" class="mb-4 p-3 bg-red-500/20 border border-red-500/40 rounded-lg">
                        <p class="text-red-300">{{ error }}</p>
                    </div>
                    
                    <div class="flex flex-wrap gap-2 mb-4">
                        <!-- Botones rápidos de fechas -->
                        <button 
                            @click="changeDate(new Date().toISOString().split('T')[0])"
                            :class="[
                                'px-3 py-1.5 text-sm rounded-lg transition-colors flex items-center',
                                selectedDate === new Date().toISOString().split('T')[0]
                                    ? 'bg-red-500 text-white' 
                                    : 'bg-black/40 text-gray-300 hover:bg-zinc-800'
                            ]"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            Hoy
                        </button>
                        <button 
                            @click="changeDate(new Date(Date.now() + 86400000).toISOString().split('T')[0])"
                            :class="[
                                'px-3 py-1.5 text-sm rounded-lg transition-colors flex items-center',
                                selectedDate === new Date(Date.now() + 86400000).toISOString().split('T')[0]
                                    ? 'bg-red-500 text-white' 
                                    : 'bg-black/40 text-gray-300 hover:bg-zinc-800'
                            ]"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            Mañana
                        </button>
                        <button 
                            @click="changeDate(new Date(Date.now() - 86400000).toISOString().split('T')[0])"
                            :class="[
                                'px-3 py-1.5 text-sm rounded-lg transition-colors flex items-center',
                                selectedDate === new Date(Date.now() - 86400000).toISOString().split('T')[0]
                                    ? 'bg-red-500 text-white' 
                                    : 'bg-black/40 text-gray-300 hover:bg-zinc-800'
                            ]"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            Ayer
                        </button>
                        <button 
                            @click="changeDate(new Date(Date.now() + 2*86400000).toISOString().split('T')[0])"
                            :class="[
                                'px-3 py-1.5 text-sm rounded-lg transition-colors flex items-center',
                                selectedDate === new Date(Date.now() + 2*86400000).toISOString().split('T')[0]
                                    ? 'bg-red-500 text-white' 
                                    : 'bg-black/40 text-gray-300 hover:bg-zinc-800'
                            ]"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            Pasado mañana
                        </button>
                        
                        <!-- Filtro estado -->
                        <select 
                            v-model="selectedStatus" 
                            class="px-3 py-1.5 text-sm bg-black/50 border border-zinc-800 rounded-lg text-white focus:border-red-500 focus:outline-none"
                        >
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    
                    <!-- Selección de liga tipo botones -->
                    <div class="mb-4">
                        <div class="flex flex-wrap gap-2">
                            <button 
                                @click="selectedLeague = ''"
                                :class="[
                                    'px-3 py-1.5 text-xs rounded-lg transition-colors flex items-center',
                                    selectedLeague === '' 
                                        ? 'bg-red-500 text-white' 
                                        : 'bg-black/40 text-gray-300 hover:bg-zinc-800'
                                ]"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                </svg>
                                Todas
                            </button>
                            <template v-for="option in leagueOptions.filter(l => l.value !== '')" :key="option.value">
                                <button 
                                    @click="selectedLeague = option.value"
                                    :class="[
                                        'px-3 py-1.5 text-xs rounded-lg transition-colors flex items-center',
                                        selectedLeague === option.value 
                                            ? 'bg-red-500 text-white' 
                                            : 'bg-black/40 text-gray-300 hover:bg-zinc-800'
                                    ]"
                                >
                                    {{ option.label }}
                                </button>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Contador de partidos y resultados -->
                    <div class="mb-3 flex justify-between items-center border-b border-zinc-800 pb-2">
                        <div class="text-gray-400 flex items-center text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            <span class="font-medium">{{ filteredMatches.length }}</span> 
                            <span class="ml-1">partidos</span>
                            <span class="mx-2 text-gray-600">·</span>
                            <span>{{ new Date(selectedDate).toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
                        </div>
                    </div>
                    
                    <!-- Cargando... -->
                    <div v-if="isLoading" class="flex justify-center items-center py-12">
                        <div class="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-red-500"></div>
                    </div>
                    
                    <!-- Sin resultados -->
                    <div v-else-if="filteredMatches.length === 0" class="py-12 px-4 text-center">
                        <div class="flex flex-col items-center">
                            <div class="text-6xl text-gray-600 mb-4">⚽</div>
                            <h3 class="text-xl font-semibold text-gray-400 mb-2">No hay partidos</h3>
                            <div class="text-gray-500 mx-auto">
                                <p>No se encontraron partidos para la fecha seleccionada o con los filtros aplicados.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de partidos -->
                    <div v-else>
                        <div v-for="(leagueMatches, leagueName) in groupedMatches" :key="leagueName" class="mb-4">
                            <!-- Cabecera de liga -->
                            <div class="flex items-center py-2 px-3 bg-zinc-900/70 border-b border-zinc-700 rounded-t-lg">
                                <img 
                                    :src="leagueMatches[0]?.league_logo" 
                                    class="h-5 w-5 mr-2 object-contain"
                                    onerror="this.src='https://via.placeholder.com/20?text=🏆'; this.onerror=null;"
                                />
                                <div class="flex-1 flex items-center">
                                    <span class="text-gray-300 text-sm font-semibold">
                                        {{ leagueName }}
                                    </span>
                                    <span v-if="leagueMatches[0]?.league_country" class="ml-2 text-xs text-gray-500">
                                        {{ leagueMatches[0]?.league_country }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500">{{ leagueMatches.length }} partidos</div>
                            </div>
                            
                            <!-- Partidos de esta liga -->
                            <div class="bg-black/40 border-x border-b border-zinc-800 rounded-b-lg divide-y divide-zinc-800/50">
                                <div 
                                    v-for="match in leagueMatches" 
                                    :key="match.id"
                                    class="px-3 py-2.5 hover:bg-zinc-900/40 transition-colors"
                                >
                                    <!-- Fila del partido -->
                                    <div class="flex items-center">
                                        <!-- Hora del partido -->
                                        <div class="w-16 text-center">
                                            <div class="text-gray-300 text-sm font-medium">
                                                {{ formatShortTime(match.match_date) }}
                                            </div>
                                            <div v-if="match.status === 'In Progress' || match.status === 'live'" 
                                                class="text-xs text-red-400 animate-pulse font-medium mt-0.5">
                                                LIVE
                                            </div>
                                            <div v-else class="text-xs text-gray-500 mt-0.5">
                                                {{ match.status === 'scheduled' ? 'Programado' : match.status }}
                                            </div>
                                        </div>
                                        
                                        <!-- Equipos y resultado -->
                                        <div class="flex-1 flex justify-between items-center">
                                            <!-- Equipo local -->
                                            <div class="flex items-center w-5/12">
                                                <div class="flex flex-col items-end text-right w-full mr-2">
                                                    <span class="text-white text-sm font-medium truncate">{{ match.home_team_name }}</span>
                                                    <span class="text-gray-500 text-xs">{{ match.home_team?.country || 'País Desconocido' }}</span>
                                                </div>
                                                <div class="h-8 w-8 bg-black/40 rounded-full flex items-center justify-center border border-zinc-800">
                                                    <img 
                                                        :src="match.home_team_logo" 
                                                        :alt="match.home_team_name" 
                                                        class="max-h-6 max-w-6 object-contain"
                                                        onerror="this.src='https://via.placeholder.com/24?text=🏠'; this.onerror=null;"
                                                    />
                                                </div>
                                            </div>
                                            
                                            <!-- Marcador -->
                                            <div class="flex items-center justify-center w-2/12">
                                                <div class="px-3 py-1 bg-black/40 rounded-md flex items-center">
                                                    <span class="text-white font-bold text-lg">{{ match.home_goals || '0' }}</span>
                                                    <span class="text-gray-500 mx-1">-</span>
                                                    <span class="text-white font-bold text-lg">{{ match.away_goals || '0' }}</span>
                                                </div>
                                            </div>
                                            
                                            <!-- Equipo visitante -->
                                            <div class="flex items-center w-5/12">
                                                <div class="h-8 w-8 bg-black/40 rounded-full flex items-center justify-center border border-zinc-800 mr-2">
                                                    <img 
                                                        :src="match.away_team_logo" 
                                                        :alt="match.away_team_name" 
                                                        class="max-h-6 max-w-6 object-contain"
                                                        onerror="this.src='https://via.placeholder.com/24?text=🏃'; this.onerror=null;"
                                                    />
                                                </div>
                                                <div class="flex flex-col items-start text-left w-full">
                                                    <span class="text-white text-sm font-medium truncate">{{ match.away_team_name }}</span>
                                                    <span class="text-gray-500 text-xs">{{ match.away_team?.country || 'País Desconocido' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Acciones -->
                                        <div class="ml-3 flex space-x-1">
                                            <button @click="analizarPartido(match)" class="p-1.5 bg-red-600 hover:bg-red-700 rounded-md" title="Analizar partido">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 2a.75.75 0 01.75.75v12.59l1.95-2.1a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 111.1-1.02l1.95 2.1V2.75A.75.75 0 0110 2z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                            <button @click="obtenerEstadisticas(match)" class="p-1.5 bg-zinc-700 hover:bg-zinc-600 rounded-md" title="Ver estadísticas">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5 3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5zm9 4a1 1 0 10-2 0v6a1 1 0 102 0V7zm-3 2a1 1 0 10-2 0v4a1 1 0 102 0V9zm-3 3a1 1 0 10-2 0v1a1 1 0 102 0v-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Información adicional -->
                                    <div class="mt-1 flex justify-between items-center text-xs text-gray-500">
                                        <div>
                                            <span v-if="match.venue">🏟️ {{ match.venue }}</span>
                                            <span v-else>Sin sede</span>
                                        </div>
                                        <div>
                                            {{ match.round || 'Sin información' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Paginación -->
                    <div v-if="props.matches && props.matches.links && props.matches.links.length > 3" class="mt-4">
                        <div class="flex justify-center">
                            <div v-for="(link, index) in props.matches.links" :key="index" class="mx-0.5">
                                <a
                                    v-if="link.url"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1 rounded border text-sm',
                                        link.active 
                                            ? 'bg-red-500/20 border-red-500 text-white' 
                                            : 'border-zinc-800 text-gray-400 hover:text-white hover:border-red-500'
                                    ]"
                                ></a>
                                <span 
                                    v-else
                                    v-html="link.label" 
                                    class="px-3 py-1 text-gray-600 text-sm"
                                ></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
    <Modal :show="showModal" @close="showModal = false">
        <div class="p-6 max-h-[80vh] overflow-y-auto">
            <h2 class="text-2xl font-bold mb-4 text-white flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Análisis Profesional para Apostadores
            </h2>
            
            <!-- Encabezado del partido -->
            <div v-if="partidoSeleccionado" class="mb-4 p-4 bg-black/40 border border-zinc-800 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <div class="h-10 w-10 bg-black/40 rounded-full flex items-center justify-center border border-zinc-800 mr-3">
                            <img 
                                :src="partidoSeleccionado.home_team_logo" 
                                :alt="partidoSeleccionado.home_team_name" 
                                class="max-h-8 max-w-8 object-contain"
                                onerror="this.src='https://via.placeholder.com/32?text=🏠'; this.onerror=null;"
                            />
                        </div>
                        <div class="text-white font-bold">{{ partidoSeleccionado.home_team_name }}</div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-gray-400 text-xs mb-1">{{ formatDate(partidoSeleccionado.match_date) }}</div>
                        <div class="px-3 py-1 bg-zinc-900 rounded-md inline-flex items-center">
                            <span class="text-white font-bold">{{ partidoSeleccionado.home_goals || '0' }}</span>
                            <span class="text-gray-500 mx-2">vs</span>
                            <span class="text-white font-bold">{{ partidoSeleccionado.away_goals || '0' }}</span>
                        </div>
                        <div class="mt-1 text-xs">
                            <span :class="{'text-blue-400 animate-pulse': partidoSeleccionado.status === 'In Progress' || partidoSeleccionado.status === 'live', 'text-green-400': partidoSeleccionado.status === 'Match Finished'}">
                                {{ partidoSeleccionado.status }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <div class="text-white font-bold mr-3">{{ partidoSeleccionado.away_team_name }}</div>
                        <div class="h-10 w-10 bg-black/40 rounded-full flex items-center justify-center border border-zinc-800">
                            <img 
                                :src="partidoSeleccionado.away_team_logo" 
                                :alt="partidoSeleccionado.away_team_name" 
                                class="max-h-8 max-w-8 object-contain"
                                onerror="this.src='https://via.placeholder.com/32?text=🏃'; this.onerror=null;"
                            />
                        </div>
                    </div>
                </div>
                
                <div class="text-xs text-gray-400 text-center">
                    <span v-if="partidoSeleccionado.venue">🏟️ {{ partidoSeleccionado.venue }}</span>
                    <span v-if="partidoSeleccionado.league_name" class="ml-3">🏆 {{ partidoSeleccionado.league_name }}</span>
                    <span v-if="partidoSeleccionado.round" class="ml-3">📅 {{ partidoSeleccionado.round }}</span>
                </div>
            </div>
            
            <!-- Cargando -->
            <div v-if="loadingAnalisis" class="p-6 bg-black/30 border border-zinc-800 rounded-lg">
                <div class="flex flex-col items-center text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-red-500 mb-4"></div>
                    <p class="text-gray-300">{{ analisis }}</p>
                    <p class="text-gray-500 text-sm mt-2">Esto puede tomar unos momentos mientras analizamos todos los datos disponibles...</p>
                </div>
            </div>
            
            <!-- Análisis generado -->
            <div v-else class="prose prose-invert max-w-none">
                <div v-if="analisis.startsWith('ERROR:') || analisis.startsWith('Error ')" class="bg-red-500/20 border border-red-500/40 p-4 rounded-lg mb-4">
                    <p class="text-red-300 mb-2">{{ analisis }}</p>
                    <button @click="analizarPartido(partidoSeleccionado)" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm">
                        Intentar nuevamente
                    </button>
                </div>
                <div v-else class="text-gray-200 whitespace-pre-wrap" v-html="formatText(analisis)"></div>
            </div>
            
            <!-- Botón para cerrar -->
            <div class="mt-6 text-right">
                <button @click="showModal = false" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                    Cerrar
                </button>
            </div>
        </div>
    </Modal>
    
    <!-- Modal para estadísticas de equipos -->
    <Modal :show="showStatsModal" @close="showStatsModal = false">
        <div class="p-6">
            <h2 class="text-xl font-bold mb-4 text-white">Estadísticas de equipos</h2>
            <div v-if="partidoSeleccionado" class="mb-4 text-gray-300">
                <span class="font-semibold">{{ partidoSeleccionado.home_team_name }}</span>
                vs
                <span class="font-semibold">{{ partidoSeleccionado.away_team_name }}</span>
                <span class="block text-xs text-gray-400">{{ formatDate(partidoSeleccionado.match_date) }}</span>
            </div>
            
            <!-- Cargando datos -->
            <div v-if="loadingStats" class="text-gray-400 flex items-center">
                <span class="animate-spin mr-2">⏳</span> Cargando estadísticas...
            </div>
            
            <!-- Error -->
            <div v-else-if="equiposStats && equiposStats.error" class="text-red-400">
                {{ equiposStats.error }}
            </div>
            
            <!-- Estadísticas -->
            <div v-else-if="equiposStats" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Equipo Local -->
                <div class="bg-black/30 border border-zinc-800 rounded-lg p-4">
                    <div class="flex items-center mb-4">
                        <img 
                            :src="equiposStats.homeTeam.team.logo" 
                            :alt="equiposStats.homeTeam.team.name" 
                            class="h-12 w-12 mr-3"
                            onerror="this.src='https://via.placeholder.com/48?text=🏠'; this.onerror=null;"
                        />
                        <div>
                            <h3 class="text-lg font-bold text-white">{{ equiposStats.homeTeam.team.name }}</h3>
                            <p class="text-xs text-gray-400">{{ equiposStats.homeTeam.league.name }}</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="text-sm">
                            <span class="text-gray-400">Forma:</span> 
                            <span class="font-mono bg-black/40 px-1 text-white">{{ equiposStats.homeTeam.form || 'N/A' }}</span>
                        </div>
                        
                        <div class="text-sm">
                            <span class="text-gray-400">Partidos:</span> 
                            <span class="text-white">{{ equiposStats.homeTeam.fixtures?.played?.total || 0 }}</span>
                        </div>
                        
                        <div class="text-sm">
                            <span class="text-gray-400">Goles marcados:</span> 
                            <span class="text-white">{{ equiposStats.homeTeam.goals?.for?.total?.total || 0 }}</span>
                        </div>
                        
                        <div class="text-sm">
                            <span class="text-gray-400">Goles recibidos:</span> 
                            <span class="text-white">{{ equiposStats.homeTeam.goals?.against?.total?.total || 0 }}</span>
                        </div>
                        
                        <div class="text-sm" v-if="equiposStats.homeTeam.biggest">
                            <span class="text-gray-400">Mayor victoria:</span> 
                            <span class="text-white">{{ equiposStats.homeTeam.biggest.wins?.home || equiposStats.homeTeam.biggest.wins?.away || 'N/A' }}</span>
                        </div>
                        
                        <div class="text-sm" v-if="equiposStats.homeTeam.clean_sheet">
                            <span class="text-gray-400">Porterías a cero:</span> 
                            <span class="text-white">{{ equiposStats.homeTeam.clean_sheet?.total || 0 }}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Equipo Visitante -->
                <div class="bg-black/30 border border-zinc-800 rounded-lg p-4">
                    <div class="flex items-center mb-4">
                        <img 
                            :src="equiposStats.awayTeam.team.logo" 
                            :alt="equiposStats.awayTeam.team.name" 
                            class="h-12 w-12 mr-3"
                            onerror="this.src='https://via.placeholder.com/48?text=🏃'; this.onerror=null;"
                        />
                        <div>
                            <h3 class="text-lg font-bold text-white">{{ equiposStats.awayTeam.team.name }}</h3>
                            <p class="text-xs text-gray-400">{{ equiposStats.awayTeam.league.name }}</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="text-sm">
                            <span class="text-gray-400">Forma:</span> 
                            <span class="font-mono bg-black/40 px-1 text-white">{{ equiposStats.awayTeam.form || 'N/A' }}</span>
                        </div>
                        
                        <div class="text-sm">
                            <span class="text-gray-400">Partidos:</span> 
                            <span class="text-white">{{ equiposStats.awayTeam.fixtures?.played?.total || 0 }}</span>
                        </div>
                        
                        <div class="text-sm">
                            <span class="text-gray-400">Goles marcados:</span> 
                            <span class="text-white">{{ equiposStats.awayTeam.goals?.for?.total?.total || 0 }}</span>
                        </div>
                        
                        <div class="text-sm">
                            <span class="text-gray-400">Goles recibidos:</span> 
                            <span class="text-white">{{ equiposStats.awayTeam.goals?.against?.total?.total || 0 }}</span>
                        </div>
                        
                        <div class="text-sm" v-if="equiposStats.awayTeam.biggest">
                            <span class="text-gray-400">Mayor victoria:</span> 
                            <span class="text-white">{{ equiposStats.awayTeam.biggest.wins?.away || equiposStats.awayTeam.biggest.wins?.home || 'N/A' }}</span>
                        </div>
                        
                        <div class="text-sm" v-if="equiposStats.awayTeam.clean_sheet">
                            <span class="text-gray-400">Porterías a cero:</span> 
                            <span class="text-white">{{ equiposStats.awayTeam.clean_sheet?.total || 0 }}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Head-to-Head -->
                <div class="col-span-1 md:col-span-2 bg-black/30 border border-zinc-800 rounded-lg p-4">
                    <h3 class="font-bold text-white mb-2">Enfrentamientos directos</h3>
                    
                    <div v-if="equiposStats.headToHead && equiposStats.headToHead.length > 0" class="space-y-2">
                        <div 
                            v-for="(match, index) in equiposStats.headToHead" 
                            :key="index"
                            class="text-sm flex justify-between border-b border-zinc-800 pb-2"
                        >
                            <div class="flex items-center">
                                <span class="text-gray-300 mr-1">{{ match.teams.home.name }}</span>
                                <span class="text-white mx-1">{{ match.goals.home }}-{{ match.goals.away }}</span>
                                <span class="text-gray-300 ml-1">{{ match.teams.away.name }}</span>
                            </div>
                            <div class="text-gray-400">
                                {{ new Date(match.fixture.date).toLocaleDateString() }}
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="text-gray-400 text-sm">
                        No hay enfrentamientos directos recientes.
                    </div>
                </div>
            </div>
            
            <div class="mt-6 text-right">
                <button @click="showStatsModal = false" class="px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded">Cerrar</button>
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
