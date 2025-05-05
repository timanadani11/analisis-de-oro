<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <form @submit.prevent="updatePassword" class="space-y-5">
            <div>
                <InputLabel for="current_password" value="Contraseña actual" class="text-gray-300 mb-2 inline-block" />
                
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-focus-within:text-[#FF2D20] transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    
                    <TextInput
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        type="password"
                        class="pl-10 block w-full bg-zinc-800/70 border-zinc-700 focus:border-[#FF2D20] focus:ring-[#FF2D20] text-white placeholder-gray-500 transition-all duration-200"
                        placeholder="••••••••"
                        autocomplete="current-password"
                    />
                </div>

                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <div>
                <InputLabel for="password" value="Nueva contraseña" class="text-gray-300 mb-2 inline-block" />
                
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-focus-within:text-[#FF2D20] transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="pl-10 block w-full bg-zinc-800/70 border-zinc-700 focus:border-[#FF2D20] focus:ring-[#FF2D20] text-white placeholder-gray-500 transition-all duration-200"
                        placeholder="••••••••"
                        autocomplete="new-password"
                    />
                </div>

                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirmar contraseña" class="text-gray-300 mb-2 inline-block" />
                
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-focus-within:text-[#FF2D20] transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="pl-10 block w-full bg-zinc-800/70 border-zinc-700 focus:border-[#FF2D20] focus:ring-[#FF2D20] text-white placeholder-gray-500 transition-all duration-200"
                        placeholder="••••••••"
                        autocomplete="new-password"
                    />
                </div>

                <InputError :message="form.errors.password_confirmation" class="mt-2" />
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
