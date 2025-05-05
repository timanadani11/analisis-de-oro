<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const favoriteSport = ref(user.favorite_sport || '');
const username = ref(user.username || '');

const form = useForm({
    name: user.name,
    username: username.value,
    email: user.email,
    favorite_sport: favoriteSport.value,
});

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
    <section>
        <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-5">
            <div>
                <InputLabel for="name" value="Nombre" class="text-gray-300 mb-2 inline-block" />
                
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-focus-within:text-[#FF2D20] transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    
                    <TextInput
                        id="name"
                        type="text"
                        class="pl-10 block w-full bg-zinc-800/70 border-zinc-700 focus:border-[#FF2D20] focus:ring-[#FF2D20] text-white placeholder-gray-500 transition-all duration-200"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Tu nombre completo"
                    />
                </div>

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="username" value="Nombre de usuario" class="text-gray-300 mb-2 inline-block" />
                
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-focus-within:text-[#FF2D20] transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    
                    <TextInput
                        id="username"
                        type="text"
                        class="pl-10 block w-full bg-zinc-800/70 border-zinc-700 focus:border-[#FF2D20] focus:ring-[#FF2D20] text-white placeholder-gray-500 transition-all duration-200"
                        v-model="form.username"
                        required
                        autocomplete="username"
                    />
                </div>

                <InputError class="mt-2" :message="form.errors.username" />
            </div>

            <div>
                <InputLabel for="email" value="Email" class="text-gray-300 mb-2 inline-block" />
                
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-focus-within:text-[#FF2D20] transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                    </div>
                    
                    <TextInput
                        id="email"
                        type="email"
                        class="pl-10 block w-full bg-zinc-800/70 border-zinc-700 focus:border-[#FF2D20] focus:ring-[#FF2D20] text-white placeholder-gray-500 transition-all duration-200"
                        v-model="form.email"
                        required
                        autocomplete="username"
                    />
                </div>

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="favorite_sport" value="Deporte favorito" class="text-gray-300 mb-2 inline-block" />
                
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-focus-within:text-[#FF2D20] transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    
                    <select
                        id="favorite_sport"
                        v-model="form.favorite_sport"
                        class="pl-10 block w-full rounded-md bg-zinc-800/70 border-zinc-700 text-white focus:border-[#FF2D20] focus:ring-[#FF2D20] transition-all duration-200"
                    >
                        <option value="" disabled>Selecciona un deporte</option>
                        <option v-for="sport in sports" :key="sport.value" :value="sport.value">
                            {{ sport.label }}
                        </option>
                    </select>
                </div>

                <InputError class="mt-2" :message="form.errors.favorite_sport" />
            </div>

            <div v-if="props.mustVerifyEmail && user.email_verified_at === null" class="mt-2 text-sm">
                <p class="text-gray-400">
                    Tu email no está verificado.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="text-[#FF2D20] hover:text-[#FF2D20]/80 hover:underline rounded-md focus:outline-none"
                    >
                        Haz clic aquí para reenviar el correo de verificación.
                    </Link>
                </p>

                <div v-show="props.status === 'verification-link-sent'" class="mt-2 text-[#FF2D20] text-sm">
                    Se ha enviado un nuevo enlace de verificación a tu correo electrónico.
                </div>
            </div>

            <div class="flex items-center justify-end mt-6">
                <p v-if="form.recentlySuccessful" class="text-sm text-gray-300 mr-3">
                    Guardado.
                </p>

                <PrimaryButton 
                    :class="{ 'opacity-70 cursor-not-allowed': form.processing }" 
                    :disabled="form.processing"
                    class="bg-[#FF2D20] hover:bg-[#CC2419] transition-all duration-200 group flex items-center"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span v-if="form.processing">Guardando...</span>
                    <span v-else>Guardar</span>
                </PrimaryButton>
            </div>
        </form>
    </section>
</template>
