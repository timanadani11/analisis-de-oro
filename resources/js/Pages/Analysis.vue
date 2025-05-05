<script setup>
import { Head } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
</script>

<template>
    <Head title="Análisis - Sport Mind" />
    
    <MainLayout>
        <!-- Encabezado de la página -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Análisis Deportivos</h1>
            <p class="text-gray-400">Análisis detallados para tus apuestas deportivas</p>
        </div>
        
        <!-- Contenido principal de la página -->
        <div class="backdrop-blur-sm bg-black/60 border border-zinc-800 rounded-xl p-6 relative overflow-hidden">
            <!-- Efecto de resplandor en la esquina -->
            <div class="absolute -top-20 -right-20 w-40 h-40 bg-[#FF2D20]/20 rounded-full blur-3xl opacity-30"></div>
            
            <div class="relative z-10">
                <div class="text-center py-12">
                    <div class="mx-auto w-16 h-16 bg-[#FF2D20]/20 rounded-full flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-[#FF2D20]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-4">Página de Análisis</h2>
                    <p class="text-gray-400 max-w-2xl mx-auto mb-6">
                        Aquí encontrarás todos los análisis deportivos detallados, con estadísticas avanzadas, tendencias y factores clave para cada partido.
                    </p>
                    <p class="text-[#FF2D20]">¡Contenido próximamente!</p>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
