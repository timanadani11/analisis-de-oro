<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    users: Object,
});

const search = ref('');

const filteredUsers = computed(() => {
    if (!search.value) return props.users.data;
    
    const searchLower = search.value.toLowerCase();
    return props.users.data.filter(user => 
        user.name.toLowerCase().includes(searchLower) || 
        user.email.toLowerCase().includes(searchLower) ||
        (user.username && user.username.toLowerCase().includes(searchLower))
    );
});

// Función para obtener las iniciales del nombre del usuario
const getInitials = (name) => {
    if (!name) return '';
    return name.split(' ').map(n => n[0]).join('').toUpperCase();
};

// Función para generar un color de fondo basado en el nombre
const getAvatarColor = (name) => {
    if (!name) return 'bg-red-500';
    
    const colors = [
        'bg-red-500', 'bg-blue-500', 'bg-green-500', 'bg-yellow-500', 
        'bg-purple-500', 'bg-pink-500', 'bg-indigo-500', 'bg-teal-500'
    ];
    
    // Generar un índice basado en la suma de los códigos ASCII de los caracteres del nombre
    const sum = name.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
    return colors[sum % colors.length];
};

// Función para dar formato a la fecha
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('es-ES', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AdminLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="backdrop-blur-sm bg-black/60 border border-zinc-800 overflow-hidden shadow-xl rounded-xl p-6 relative">
                    <!-- Efecto de resplandor en la esquina -->
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-red-500/20 rounded-full blur-3xl opacity-30"></div>
                    
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                        <h1 class="text-3xl font-bold text-white mb-4 md:mb-0 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            Gestión de Usuarios
                        </h1>
                        
                        <div class="flex items-center space-x-4">
                            <div class="relative">
                                <input 
                                    type="text" 
                                    v-model="search" 
                                    placeholder="Buscar usuario..." 
                                    class="pl-10 pr-4 py-2 rounded-lg bg-zinc-800/70 border-zinc-700 focus:border-red-500 focus:ring-red-500 text-white placeholder-gray-500 transition-all duration-200"
                                />
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            
                            <button class="inline-flex items-center px-4 py-2 bg-red-600/80 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Nuevo Usuario
                            </button>
                        </div>
                    </div>
                    
                    <!-- Tabla de usuarios -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-800">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Usuario</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Correo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Rol</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Registro</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800">
                                <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-zinc-900/30 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div :class="[getAvatarColor(user.name), 'flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center text-white font-bold']">
                                                {{ getInitials(user.name) }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-white">{{ user.name }}</div>
                                                <div class="text-sm text-gray-400">{{ user.username || 'Sin username' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-white">{{ user.email }}</div>
                                        <div v-if="user.email_verified_at" class="text-xs text-green-500">Verificado</div>
                                        <div v-else class="text-xs text-yellow-500">No verificado</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="user.role === 'admin'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-600/30 text-red-300">
                                            Administrador
                                        </span>
                                        <span v-else-if="user.is_premium" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-600/30 text-yellow-300">
                                            Premium
                                        </span>
                                        <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-zinc-600/30 text-gray-300">
                                            Usuario
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        {{ formatDate(user.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="user.active" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-600/30 text-green-300">
                                            Activo
                                        </span>
                                        <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-600/30 text-red-300">
                                            Inactivo
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end items-center space-x-2">
                                            <button class="text-indigo-500 hover:text-indigo-400 transition-colors duration-150">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            <button class="text-blue-500 hover:text-blue-400 transition-colors duration-150">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button class="text-red-500 hover:text-red-400 transition-colors duration-150">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Mensaje si no hay usuarios -->
                                <tr v-if="filteredUsers.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto mb-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <p>No se encontraron usuarios con esos criterios</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginación -->
                    <div v-if="users.data.length > 0" class="mt-6 flex items-center justify-between">
                        <div class="text-sm text-gray-400">
                            Mostrando {{ users.from }} a {{ users.to }} de {{ users.total }} usuarios
                        </div>
                        
                        <div class="flex space-x-1">
                            <Link 
                                v-for="link in users.links" 
                                :key="link.label" 
                                :href="link.url || '#'" 
                                :class="[
                                    'px-3 py-1 rounded transition-colors duration-150',
                                    link.active ? 'bg-red-600/60 text-white' : 'bg-zinc-800 text-gray-400 hover:bg-zinc-700',
                                    !link.url && 'opacity-50 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            >
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
