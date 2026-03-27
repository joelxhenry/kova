<script setup>
defineProps({
    disabled: { type: Boolean, default: false },
    type: { type: String, default: 'submit' },
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="relative inline-flex items-center justify-center gap-2.5 py-3 px-0 text-accent font-semibold text-sm uppercase tracking-wider whitespace-nowrap transition-all duration-150 active:translate-y-px focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50"
    >
        <slot />
        <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-accent transition-transform duration-150 scale-x-100 hover:scale-x-110 origin-left" />
    </button>
</template>
