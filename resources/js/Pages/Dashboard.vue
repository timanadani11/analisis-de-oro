<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useNavigation } from '@/composables/useNavigation';
import { PUBLIC_ROUTES, AUTH_ROUTES } from '@/routes';

const { navigate, post, isActive } = useNavigation();

// Helper para obtener rutas directas
const getDirectRoute = (name) => {
    const baseURL = window.location.origin;
    const routes = {
        'dashboard': `${baseURL}/dashboard`,
        'predictions': `${baseURL}/predictions`,
        'matches': `${baseURL}/matches`,
        'login': `${baseURL}/login`,
        'register': `${baseURL}/register`
    };
    return routes[name] || '#';
};

const featuredMatches = ref([
    { id: 1, league: 'Premier League', home_team: 'Arsenal', away_team: 'Manchester City', date: '2023-05-10', time: '20:00', home_logo: 'https://media.api-sports.io/football/teams/42.png', away_logo: 'https://media.api-sports.io/football/teams/50.png', venue: 'Emirates Stadium' },
    { id: 2, league: 'La Liga', home_team: 'Barcelona', away_team: 'Real Madrid', date: '2023-05-12', time: '21:00', home_logo: 'https://media.api-sports.io/football/teams/529.png', away_logo: 'https://media.api-sports.io/football/teams/541.png', venue: 'Camp Nou' },
    { id: 3, league: 'Serie A', home_team: 'Inter', away_team: 'Milan', date: '2023-05-15', time: '20:45', home_logo: 'https://media.api-sports.io/football/teams/505.png', away_logo: 'https://media.api-sports.io/football/teams/489.png', venue: 'San Siro' },
]);

const recentPredictions = ref([
    { id: 1, match: 'Liverpool vs Chelsea', prediction: 'Liverpool gana', success: true, odds: 1.75 },
    { id: 2, match: 'Bayern Munich vs Dortmund', prediction: 'Más de 2.5 goles', success: true, odds: 1.65 },
    { id: 3, match: 'PSG vs Marseille', prediction: 'Ambos equipos marcan', success: false, odds: 1.80 },
    { id: 4, match: 'Juventus vs Roma', prediction: 'Menos de 2.5 goles', success: true, odds: 1.95 },
]);

const upcomingAnalysis = ref([
    { id: 1, title: 'Análisis previo: Liverpool vs Chelsea', date: '2023-05-08', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
    { id: 2, title: 'La clave táctica del Manchester City', date: '2023-05-07', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
    { id: 3, title: 'El resurgir del Arsenal en la Premier', date: '2023-05-06', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
]);

// Stats
const stats = {
    aciertos: { valor: '78%', cambio: '5.2%', aumento: true },
    predicciones: { valor: '156', cambio: '12%', aumento: true },
    roi: { valor: '+15.3%', cambio: '3.8%', aumento: true },
    analisis: { valor: '45', cambio: '8.2%', aumento: true }
};

// Ejemplo de navegación programática
const handlePredictionClick = () => {
    navigate(PUBLIC_ROUTES.PREDICTIONS, {
        preserveState: true,
        preserveScroll: true,
        only: ['predictions']
    });
};

// Ejemplo de envío de formulario
const handleSubmit = async (formData) => {
    await post(AUTH_ROUTES.PROFILE, formData, {
        preserveScroll: true,
        onSuccess: () => {
            // Manejar éxito
        },
        onError: (errors) => {
            // Manejar errores
        }
    });
};
</script>

<template>
    <Head title="Dashboard" />
    
    <AppLayout>
        <div class="py-4 md:py-6">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
                <!-- Encabezado compacto -->
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-2">
                    <div>
                        <h1 class="text-2xl font-bold text-white leading-tight">Bienvenido a Sport Mind</h1>
                        <p class="text-sm text-gray-400">Tu plataforma de análisis deportivo con inteligencia artificial</p>
                    </div>
                </div>
                
                <!-- Stats Cards - Versión más compacta -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                    <!-- Aciertos -->
                    <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg p-3 transition-all duration-300 hover:border-red-500/30 group overflow-hidden">
                        <div class="flex items-start">
                            <div class="p-2 rounded-lg bg-red-500/10 mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-white font-medium text-sm">Aciertos</h3>
                                <div class="flex items-end">
                                    <span class="text-xl font-bold text-white">{{ stats.aciertos.valor }}</span>
                                    <span class="text-green-500 ml-2 text-xs flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                        </svg>
                                        {{ stats.aciertos.cambio }}
                                    </span>
                                </div>
                                <p class="text-gray-400 text-xs">Este mes</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Predicciones -->
                    <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg p-3 transition-all duration-300 hover:border-red-500/30 group overflow-hidden">
                        <div class="flex items-start">
                            <div class="p-2 rounded-lg bg-red-500/10 mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-white font-medium text-sm">Predicciones</h3>
                                <div class="flex items-end">
                                    <span class="text-xl font-bold text-white">{{ stats.predicciones.valor }}</span>
                                    <span class="text-green-500 ml-2 text-xs flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                        </svg>
                                        {{ stats.predicciones.cambio }}
                                    </span>
                                </div>
                                <p class="text-gray-400 text-xs">Este mes</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ROI -->
                    <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg p-3 transition-all duration-300 hover:border-red-500/30 group overflow-hidden">
                        <div class="flex items-start">
                            <div class="p-2 rounded-lg bg-red-500/10 mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-white font-medium text-sm">ROI</h3>
                                <div class="flex items-end">
                                    <span class="text-xl font-bold text-white">{{ stats.roi.valor }}</span>
                                    <span class="text-green-500 ml-2 text-xs flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                        </svg>
                                        {{ stats.roi.cambio }}
                                    </span>
                                </div>
                                <p class="text-gray-400 text-xs">Este mes</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Análisis -->
                    <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg p-3 transition-all duration-300 hover:border-red-500/30 group overflow-hidden">
                        <div class="flex items-start">
                            <div class="p-2 rounded-lg bg-red-500/10 mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-white font-medium text-sm">Análisis</h3>
                                <div class="flex items-end">
                                    <span class="text-xl font-bold text-white">{{ stats.analisis.valor }}</span>
                                    <span class="text-green-500 ml-2 text-xs flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                        </svg>
                                        {{ stats.analisis.cambio }}
                                    </span>
                                </div>
                                <p class="text-gray-400 text-xs">Este mes</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Partidos destacados -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-white">Partidos Destacados</h2>
                        <Link :href="$route('matches')" class="text-red-500 hover:text-red-400 text-sm font-medium flex items-center relative z-30">
                            Ver todos
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                    
                    <div class="space-y-3">
                        <div v-for="match in featuredMatches" :key="match.id" 
                            class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg p-3 transition-all duration-300 hover:bg-black/50">
                            <!-- Cabecera con liga -->
                            <div class="text-xs text-gray-400 mb-2">{{ match.league }}</div>
                            
                            <!-- Estructura del partido -->
                            <div class="flex items-center justify-between">
                                <!-- Equipo local -->
                                <div class="flex items-center w-[40%]">
                                    <div class="h-8 w-8 flex-shrink-0 mr-2">
                                        <img 
                                            :src="match.home_logo" 
                                            :alt="match.home_team" 
                                            class="h-full w-full object-contain"
                                            onerror="this.src='https://via.placeholder.com/32?text=🏠'; this.onerror=null;"
                                        />
                                    </div>
                                    <div class="text-sm text-white font-medium truncate">{{ match.home_team }}</div>
                                </div>
                                
                                <!-- Información central (fecha/hora) -->
                                <div class="text-center px-2">
                                    <div class="text-sm text-white font-medium mb-1">VS</div>
                                    <div class="text-xs text-gray-400">{{ match.time }}</div>
                                </div>
                                
                                <!-- Equipo visitante -->
                                <div class="flex items-center justify-end w-[40%]">
                                    <div class="text-sm text-white font-medium truncate text-right mr-2">{{ match.away_team }}</div>
                                    <div class="h-8 w-8 flex-shrink-0">
                                        <img 
                                            :src="match.away_logo" 
                                            :alt="match.away_team" 
                                            class="h-full w-full object-contain"
                                            onerror="this.src='https://via.placeholder.com/32?text=🏠'; this.onerror=null;"
                                        />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sede y botón -->
                            <div class="mt-2 flex items-center justify-between pt-2 border-t border-zinc-800/50">
                                <div class="text-xs text-gray-500">🏟️ {{ match.venue }}</div>
                                <Link :href="$route('predictions')" class="text-xs bg-red-500/10 text-red-500 px-3 py-1 rounded border border-red-500/20 hover:bg-red-500/20 transition-colors duration-200 relative z-30">
                                    Ver predicción
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sección de contenido dual -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                    <!-- Predicciones recientes -->
                    <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-bold text-white">Predicciones Recientes</h2>
                            <Link :href="$route('predictions')" class="text-red-500 hover:text-red-400 text-xs font-medium flex items-center relative z-30">
                                Ver todas
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                        
                        <div class="space-y-3">
                            <div v-for="prediction in recentPredictions" :key="prediction.id" class="border-b border-zinc-800/50 pb-3 last:border-0 last:pb-0">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-sm text-white font-medium">{{ prediction.match }}</h3>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ prediction.prediction }}</p>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-xs px-1.5 py-0.5 rounded mr-2" 
                                            :class="prediction.success ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'">
                                            {{ prediction.success ? 'Acierto' : 'Fallo' }}
                                        </span>
                                        <span class="text-white text-xs font-medium">{{ prediction.odds.toFixed(2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Análisis próximos -->
                    <div class="backdrop-blur-sm bg-black/40 border border-zinc-800 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-bold text-white">Análisis Recientes</h2>
                            <Link :href="$route('dashboard')" class="text-red-500 hover:text-red-400 text-xs font-medium flex items-center relative z-30">
                                Ver todos
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                        
                        <div class="space-y-3">
                            <div v-for="analysis in upcomingAnalysis" :key="analysis.id" class="flex border-b border-zinc-800/50 pb-3 last:border-0 last:pb-0">
                                <div class="w-8 h-8 bg-black/50 rounded-lg flex-shrink-0 flex items-center justify-center border border-zinc-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="analysis.icon" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm text-white font-medium">{{ analysis.title }}</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ analysis.date }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Banner de suscripción (versión compacta) -->
                <div class="backdrop-blur-sm bg-gradient-to-r from-black/40 via-black/60 to-black/40 border border-zinc-800 rounded-lg p-5 relative overflow-hidden">
                    <!-- Efectos visuales sutiles -->
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-red-500/10 rounded-full blur-3xl opacity-30"></div>
                    
                    <div class="relative z-10 md:flex items-center">
                        <div class="md:flex-1 mb-4 md:mb-0 md:mr-6">
                            <h2 class="text-xl font-bold text-white mb-2">Potencia tus decisiones deportivas</h2>
                            <p class="text-gray-300 text-sm">Accede a análisis exclusivos y predicciones avanzadas con nuestro plan premium.</p>
                        </div>
                        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                            <Link :href="$route('dashboard')" class="inline-flex justify-center items-center px-4 py-2 border border-red-500 text-red-500 bg-transparent hover:bg-red-500/10 rounded-lg transition-colors duration-200 text-xs font-medium relative z-30">
                                Conocer más
                            </Link>
                            <Link :href="$route('dashboard')" class="inline-flex justify-center items-center px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors duration-200 text-xs font-medium relative z-30">
                                Probar Premium
                            </Link>
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
