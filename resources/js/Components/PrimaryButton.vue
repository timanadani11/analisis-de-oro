<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center px-4 py-2 bg-[#FF2D20] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#CC2419] focus:bg-[#CC2419] active:bg-[#CC2419] focus:outline-none focus:ring-2 focus:ring-[#FF2D20] focus:ring-offset-2 transition ease-in-out duration-150"
    >
        <slot />
    </button>
</template>
