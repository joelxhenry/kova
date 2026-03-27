<script setup>
defineProps({
    value: { type: String, default: '' },
});
</script>

<template>
    <label class="block text-sm font-medium text-foreground/70 mb-1.5">
        <slot>{{ value }}</slot>
    </label>
</template>
