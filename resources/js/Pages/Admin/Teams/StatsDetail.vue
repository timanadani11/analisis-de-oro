<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';

const props = defineProps({
    team: Object,
    stats: Object,
    stats_data: Object
});

// Función para formatear JSON para su visualización
function formatJSON(json) {
    if (!json) return 'No hay datos disponibles';
    return JSON.stringify(json, null, 2);
}

// Función para verificar si un objeto es vacío o nulo
function isEmpty(obj) {
    return !obj || Object.keys(obj).length === 0;
}
</script>

<template>
    <Head :title="`Estadísticas de ${team.name}`" />

    <AdminLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Cabecera -->
                <div class="md:flex md:items-center md:justify-between mb-6">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-2xl font-bold leading-7 text-white sm:text-3xl sm:truncate flex items-center">
                            <img v-if="team.logo" :src="team.logo" :alt="team.name" class="h-10 w-10 rounded-full mr-3">
                            <span>Estadísticas de {{ team.name }}</span>
                        </h2>
                    </div>
                    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
                        <Link :href="route('admin.team-stats.index')" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                            </svg>
                            Volver
                        </Link>
                    </div>
                </div>
                
                <!-- Información del equipo -->
                <div class="bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6">
                    <div class="px-4 py-5 sm:px-6 flex items-center">
                        <img v-if="team.logo" :src="team.logo" :alt="team.name" class="h-16 w-16 rounded-full mr-4">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-white">{{ team.name }}</h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-400" v-if="team.short_name">{{ team.short_name }}</p>
                            <p class="mt-1 max-w-2xl text-sm text-gray-400" v-if="team.country">País: {{ team.country }}</p>
                        </div>
                    </div>
                    <div class="border-t border-gray-700">
                        <dl>
                            <div class="bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-400">ID en base de datos</dt>
                                <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">{{ team.id }}</dd>
                            </div>
                            <div class="bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-400">ID API</dt>
                                <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">{{ team.api_team_id || 'N/A' }}</dd>
                            </div>
                            <div class="bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-400">Sitio Web</dt>
                                <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                                    <a v-if="team.website" :href="team.website" target="_blank" class="text-blue-400 hover:text-blue-300">
                                        {{ team.website }}
                                    </a>
                                    <span v-else>N/A</span>
                                </dd>
                            </div>
                            <div class="bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-400">Dirección</dt>
                                <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">{{ team.address || 'N/A' }}</dd>
                            </div>
                            <div class="bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-400">Colores del club</dt>
                                <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">{{ team.club_colors || 'N/A' }}</dd>
                            </div>
                            <div class="bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-400">Estadio</dt>
                                <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">{{ team.stadium || 'N/A' }}</dd>
                            </div>
                            <div class="bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-400">Fundado en</dt>
                                <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">{{ team.founded || 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
                
                <!-- Estadísticas del equipo -->
                <div v-if="stats_data" class="space-y-6">
                    <!-- Form -->
                    <div v-if="stats_data.form" class="bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6">
                            <h3 class="text-lg leading-6 font-medium text-white">Forma reciente</h3>
                        </div>
                        <div class="border-t border-gray-700 px-4 py-5 sm:p-6">
                            <div class="text-xl font-mono tracking-widest">
                                <span v-for="(letter, index) in stats_data.form" :key="index" 
                                    :class="{
                                        'text-green-400 bg-green-900/30 px-2 py-1 rounded-md': letter === 'W',
                                        'text-red-400 bg-red-900/30 px-2 py-1 rounded-md': letter === 'L',
                                        'text-yellow-400 bg-yellow-900/30 px-2 py-1 rounded-md': letter === 'D'
                                    }"
                                    class="mr-1"
                                >
                                    {{ letter }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics -->
                    <div v-if="stats_data.fixtures && !isEmpty(stats_data.fixtures)" class="bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6">
                            <h3 class="text-lg leading-6 font-medium text-white">Estadísticas de partidos</h3>
                        </div>
                        <div class="border-t border-gray-700">
                            <dl>
                                <!-- Partidos jugados -->
                                <div v-if="stats_data.fixtures.played" class="bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-400">Partidos jugados</dt>
                                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                                        Total: {{ stats_data.fixtures.played.total || 0 }} | 
                                        Local: {{ stats_data.fixtures.played.home || 0 }} | 
                                        Visitante: {{ stats_data.fixtures.played.away || 0 }}
                                    </dd>
                                </div>
                                
                                <!-- Victorias -->
                                <div v-if="stats_data.fixtures.wins" class="bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-400">Victorias</dt>
                                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                                        Total: {{ stats_data.fixtures.wins.total || 0 }} | 
                                        Local: {{ stats_data.fixtures.wins.home || 0 }} | 
                                        Visitante: {{ stats_data.fixtures.wins.away || 0 }}
                                    </dd>
                                </div>
                                
                                <!-- Empates -->
                                <div v-if="stats_data.fixtures.draws" class="bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-400">Empates</dt>
                                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                                        Total: {{ stats_data.fixtures.draws.total || 0 }} | 
                                        Local: {{ stats_data.fixtures.draws.home || 0 }} | 
                                        Visitante: {{ stats_data.fixtures.draws.away || 0 }}
                                    </dd>
                                </div>
                                
                                <!-- Derrotas -->
                                <div v-if="stats_data.fixtures.loses" class="bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-400">Derrotas</dt>
                                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                                        Total: {{ stats_data.fixtures.loses.total || 0 }} | 
                                        Local: {{ stats_data.fixtures.loses.home || 0 }} | 
                                        Visitante: {{ stats_data.fixtures.loses.away || 0 }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    
                    <!-- Goals -->
                    <div v-if="stats_data.goals && !isEmpty(stats_data.goals)" class="bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6">
                            <h3 class="text-lg leading-6 font-medium text-white">Estadísticas de goles</h3>
                        </div>
                        <div class="border-t border-gray-700">
                            <dl>
                                <!-- Goles a favor -->
                                <div v-if="stats_data.goals.for && stats_data.goals.for.total" class="bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-400">Goles marcados</dt>
                                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                                        Total: {{ stats_data.goals.for.total.total || 0 }} | 
                                        Local: {{ stats_data.goals.for.total.home || 0 }} | 
                                        Visitante: {{ stats_data.goals.for.total.away || 0 }}
                                    </dd>
                                </div>
                                
                                <!-- Goles en contra -->
                                <div v-if="stats_data.goals.against && stats_data.goals.against.total" class="bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-400">Goles recibidos</dt>
                                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                                        Total: {{ stats_data.goals.against.total.total || 0 }} | 
                                        Local: {{ stats_data.goals.against.total.home || 0 }} | 
                                        Visitante: {{ stats_data.goals.against.total.away || 0 }}
                                    </dd>
                                </div>
                                
                                <!-- Promedio de goles por partido -->
                                <div v-if="stats_data.goals.for.average && stats_data.goals.for.average.total" class="bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-400">Promedio de goles marcados</dt>
                                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                                        Total: {{ stats_data.goals.for.average.total || 0 }} | 
                                        Local: {{ stats_data.goals.for.average.home || 0 }} | 
                                        Visitante: {{ stats_data.goals.for.average.away || 0 }}
                                    </dd>
                                </div>
                                
                                <!-- Promedio de goles en contra -->
                                <div v-if="stats_data.goals.against.average && stats_data.goals.against.average.total" class="bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-400">Promedio de goles recibidos</dt>
                                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                                        Total: {{ stats_data.goals.against.average.total || 0 }} | 
                                        Local: {{ stats_data.goals.against.average.home || 0 }} | 
                                        Visitante: {{ stats_data.goals.against.average.away || 0 }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    
                    <!-- Additional statistics -->
                    <div class="bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6">
                            <h3 class="text-lg leading-6 font-medium text-white">Estadísticas adicionales</h3>
                        </div>
                        <div class="border-t border-gray-700">
                            <dl>
                                <!-- Clean sheets -->
                                <div v-if="stats_data.clean_sheet" class="bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-400">Porterías a cero</dt>
                                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                                        Total: {{ stats_data.clean_sheet.total || 0 }} | 
                                        Local: {{ stats_data.clean_sheet.home || 0 }} | 
                                        Visitante: {{ stats_data.clean_sheet.away || 0 }}
                                    </dd>
                                </div>
                                
                                <!-- Failed to score -->
                                <div v-if="stats_data.failed_to_score" class="bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-400">Partidos sin marcar</dt>
                                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                                        Total: {{ stats_data.failed_to_score.total || 0 }} | 
                                        Local: {{ stats_data.failed_to_score.home || 0 }} | 
                                        Visitante: {{ stats_data.failed_to_score.away || 0 }}
                                    </dd>
                                </div>
                                
                                <!-- Biggest wins -->
                                <div v-if="stats_data.biggest && stats_data.biggest.wins" class="bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-400">Mayor victoria</dt>
                                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                                        Local: {{ stats_data.biggest.wins.home || 'N/A' }} | 
                                        Visitante: {{ stats_data.biggest.wins.away || 'N/A' }}
                                    </dd>
                                </div>
                                
                                <!-- Biggest losses -->
                                <div v-if="stats_data.biggest && stats_data.biggest.loses" class="bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-400">Mayor derrota</dt>
                                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                                        Local: {{ stats_data.biggest.loses.home || 'N/A' }} | 
                                        Visitante: {{ stats_data.biggest.loses.away || 'N/A' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    
                    <!-- JSON data viewer (for debugging) -->
                    <div class="bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                            <h3 class="text-lg leading-6 font-medium text-white">Datos JSON completos</h3>
                            <button @click="$refs.jsonData.classList.toggle('hidden')" class="text-gray-400 hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                        <div ref="jsonData" class="border-t border-gray-700 hidden">
                            <div class="p-4 overflow-x-auto">
                                <pre class="text-xs text-gray-400 bg-gray-900 p-4 rounded-lg">{{ formatJSON(stats_data) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-else class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-300">No hay estadísticas disponibles</h3>
                    <p class="mt-1 text-gray-500">No se encontraron estadísticas para este equipo.</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Estilos específicos si son necesarios */
</style> 