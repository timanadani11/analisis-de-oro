<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    setTimeout(() => passwordInput.value.focus(), 250);
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <div class="text-gray-300">
            <h3 class="text-lg font-medium text-gray-200">Eliminar cuenta</h3>
            <p class="mt-1">
                Una vez que tu cuenta sea eliminada, todos sus recursos y datos serán eliminados permanentemente. Antes de eliminar tu cuenta, por favor descarga cualquier dato o información que desees conservar.
            </p>
        </div>

        <div class="flex justify-start">
            <button 
                @click="confirmUserDeletion" 
                class="inline-flex items-center px-4 py-2 bg-red-600/80 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Eliminar cuenta
            </button>
        </div>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="backdrop-blur-sm bg-black/80 p-6 rounded-lg relative overflow-hidden">
                <!-- Resplandor en la esquina -->
                <div class="absolute -top-20 -right-20 w-40 h-40 bg-red-500/20 rounded-full blur-3xl opacity-30"></div>
                
                <h2 class="text-lg font-medium text-white mb-6">
                    ¿Estás seguro de que quieres eliminar tu cuenta?
                </h2>

                <p class="mt-1 text-sm text-gray-400 mb-6">
                    Una vez que tu cuenta sea eliminada, todos sus recursos y datos serán eliminados permanentemente. Por favor, ingresa tu contraseña para confirmar que deseas eliminar tu cuenta de forma permanente.
                </p>

                <div class="mt-6">
                    <InputLabel for="password" value="Contraseña" class="sr-only" />

                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-focus-within:text-red-500 transition-colors duration-200" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        
                        <TextInput
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="pl-10 block w-full bg-zinc-800/70 border-zinc-700 focus:border-red-500 focus:ring-red-500 text-white placeholder-gray-500 transition-all duration-200"
                            placeholder="Introduce tu contraseña"
                            @keyup.enter="deleteUser"
                        />
                    </div>

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center px-4 py-2 bg-zinc-800 border border-zinc-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 mr-3"
                        @click="closeModal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                        :class="{ 'opacity-70 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Eliminar cuenta
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
