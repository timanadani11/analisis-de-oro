<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Form from '@/Components/Form.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { AUTH_ROUTES } from '@/routes';

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
});

const handleSuccess = (response) => {
    // Mostrar mensaje de éxito
    console.log('Perfil actualizado con éxito', response);
};

const handleError = (errors) => {
    // Mostrar mensaje de error
    console.error('Error al actualizar el perfil', errors);
};
</script>

<template>
    <Head title="Editar Perfil" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="backdrop-blur-sm bg-black/60 border border-zinc-800 overflow-hidden shadow-xl rounded-xl p-6 relative">
                    <!-- Efecto de resplandor en la esquina -->
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-red-500/20 rounded-full blur-3xl opacity-30"></div>
                    
                    <h1 class="text-2xl font-bold text-white mb-6">Editar Perfil</h1>

                    <Form
                        :initial-data="{
                            name: user.name,
                            email: user.email,
                            username: user.username,
                            bio: user.bio || '',
                            favorite_sport: user.favorite_sport || '',
                            favorite_team: user.favorite_team || ''
                        }"
                        :submit-route="AUTH_ROUTES.PROFILE"
                        method="put"
                        @success="handleSuccess"
                        @error="handleError"
                    >
                        <template #default="{ form, errors, processing }">
                            <!-- Nombre -->
                            <div>
                                <InputLabel for="name" value="Nombre" class="text-gray-300 mb-1 text-sm" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full bg-zinc-800/70 border-zinc-700 focus:border-red-500 focus:ring-red-500 text-white"
                                    v-model="form.name"
                                    required
                                    autofocus
                                />
                                <InputError :message="errors.name" class="mt-2" />
                            </div>

                            <!-- Email -->
                            <div>
                                <InputLabel for="email" value="Email" class="text-gray-300 mb-1 text-sm" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full bg-zinc-800/70 border-zinc-700 focus:border-red-500 focus:ring-red-500 text-white"
                                    v-model="form.email"
                                    required
                                />
                                <InputError :message="errors.email" class="mt-2" />
                            </div>

                            <!-- Nombre de usuario -->
                            <div>
                                <InputLabel for="username" value="Nombre de usuario" class="text-gray-300 mb-1 text-sm" />
                                <TextInput
                                    id="username"
                                    type="text"
                                    class="mt-1 block w-full bg-zinc-800/70 border-zinc-700 focus:border-red-500 focus:ring-red-500 text-white"
                                    v-model="form.username"
                                    required
                                />
                                <InputError :message="errors.username" class="mt-2" />
                            </div>

                            <!-- Biografía -->
                            <div>
                                <InputLabel for="bio" value="Biografía" class="text-gray-300 mb-1 text-sm" />
                                <textarea
                                    id="bio"
                                    class="mt-1 block w-full bg-zinc-800/70 border-zinc-700 focus:border-red-500 focus:ring-red-500 text-white rounded-md shadow-sm"
                                    v-model="form.bio"
                                    rows="3"
                                ></textarea>
                                <InputError :message="errors.bio" class="mt-2" />
                            </div>

                            <!-- Deporte favorito -->
                            <div>
                                <InputLabel for="favorite_sport" value="Deporte favorito" class="text-gray-300 mb-1 text-sm" />
                                <select
                                    id="favorite_sport"
                                    class="mt-1 block w-full bg-zinc-800/70 border-zinc-700 focus:border-red-500 focus:ring-red-500 text-white rounded-md shadow-sm"
                                    v-model="form.favorite_sport"
                                >
                                    <option value="">Selecciona un deporte</option>
                                    <option value="football">Fútbol</option>
                                    <option value="basketball">Baloncesto</option>
                                    <option value="tennis">Tenis</option>
                                    <option value="formula1">Fórmula 1</option>
                                    <option value="baseball">Béisbol</option>
                                    <option value="mma">MMA</option>
                                    <option value="boxing">Boxeo</option>
                                    <option value="other">Otro</option>
                                </select>
                                <InputError :message="errors.favorite_sport" class="mt-2" />
                            </div>

                            <!-- Equipo favorito -->
                            <div>
                                <InputLabel for="favorite_team" value="Equipo favorito" class="text-gray-300 mb-1 text-sm" />
                                <TextInput
                                    id="favorite_team"
                                    type="text"
                                    class="mt-1 block w-full bg-zinc-800/70 border-zinc-700 focus:border-red-500 focus:ring-red-500 text-white"
                                    v-model="form.favorite_team"
                                />
                                <InputError :message="errors.favorite_team" class="mt-2" />
                            </div>

                            <div class="flex items-center justify-end mt-6">
                                <PrimaryButton
                                    class="bg-red-500 hover:bg-red-600"
                                    :class="{ 'opacity-25': processing }"
                                    :disabled="processing"
                                >
                                    {{ processing ? 'Guardando...' : 'Guardar cambios' }}
                                </PrimaryButton>
                            </div>
                        </template>
                    </Form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
