<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
</script>

<template>
    <div class="min-h-screen flex flex-col items-center pt-6 sm:justify-center sm:pt-0 relative bg-gradient-to-br from-black via-zinc-900 to-black">
        <!-- Fondo de partículas -->
        <canvas id="particles-canvas" class="absolute inset-0 z-0"></canvas>
        
        <!-- Overlay con gradiente para mejorar legibilidad -->
        <div class="absolute inset-0 z-0 opacity-30 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-zinc-900 via-black to-black"></div>
        
        <!-- Contenido principal -->
        <div class="relative z-10">
            <Link href="/" class="group block">
                <div class="h-28 w-28 relative mx-auto">
                    <div class="absolute inset-0 bg-[#FF2D20] rounded-md opacity-60 blur-md group-hover:opacity-100 group-hover:blur-lg transition-all duration-500 animate-pulse"></div>
                    <ApplicationLogo class="h-28 w-28 text-white relative z-10 hover:scale-105 transition-transform duration-300" />
                </div>
                <div class="text-3xl text-center mt-4 font-extrabold text-white tracking-tight">
                    Sport <span class="text-[#FF2D20]">Mind</span>
                </div>
            </Link>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 relative z-10">
            <slot />
        </div>
    </div>
</template>

<style scoped>
@keyframes pulse {
  0%, 100% { opacity: 0.6; }
  50% { opacity: 1; }
}
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes float {
  0% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
  100% { transform: translateY(0px); }
}
.animate-float {
  animation: float 6s ease-in-out infinite;
}
</style>
