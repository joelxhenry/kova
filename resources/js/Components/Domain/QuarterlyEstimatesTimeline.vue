<script setup>
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    estimates: { type: Array, required: true },
});

const { formatJMD } = useCurrencyFormatter();

const quarterLabels = ['Q1', 'Q2', 'Q3', 'Q4'];

const formatDeadline = (date) => {
    const d = new Date(date + 'T00:00:00');
    return d.toLocaleDateString('en-JM', { month: 'short', day: 'numeric' });
};
</script>

<template>
    <div class="border border-border p-6 md:p-8">
        <h2 class="text-xs uppercase tracking-wider text-muted-foreground mb-6">Quarterly Estimated Payments</h2>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div
                v-for="(est, i) in estimates"
                :key="est.quarter"
                class="border border-border p-4 transition-colors duration-150"
                :class="est.isPast ? 'bg-muted' : ''"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-bold tracking-tight">{{ quarterLabels[i] }}</span>
                    <span v-if="est.isPast" class="text-xs font-mono uppercase tracking-wider text-muted-foreground">Past</span>
                    <span v-else class="text-xs font-mono uppercase tracking-wider text-accent">Due</span>
                </div>
                <div class="font-mono text-base font-medium">{{ formatJMD(est.amountDue) }}</div>
                <div class="text-xs text-muted-foreground mt-1">{{ formatDeadline(est.deadline) }}</div>
            </div>
        </div>
    </div>
</template>
