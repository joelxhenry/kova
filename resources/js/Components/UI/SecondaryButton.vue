<script setup>
defineProps({
    disabled: { type: Boolean, default: false },
    type: { type: String, default: 'button' },
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="inline-flex items-center justify-center gap-2.5 py-3 px-6 border border-foreground text-foreground font-semibold text-sm uppercase tracking-wider whitespace-nowrap transition-all duration-150 hover:bg-foreground hover:text-background active:translate-y-px focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50"
    >
        <slot />
    </button>
</template>
