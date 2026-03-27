<script setup>
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    breakdown: { type: Object, required: true },
});

const { formatJMD } = useCurrencyFormatter();
</script>

<template>
    <div class="bg-card rounded-2xl shadow-sm p-6 md:p-8">
        <h2 class="text-sm font-medium text-muted-foreground mb-6">Tax Summary</h2>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
            <div>
                <div class="text-xs text-muted-foreground">Gross Income</div>
                <div class="font-mono text-lg font-bold mt-1">{{ formatJMD(breakdown.grossIncome) }}</div>
            </div>
            <div>
                <div class="text-xs text-muted-foreground">Expenses</div>
                <div class="font-mono text-lg font-bold mt-1">{{ formatJMD(breakdown.totalExpenses) }}</div>
            </div>
            <div>
                <div class="text-xs text-muted-foreground">Net Income</div>
                <div class="font-mono text-lg font-bold mt-1">{{ formatJMD(breakdown.netIncome) }}</div>
            </div>
            <div>
                <div class="text-xs text-muted-foreground">Net Payable</div>
                <div class="font-mono text-lg font-bold mt-1" :class="breakdown.netTaxPayable < 0 ? 'text-accent' : ''">
                    {{ formatJMD(breakdown.netTaxPayable) }}
                </div>
                <div v-if="breakdown.netTaxPayable < 0" class="text-xs text-accent mt-0.5">Refund due</div>
            </div>
        </div>

        <div class="mt-6 pt-6 border-t border-border/50 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
            <div class="flex justify-between">
                <span class="text-muted-foreground">Income Tax</span>
                <span class="font-mono">{{ formatJMD(breakdown.totalIncomeTax) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted-foreground">NIS</span>
                <span class="font-mono">{{ formatJMD(breakdown.nisContribution) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted-foreground">NHT</span>
                <span class="font-mono">{{ formatJMD(breakdown.nhtContribution) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted-foreground">Education Tax</span>
                <span class="font-mono">{{ formatJMD(breakdown.educationTax) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted-foreground">Total Liability</span>
                <span class="font-mono font-medium">{{ formatJMD(breakdown.totalTaxLiability) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted-foreground">WHT Credits</span>
                <span class="font-mono">-{{ formatJMD(breakdown.withholdingCredits) }}</span>
            </div>
        </div>
    </div>
</template>
