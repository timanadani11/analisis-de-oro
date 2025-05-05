<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PUBLIC_ROUTES } from '@/routes';

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    favorite_sport: '',
    terms: false,
});

const submit = () => {
    form.post(PUBLIC_ROUTES.REGISTER, {
        onFinish: () => form.reset('password', 'password_confirmation'),
        preserveScroll: true,
    });
};

const sports = [
    { value: 'football', label: 'Fútbol' },
    { value: 'basketball', label: 'Baloncesto' },
    { value: 'tennis', label: 'Tenis' },
    { value: 'formula1', label: 'Fórmula 1' },
    { value: 'baseball', label: 'Béisbol' },
    { value: 'mma', label: 'MMA' },
    { value: 'boxing', label: 'Boxeo' },
    { value: 'other', label: 'Otro' },
];
</script>

<template>
    <Head title="Registro - Sport Mind" />
    
    <div class="min-h-screen flex flex-col items-center justify-center relative bg-gradient-to-br from-black via-zinc-900 to-black px-4">
        <!-- Overlay con gradiente para mejorar legibilidad -->
        <div class="absolute inset-0 z-0 opacity-30 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-zinc-900 via-black to-black"></div>
        
        <div class="w-full max-w-md relative z-10">
            <!-- Logo más compacto -->
            <div class="mb-5 text-center">
                <Link href="/" class="group inline-block">
                    <div class="h-16 w-16 relative mx-auto">
                        <div class="absolute inset-0 bg-red-500 rounded-md opacity-60 blur-md group-hover:opacity-100 group-hover:blur-lg transition-all duration-500 animate-pulse"></div>
                        <ApplicationLogo class="h-16 w-16 text-white relative z-10 hover:scale-105 transition-transform duration-300" />
                    </div>
                    <div class="text-xl text-center mt-1 font-extrabold text-white tracking-tight">
                        Sport <span class="text-red-500">Mind</span>
                    </div>
                </Link>
            </div>

            <div class="backdrop-blur-sm bg-black/60 border border-zinc-800 p-5 shadow-xl rounded-xl overflow-hidden relative">
                <!-- Efecto brillante en el borde superior -->
                <div class="absolute -top-px left-20 right-20 h-px bg-gradient-to-r from-transparent via-red-500 to-transparent"></div>
                
                <!-- Resplandor en la esquina -->
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-red-500/20 rounded-full blur-3xl opacity-50"></div>
                
                <div class="text-center mb-4">
                    <h1 class="text-xl font-bold text-white mb-1">Crea tu cuenta</h1>
                    <p class="text-gray-400 text-xs">Únete a la comunidad de Sport Mind</p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- Nombre completo -->
                        <div class="relative">
                            <InputLabel for="name" value="Nombre completo" class="text-gray-300 mb-1 text-xs inline-block" />
                            
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 group-focus-within:text-red-500 transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="pl-10 block w-full bg-zinc-800/70 border-zinc-700 focus:border-red-500 focus:ring-red-500 text-white placeholder-gray-500 transition-all duration-200 text-xs h-8"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="name"
                                    placeholder="Tu nombre completo"
                                />
                            </div>

                            <InputError class="mt-1 text-xs" :message="form.errors.name" />
                        </div>

                        <!-- Nombre de usuario -->
                        <div class="relative">
                            <InputLabel for="username" value="Nombre de usuario" class="text-gray-300 mb-1 text-xs inline-block" />
                            
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 group-focus-within:text-red-500 transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                
                                <TextInput
                                    id="username"
                                    type="text"
                                    class="pl-10 block w-full bg-zinc-800/70 border-zinc-700 focus:border-red-500 focus:ring-red-500 text-white placeholder-gray-500 transition-all duration-200 text-xs h-8"
                                    v-model="form.username"
                                    required
                                    autocomplete="username"
                                    placeholder="username123"
                                />
                            </div>

                            <InputError class="mt-1 text-xs" :message="form.errors.username" />
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="relative">
                        <InputLabel for="email" value="Email" class="text-gray-300 mb-1 text-xs inline-block" />
                        
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
                                class="pl-10 block w-full bg-zinc-800/70 border-zinc-700 focus:border-red-500 focus:ring-red-500 text-white placeholder-gray-500 transition-all duration-200 text-xs h-8"
                                v-model="form.email"
                                required
                                autocomplete="username"
                                placeholder="email@ejemplo.com"
                            />
                        </div>

                        <InputError class="mt-1 text-xs" :message="form.errors.email" />
                    </div>

                    <div class="relative">
                        <InputLabel for="favorite_sport" value="Deporte favorito" class="text-gray-300 mb-1 text-xs inline-block" />
                        
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 group-focus-within:text-red-500 transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            
                            <select
                                id="favorite_sport"
                                v-model="form.favorite_sport"
                                class="pl-10 block w-full rounded-md bg-zinc-800/70 border-zinc-700 text-white focus:border-red-500 focus:ring-red-500 transition-all duration-200 text-xs h-8"
                            >
                                <option value="" disabled>Selecciona un deporte</option>
                                <option v-for="sport in sports" :key="sport.value" :value="sport.value">
                                    {{ sport.label }}
                                </option>
                            </select>
                        </div>

                        <InputError class="mt-1 text-xs" :message="form.errors.favorite_sport" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- Contraseña -->
                        <div class="relative">
                            <InputLabel for="password" value="Contraseña" class="text-gray-300 mb-1 text-xs inline-block" />
                            
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 group-focus-within:text-red-500 transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                
                                <TextInput
                                    id="password"
                                    type="password"
                                    class="pl-10 block w-full bg-zinc-800/70 border-zinc-700 focus:border-red-500 focus:ring-red-500 text-white placeholder-gray-500 transition-all duration-200 text-xs h-8"
                                    v-model="form.password"
                                    required
                                    autocomplete="new-password"
                                    placeholder="••••••••"
                                />
                            </div>

                            <InputError class="mt-1 text-xs" :message="form.errors.password" />
                        </div>

                        <!-- Confirmar contraseña -->
                        <div class="relative">
                            <InputLabel for="password_confirmation" value="Confirmar" class="text-gray-300 mb-1 text-xs inline-block" />
                            
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 group-focus-within:text-red-500 transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    class="pl-10 block w-full bg-zinc-800/70 border-zinc-700 focus:border-red-500 focus:ring-red-500 text-white placeholder-gray-500 transition-all duration-200 text-xs h-8"
                                    v-model="form.password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    placeholder="••••••••"
                                />
                            </div>

                            <InputError class="mt-1 text-xs" :message="form.errors.password_confirmation" />
                        </div>
                    </div>

                    <!-- Términos y condiciones -->
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input 
                                id="terms" 
                                name="terms" 
                                type="checkbox"
                                v-model="form.terms"
                                class="h-3 w-3 rounded border-zinc-700 bg-zinc-800/70 text-red-500 focus:ring-red-500"
                            />
                        </div>
                        <div class="ml-2 text-xs">
                            <label for="terms" class="text-gray-300">
                                Acepto los <a href="#" class="text-red-500 hover:text-red-400 underline">términos y condiciones</a> y la <a href="#" class="text-red-500 hover:text-red-400 underline">política de privacidad</a>
                            </label>
                            <InputError class="mt-1 text-xs" :message="form.errors.terms" />
                        </div>
                    </div>

                    <div class="pt-1">
                        <PrimaryButton 
                            class="w-full justify-center py-2 bg-red-500 hover:bg-red-600 transition-all duration-200 group text-xs" 
                            :class="{ 'opacity-70 cursor-not-allowed': form.processing }" 
                            :disabled="form.processing"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 transition-transform duration-200 group-hover:translate-x-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd" />
                            </svg>
                            <span v-if="form.processing">Procesando...</span>
                            <span v-else>Crear cuenta</span>
                        </PrimaryButton>
                    </div>
                </form>

                <div class="mt-4 text-center border-t border-zinc-800 pt-4">
                    <span class="text-gray-400 text-xs">¿Ya tienes una cuenta?</span>
                    <Link
                        :href="PUBLIC_ROUTES.LOGIN"
                        class="ml-1 text-red-500 hover:text-red-400 transition-colors duration-200 text-xs font-medium hover:underline"
                    >
                        Iniciar sesión
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
