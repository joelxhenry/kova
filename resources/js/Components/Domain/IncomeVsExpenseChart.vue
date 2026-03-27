<script setup>
import { computed } from 'vue';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    monthlyData: { type: Array, required: true },
});

const { formatJMD } = useCurrencyFormatter();

const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const maxValue = computed(() => {
    const allValues = props.monthlyData.flatMap(d => [d.income, d.expenses]);
    return Math.max(...allValues, 1);
});

const getBarHeight = (value) => {
    return Math.max((value / maxValue.value) * 100, 0);
};

const totalIncome = computed(() => props.monthlyData.reduce((s, d) => s + d.income, 0));
const totalExpenses = computed(() => props.monthlyData.reduce((s, d) => s + d.expenses, 0));
</script>

<template>
    <div class="border border-border p-6 md:p-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xs uppercase tracking-wider text-muted-foreground">Income vs Expenses</h2>
            <div class="flex items-center gap-4 text-xs">
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 bg-foreground"></span>
                    Income {{ formatJMD(totalIncome) }}
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 bg-accent"></span>
                    Expenses {{ formatJMD(totalExpenses) }}
                </span>
            </div>
        </div>

        <div class="flex items-end gap-1 h-40">
            <div v-for="d in monthlyData" :key="d.month" class="flex-1 flex items-end gap-px">
                <div
                    class="flex-1 bg-foreground transition-all duration-150"
                    :style="{ height: getBarHeight(d.income) + '%' }"
                    :title="formatJMD(d.income)"
                ></div>
                <div
                    class="flex-1 bg-accent transition-all duration-150"
                    :style="{ height: getBarHeight(d.expenses) + '%' }"
                    :title="formatJMD(d.expenses)"
                ></div>
            </div>
        </div>

        <div class="flex gap-1 mt-2">
            <div v-for="(name, i) in monthNames" :key="i" class="flex-1 text-center text-xs text-muted-foreground">
                {{ name }}
            </div>
        </div>
    </div>
</template>
