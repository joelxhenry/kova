<script setup>
defineProps({
    value: { type: String, default: '' },
});
</script>

<template>
    <label class="block text-sm font-medium tracking-wider uppercase text-muted-foreground mb-2">
        <slot>{{ value }}</slot>
    </label>
</template>
