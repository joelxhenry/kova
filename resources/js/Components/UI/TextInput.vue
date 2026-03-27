<script setup>
const model = defineModel({ type: String });

defineProps({
    type: { type: String, default: 'text' },
    error: { type: String, default: '' },
});
</script>

<template>
    <input
        v-model="model"
        :type="type"
        class="w-full h-12 md:h-14 bg-input border border-border px-4 text-base text-foreground placeholder:text-muted-foreground outline-none transition-colors duration-150 focus:border-accent disabled:cursor-not-allowed disabled:opacity-50"
        :class="{ 'border-accent': error }"
    />
</template>
