<script setup>
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    gctStatus: { type: Object, required: true },
});

const { formatJMD } = useCurrencyFormatter();
</script>

<template>
    <div class="border border-border p-6 md:p-8">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-xs uppercase tracking-wider text-muted-foreground">GCT Threshold</h2>
            <span v-if="gctStatus.isRegistered" class="text-xs font-mono uppercase tracking-wider text-accent">Registered</span>
        </div>

        <div class="font-mono text-2xl font-bold">{{ gctStatus.percentage }}%</div>
        <p class="text-xs text-muted-foreground mt-1">
            {{ formatJMD(gctStatus.turnover) }} of {{ formatJMD(gctStatus.threshold) }}
        </p>

        <!-- Progress bar -->
        <div class="mt-4 h-2 bg-muted w-full">
            <div
                class="h-full transition-all duration-300"
                :class="gctStatus.percentage >= 80 ? 'bg-accent' : 'bg-foreground'"
                :style="{ width: Math.min(gctStatus.percentage, 100) + '%' }"
            ></div>
        </div>

        <p v-if="gctStatus.percentage >= 80 && !gctStatus.isRegistered" class="mt-3 text-xs text-accent font-medium">
            Approaching mandatory GCT registration threshold. Consider registering.
        </p>
    </div>
</template>
