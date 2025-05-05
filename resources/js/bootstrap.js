import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Asegurarnos de que se incluye el token CSRF en todas las solicitudes
const csrfToken = document.head.querySelector('meta[name="csrf-token"]');

if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Agregar un interceptor para ver qué está pasando con las solicitudes
window.axios.interceptors.request.use(
    config => {
        console.log('Enviando solicitud:', config);
        return config;
    },
    error => {
        console.error('Error en la solicitud:', error);
        return Promise.reject(error);
    }
);

window.axios.interceptors.response.use(
    response => {
        console.log('Respuesta recibida:', response);
        return response;
    },
    error => {
        console.error('Error en la respuesta:', error);
        return Promise.reject(error);
    }
);
