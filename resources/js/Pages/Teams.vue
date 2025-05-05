<script setup>
import { Head } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
</script>

<template>
    <Head title="Equipos - Sport Mind" />
    
    <MainLayout>
        <!-- Encabezado de la página -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Equipos</h1>
            <p class="text-gray-400">Información detallada sobre los equipos y jugadores</p>
        </div>
        
        <!-- Contenido principal de la página -->
        <div class="backdrop-blur-sm bg-black/60 border border-zinc-800 rounded-xl p-6 relative overflow-hidden">
            <!-- Efecto de resplandor en la esquina -->
            <div class="absolute -top-20 -right-20 w-40 h-40 bg-[#FF2D20]/20 rounded-full blur-3xl opacity-30"></div>
            
            <div class="relative z-10">
                <div class="text-center py-12">
                    <div class="mx-auto w-16 h-16 bg-[#FF2D20]/20 rounded-full flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-[#FF2D20]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-4">Página de Equipos</h2>
                    <p class="text-gray-400 max-w-2xl mx-auto mb-6">
                        Aquí encontrarás información detallada sobre todos los equipos, sus jugadores, estadísticas históricas y rendimiento actual.
                    </p>
                    <p class="text-[#FF2D20]">¡Contenido próximamente!</p>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
