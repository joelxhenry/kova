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
    <div class="bg-card rounded-2xl shadow-sm p-6 md:p-8">
        <h2 class="text-sm font-medium text-muted-foreground mb-6">Quarterly Estimated Payments</h2>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div
                v-for="(est, i) in estimates"
                :key="est.quarter"
                class="rounded-xl p-4 transition-all duration-200"
                :class="est.isPast ? 'bg-muted/40' : 'bg-muted/20 border border-border'"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-bold">{{ quarterLabels[i] }}</span>
                    <span v-if="est.isPast" class="text-xs text-muted-foreground">Past</span>
                    <span v-else class="text-xs text-accent font-medium">Due</span>
                </div>
                <div class="tabular-nums text-base font-medium">{{ formatJMD(est.amountDue) }}</div>
                <div class="text-xs text-muted-foreground mt-1">{{ formatDeadline(est.deadline) }}</div>
            </div>
        </div>
    </div>
</template>
