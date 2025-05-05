<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { PUBLIC_ROUTES, ADMIN_ROUTES } from '@/routes';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    isAdmin: {
        type: Boolean,
        default: false
    }
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    const routeName = props.isAdmin ? ADMIN_ROUTES.LOGIN : PUBLIC_ROUTES.LOGIN;
    form.post(routeName, {
        onFinish: () => form.reset('password'),
        preserveScroll: true,
    });
};

onMounted(() => {
    // Inicializar el fondo de partículas si existe el elemento
    const canvas = document.getElementById('particles-canvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        // Partículas
        const particlesArray = [];
        const numberOfParticles = 80;

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2 + 1;
                this.speedX = Math.random() * 0.8 - 0.4;
                this.speedY = Math.random() * 0.8 - 0.4;
                this.color = '#FF2D20';
                this.opacity = Math.random() * 0.4 + 0.1;
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
    <Head :title="props.isAdmin ? 'Acceso Administración - Sport Mind' : 'Iniciar Sesión - Sport Mind'" />
    
    <div class="min-h-screen flex flex-col items-center justify-center relative bg-gradient-to-br from-black via-zinc-900 to-black px-4">
        <!-- Fondo de partículas -->
        <canvas id="particles-canvas" class="absolute inset-0 z-0"></canvas>
        
        <!-- Overlay con gradiente para mejorar legibilidad -->
        <div class="absolute inset-0 z-0 opacity-30 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-zinc-900 via-black to-black"></div>
        
        <div class="w-full max-w-md relative z-10">
            <!-- Logo más compacto -->
            <div class="mb-6 text-center">
                <Link :href="props.isAdmin ? ADMIN_ROUTES.LOGIN : '/'" class="group inline-block">
                    <div class="h-20 w-20 relative mx-auto">
                        <div class="absolute inset-0 bg-red-500 rounded-md opacity-60 blur-md group-hover:opacity-100 group-hover:blur-lg transition-all duration-500 animate-pulse"></div>
                        <ApplicationLogo class="h-20 w-20 text-white relative z-10 hover:scale-105 transition-transform duration-300" />
                    </div>
                    <div class="text-2xl text-center mt-2 font-extrabold text-white tracking-tight">
                        Sport <span class="text-red-500">Mind</span>
                    </div>
                    <div v-if="props.isAdmin" class="text-sm text-gray-400 mt-1">Panel de Administración</div>
                </Link>
            </div>

            <div class="backdrop-blur-sm bg-black/60 border border-zinc-800 p-6 shadow-xl rounded-xl overflow-hidden relative">
                <!-- Efecto brillante en el borde superior -->
                <div class="absolute -top-px left-20 right-20 h-px bg-gradient-to-r from-transparent via-red-500 to-transparent"></div>
                
                <!-- Resplandor en la esquina -->
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-red-500/20 rounded-full blur-3xl opacity-50"></div>
                
                <div v-if="status" class="mb-4 font-medium text-xs text-red-500 p-2 bg-red-500/10 rounded-lg border border-red-500/20">
                    {{ status }}
                </div>

                <div class="text-center mb-4">
                    <h1 class="text-xl font-bold text-white mb-1">{{ props.isAdmin ? 'Acceso Administración' : '¡Bienvenido de nuevo!' }}</h1>
                    <p class="text-gray-400 text-sm">{{ props.isAdmin ? 'Ingrese sus credenciales de administrador' : 'Accede a tu cuenta para continuar' }}</p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="relative">
                        <InputLabel for="email" value="Email" class="text-gray-300 mb-1 text-sm inline-block" />
                        
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 group-focus-within:text-red-500 transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                </svg>
                            </div>
                            
                            <TextInput
                                id="email"
                                type="email"
                                class="pl-10 block w-full bg-zinc-800/70 border-zinc-700 focus:border-red-500 focus:ring-red-500 text-white placeholder-gray-500 transition-all duration-200 text-sm h-9"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="nombre@ejemplo.com"
                            />
                        </div>

                        <InputError class="mt-1 text-xs" :message="form.errors.email" />
                    </div>

                    <div class="relative">
                        <div class="flex items-center justify-between mb-1">
                            <InputLabel for="password" value="Contraseña" class="text-gray-300 text-sm inline-block" />
                            <Link
                                v-if="canResetPassword"
                                :href="PUBLIC_ROUTES.FORGOT_PASSWORD"
                                class="text-xs text-red-500 hover:text-red-400 transition-colors duration-200 hover:underline"
                            >
                                ¿Olvidaste tu contraseña?
                            </Link>
                        </div>

                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 group-focus-within:text-red-500 transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            
                            <TextInput
                                id="password"
                                type="password"
                                class="pl-10 block w-full bg-zinc-800/70 border-zinc-700 focus:border-red-500 focus:ring-red-500 text-white placeholder-gray-500 transition-all duration-200 text-sm h-9"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                            />
                        </div>

                        <InputError class="mt-1 text-xs" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center">
                        <label class="flex items-center cursor-pointer group">
                            <Checkbox 
                                name="remember" 
                                v-model:checked="form.remember" 
                                class="bg-zinc-800 border-zinc-700 text-red-500 rounded focus:ring-red-500 h-4 w-4" 
                            />
                            <span class="ms-2 text-xs text-gray-300 group-hover:text-white transition-colors duration-200">Recordarme</span>
                        </label>
                    </div>

                    <div class="pt-1">
                        <PrimaryButton 
                            class="w-full justify-center py-2 bg-red-500 hover:bg-red-600 transition-all duration-200 group text-sm" 
                            :class="{ 'opacity-70 cursor-not-allowed': form.processing }" 
                            :disabled="form.processing"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 transition-transform duration-200 group-hover:translate-x-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" />
                            </svg>
                            <span v-if="form.processing">Procesando...</span>
                            <span v-else>Iniciar Sesión</span>
                        </PrimaryButton>
                    </div>
                </form>

                <div v-if="!props.isAdmin" class="mt-4 text-center border-t border-zinc-800 pt-4">
                    <span class="text-gray-400 text-xs">¿No tienes una cuenta?</span>
                    <Link
                        :href="PUBLIC_ROUTES.REGISTER"
                        class="ml-1 text-red-500 hover:text-red-400 transition-colors duration-200 text-xs font-medium hover:underline"
                    >
                        Registrarse
                    </Link>
                </div>

                <div v-if="props.isAdmin" class="mt-4 text-center">
                    <Link
                        :href="PUBLIC_ROUTES.DASHBOARD"
                        class="text-sm text-gray-400 hover:text-red-500 transition-colors duration-200"
                    >
                        Volver al sitio principal
                    </Link>
                </div>
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
</style>
