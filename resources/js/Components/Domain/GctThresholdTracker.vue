<script setup>
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    gctStatus: { type: Object, required: true },
});

const { formatJMD } = useCurrencyFormatter();
</script>

<template>
    <div class="bg-card rounded-2xl shadow-sm p-5 md:p-6">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-xs font-medium text-muted-foreground">GCT Threshold</h2>
            <span v-if="gctStatus.isRegistered" class="text-[11px] font-medium text-accent bg-accent/10 px-2 py-0.5 rounded-full">Registered</span>
        </div>

        <div class="tabular-nums text-xl md:text-2xl font-bold">{{ gctStatus.percentage }}%</div>
        <p class="text-[11px] text-muted-foreground mt-1">
            {{ formatJMD(gctStatus.turnover) }} of {{ formatJMD(gctStatus.threshold) }}
        </p>

        <!-- Progress bar -->
        <div class="mt-3 h-2 bg-muted rounded-full w-full overflow-hidden">
            <div
                class="h-full rounded-full transition-all duration-300"
                :class="gctStatus.percentage >= 80 ? 'bg-accent' : 'bg-muted-foreground'"
                :style="{ width: Math.min(gctStatus.percentage, 100) + '%' }"
            ></div>
        </div>

        <p v-if="gctStatus.percentage >= 80 && !gctStatus.isRegistered" class="mt-3 text-[11px] text-accent font-medium">
            Approaching mandatory GCT registration threshold. Consider registering.
        </p>
    </div>
</template>
