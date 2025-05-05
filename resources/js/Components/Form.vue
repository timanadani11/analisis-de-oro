<script setup>
import { ref } from 'vue';
import { useNavigation } from '@/composables/useNavigation';
import { AUTH_ROUTES } from '@/routes';

const props = defineProps({
    initialData: {
        type: Object,
        default: () => ({})
    },
    submitRoute: {
        type: String,
        required: true
    },
    method: {
        type: String,
        default: 'post',
        validator: (value) => ['post', 'put', 'patch'].includes(value)
    }
});

const emit = defineEmits(['success', 'error']);

const { post, put } = useNavigation();

const form = ref({
    ...props.initialData
});

const processing = ref(false);
const errors = ref({});

const submit = async () => {
    processing.value = true;
    errors.value = {};

    try {
        const data = { ...form.value };
        const response = props.method === 'post' 
            ? await post(props.submitRoute, data, {
                preserveScroll: true,
                onSuccess: (response) => {
                    emit('success', response);
                },
                onError: (errors) => {
                    emit('error', errors);
                    errors.value = errors;
                }
            })
            : await put(props.submitRoute, data, {
                preserveScroll: true,
                onSuccess: (response) => {
                    emit('success', response);
                },
                onError: (errors) => {
                    emit('error', errors);
                    errors.value = errors;
                }
            });

        return response;
    } catch (error) {
        errors.value = error.response?.data?.errors || { general: ['Ha ocurrido un error inesperado'] };
        emit('error', errors.value);
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">
        <slot 
            :form="form" 
            :errors="errors" 
            :processing="processing"
        ></slot>
    </form>
</template> 