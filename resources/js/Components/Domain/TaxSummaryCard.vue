<script setup>
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    breakdown: { type: Object, required: true },
});

const { formatJMD } = useCurrencyFormatter();
</script>

<template>
    <div class="bg-card rounded-2xl shadow-sm p-5 md:p-8">
        <h2 class="text-xs font-medium text-muted-foreground mb-4 md:mb-6">Tax Summary</h2>

        <div class="grid grid-cols-2 gap-4 md:gap-6">
            <div>
                <div class="text-xs text-muted-foreground">Gross Income</div>
                <div class="tabular-nums text-base md:text-lg font-bold mt-1">{{ formatJMD(breakdown.grossIncome) }}</div>
            </div>
            <div>
                <div class="text-xs text-muted-foreground">Net Payable</div>
                <div class="tabular-nums text-base md:text-lg font-bold mt-1" :class="breakdown.netTaxPayable < 0 ? 'text-accent' : ''">
                    {{ formatJMD(breakdown.netTaxPayable) }}
                </div>
                <div v-if="breakdown.netTaxPayable < 0" class="text-[11px] text-accent mt-0.5">Refund due</div>
            </div>
        </div>

        <div class="mt-4 md:mt-6 pt-4 md:pt-6 border-t border-border/50 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-muted-foreground">Income Tax</span>
                <span class="tabular-nums">{{ formatJMD(breakdown.totalIncomeTax) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-muted-foreground">NIS</span>
                <span class="tabular-nums">{{ formatJMD(breakdown.nisContribution) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-muted-foreground">NHT</span>
                <span class="tabular-nums">{{ formatJMD(breakdown.nhtContribution) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-muted-foreground">Education Tax</span>
                <span class="tabular-nums">{{ formatJMD(breakdown.educationTax) }}</span>
            </div>
            <div class="flex justify-between text-sm pt-2 border-t border-border/30">
                <span class="text-muted-foreground font-medium">Total Liability</span>
                <span class="tabular-nums font-medium">{{ formatJMD(breakdown.totalTaxLiability) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-muted-foreground">WHT Credits</span>
                <span class="tabular-nums">-{{ formatJMD(breakdown.withholdingCredits) }}</span>
            </div>
        </div>
    </div>
</template>
