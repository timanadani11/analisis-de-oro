<script setup>
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Sidebar from '@/Components/Sidebar.vue';
import Footer from '@/Components/Footer.vue';

const showMobileMenu = ref(false);
const user = usePage().props.auth.user;

onMounted(() => {
    // Inicializar el fondo de partículas
    const canvas = document.getElementById('particles-canvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        // Partículas
        const particlesArray = [];
        const numberOfParticles = 100;

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 3 + 1;
                this.speedX = Math.random() * 1 - 0.5;
                this.speedY = Math.random() * 1 - 0.5;
                this.color = '#FF2D20';
                this.opacity = Math.random() * 0.5 + 0.1;
            }
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.x > canvas.width || this.x < 0) {
                    this.speedX = -this.speedX;
                }
                if (this.y > canvas.height || this.y < 0) {
                    this.speedY = -this.speedY;
                }
            }
            draw() {
                ctx.fillStyle = this.color;
                ctx.globalAlpha = this.opacity;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        function init() {
            for (let i = 0; i < numberOfParticles; i++) {
                particlesArray.push(new Particle());
            }
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < particlesArray.length; i++) {
                particlesArray[i].update();
                particlesArray[i].draw();
            }
            requestAnimationFrame(animate);
        }

        init();
        animate();

        window.addEventListener('resize', function() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
    }
});
</script>

<template>
    <div class="min-h-screen flex flex-col bg-gradient-to-br from-black via-zinc-900 to-black">
        <!-- Fondo de partículas -->
        <canvas id="particles-canvas" class="fixed inset-0 z-0"></canvas>
        
        <!-- Overlay con gradiente para mejorar legibilidad -->
        <div class="fixed inset-0 z-0 opacity-30 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-zinc-900 via-black to-black"></div>
        
        <div class="flex flex-1 relative z-10">
            <!-- Sidebar -->
            <Sidebar :show-mobile-menu="showMobileMenu" @close="showMobileMenu = false" />
            
            <!-- Contenido principal -->
            <div class="flex-1 flex flex-col min-h-screen lg:ml-72"> <!-- Offset for fixed sidebar -->
                <!-- Header móvil -->
                <header class="lg:hidden backdrop-blur-sm bg-black/60 border-b border-zinc-800 py-4 px-6 flex items-center justify-between z-20">
                    <button 
                        @click="showMobileMenu = !showMobileMenu" 
                        class="text-white focus:outline-none"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    
                    <div class="flex items-center">
                        <ApplicationLogo class="h-8 w-8 text-white" />
                        <div class="text-xl ml-2 font-bold text-white tracking-tight">
                            Sport <span class="text-[#FF2D20]">Mind</span>
                        </div>
                    </div>
                    
                    <div class="w-6"></div> <!-- Espaciador para balancear -->
                </header>
                
                <!-- Contenido de la página -->
                <main class="flex-1 px-4 lg:px-8 py-6 overflow-x-hidden">
                    <slot />
                </main>
                
                <!-- Footer -->
                <Footer />
            </div>
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
