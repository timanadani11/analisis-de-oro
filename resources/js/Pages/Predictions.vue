<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

// Datos de muestra para predicciones
const predictions = ref([
    { 
        id: 1, 
        league: 'Premier League',
        match: { 
            home_team: 'Arsenal', 
            away_team: 'Manchester City', 
            date: '2023-05-10', 
            time: '20:00',
            home_logo: 'https://media.api-sports.io/football/teams/42.png',
            away_logo: 'https://media.api-sports.io/football/teams/50.png',
        },
        predictions: [
            { type: 'Resultado', value: 'Empate', odds: 3.25, confidence: 67 },
            { type: 'Goles', value: 'Más de 2.5', odds: 1.85, confidence: 78 },
            { type: 'Ambos anotan', value: 'Sí', odds: 1.75, confidence: 82 }
        ]
    },
    { 
        id: 2, 
        league: 'La Liga',
        match: { 
            home_team: 'Barcelona', 
            away_team: 'Real Madrid', 
            date: '2023-05-12', 
            time: '21:00',
            home_logo: 'https://media.api-sports.io/football/teams/529.png',
            away_logo: 'https://media.api-sports.io/football/teams/541.png',
        },
        predictions: [
            { type: 'Resultado', value: 'Barcelona gana', odds: 2.10, confidence: 63 },
            { type: 'Goles', value: 'Más de 2.5', odds: 1.90, confidence: 72 },
            { type: 'Ambos anotan', value: 'Sí', odds: 1.65, confidence: 85 }
        ]
    },
    { 
        id: 3, 
        league: 'Serie A',
        match: { 
            home_team: 'Inter', 
            away_team: 'Milan', 
            date: '2023-05-15', 
            time: '20:45',
            home_logo: 'https://media.api-sports.io/football/teams/505.png',
            away_logo: 'https://media.api-sports.io/football/teams/489.png',
        },
        predictions: [
            { type: 'Resultado', value: 'Inter gana', odds: 2.30, confidence: 61 },
            { type: 'Goles', value: 'Menos de 2.5', odds: 2.05, confidence: 68 },
            { type: 'Ambos anotan', value: 'No', odds: 2.35, confidence: 59 }
        ]
    }
]);

// Categorías de predicciones
const categories = [
    { id: 'all', name: 'Todas', count: predictions.value.length },
    { id: 'high_value', name: 'Alto valor', count: 2 },
    { id: 'today', name: 'Hoy', count: 1 },
    { id: 'upcoming', name: 'Próximas', count: 3 }
];

// Filtro activo
const activeCategory = ref('all');

// Método para cambiar el filtro
const setCategory = (categoryId) => {
    activeCategory.value = categoryId;
};

// Método para obtener clase de confianza
const getConfidenceClass = (confidence) => {
    if (confidence >= 80) return 'bg-green-500/20 text-green-400';
    if (confidence >= 65) return 'bg-yellow-500/20 text-yellow-400';
    return 'bg-red-500/20 text-red-400';
};

// Método para obtener textp de confianza
const getConfidenceText = (confidence) => {
    if (confidence >= 80) return 'Alta';
    if (confidence >= 65) return 'Media';
    return 'Baja';
};
</script>

<template>
    <Head title="Predicciones" />
    
    <AppLayout>
        <div class="py-4 md:py-6">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
                <!-- Encabezado compacto -->
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-2">
                    <div>
                        <h1 class="text-2xl font-bold text-white leading-tight">Predicciones</h1>
                        <p class="text-sm text-gray-400">Análisis y predicciones basadas en inteligencia artificial</p>
                    </div>
                    
                    <!-- Estadísticas rápidas -->
                    <div class="text-right">
                        <div class="text-gray-400 text-sm">{{ new Date().toLocaleDateString('es-ES', {weekday: 'short', day: 'numeric', month: 'long', year: 'numeric'}) }}</div>
                        <div class="text-white text-sm font-medium">{{ predictions.length }} predicciones activas</div>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="flex space-x-2 overflow-x-auto py-1 mb-5">
                    <button 
                        v-for="category in categories" 
                        :key="category.id"
                        @click="setCategory(category.id)"
                        :class="[
                            'px-3 py-2 rounded-lg text-xs whitespace-nowrap transition-all duration-200',
                            activeCategory === category.id 
                                ? 'bg-red-500/20 border border-red-500 text-white' 
                                : 'bg-black/30 border border-zinc-800 text-gray-300 hover:border-red-500/50'
                        ]"
                    >
                        {{ category.name }} ({{ category.count }})
                    </button>
                </div>
                
                <!-- Lista de predicciones -->
                <div class="space-y-4">
                    <div v-for="prediction in predictions" :key="prediction.id" class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg overflow-hidden">
                        <!-- Cabecera de la predicción con liga -->
                        <div class="px-3 py-2 border-b border-zinc-800 flex items-center bg-black/30">
                            <div class="text-sm text-gray-300">{{ prediction.league }}</div>
                            <div class="text-xs text-gray-400 ml-2">{{ prediction.match.date + ' - ' + prediction.match.time }}</div>
                        </div>
                        
                        <!-- Información del partido -->
                        <div class="p-3">
                            <!-- Equipos y logos -->
                            <div class="flex items-center justify-between mb-3">
                                <!-- Equipo local -->
                                <div class="flex items-center w-[40%]">
                                    <div class="h-8 w-8 flex-shrink-0 mr-2">
                                        <img 
                                            :src="prediction.match.home_logo" 
                                            :alt="prediction.match.home_team" 
                                            class="h-full w-full object-contain"
                                            onerror="this.src='https://via.placeholder.com/32?text=🏠'; this.onerror=null;"
                                        />
                                    </div>
                                    <div class="text-sm text-white font-medium truncate">{{ prediction.match.home_team }}</div>
                                </div>
                                
                                <!-- Versus -->
                                <div class="text-center">
                                    <div class="text-sm text-white font-medium">VS</div>
                                </div>
                                
                                <!-- Equipo visitante -->
                                <div class="flex items-center justify-end w-[40%]">
                                    <div class="text-sm text-white font-medium truncate text-right mr-2">{{ prediction.match.away_team }}</div>
                                    <div class="h-8 w-8 flex-shrink-0">
                                        <img 
                                            :src="prediction.match.away_logo" 
                                            :alt="prediction.match.away_team" 
                                            class="h-full w-full object-contain"
                                            onerror="this.src='https://via.placeholder.com/32?text=🏠'; this.onerror=null;"
                                        />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Predicciones específicas -->
                            <div class="space-y-2 mt-4">
                                <div v-for="(pred, index) in prediction.predictions" :key="index" 
                                    class="flex items-center justify-between bg-black/30 rounded-lg px-3 py-2 border border-zinc-800/60">
                                    <div>
                                        <div class="text-xs text-gray-400">{{ pred.type }}</div>
                                        <div class="text-sm text-white font-medium">{{ pred.value }}</div>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="flex flex-col items-end mr-4">
                                            <div class="text-xs text-gray-400">Confianza</div>
                                            <div class="flex items-center">
                                                <span :class="['text-xs px-1.5 py-0.5 rounded-sm', getConfidenceClass(pred.confidence)]">
                                                    {{ getConfidenceText(pred.confidence) }}
                                                </span>
                                                <span class="text-xs text-gray-400 ml-1">({{ pred.confidence }}%)</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end">
                                            <div class="text-xs text-gray-400">Cuota</div>
                                            <div class="text-lg font-bold text-white">{{ pred.odds.toFixed(2) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Botón de análisis completo -->
                            <div class="mt-3 flex justify-end">
                                <button class="text-xs bg-red-500/10 text-red-500 px-3 py-1 rounded border border-red-500/20 hover:bg-red-500/20 transition-colors duration-200">
                                    Ver análisis completo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Banner de suscripción (versión compacta) -->
                <div class="mt-6 backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg p-4 relative overflow-hidden">
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-red-500/10 rounded-full blur-3xl opacity-30"></div>
                    
                    <div class="relative z-10 md:flex items-center">
                        <div class="flex-shrink-0 mr-4 mb-4 md:mb-0">
                            <div class="w-12 h-12 bg-red-500/20 rounded-full flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-white mb-1">Accede a predicciones exclusivas</h3>
                            <p class="text-sm text-gray-300 mb-3">Suscríbete a nuestro plan premium para obtener predicciones avanzadas y análisis detallados.</p>
                            <button class="text-xs bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded transition-colors duration-200">
                                Probar Premium
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Para asegurar que nombres largos se truncan correctamente */
.truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
