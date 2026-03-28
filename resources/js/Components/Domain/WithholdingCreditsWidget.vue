<script setup>
import { Link } from '@inertiajs/vue3';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    credits: { type: Number, required: true },
    year: { type: Number, required: true },
});

const { formatJMD } = useCurrencyFormatter();
</script>

<template>
    <div class="bg-card rounded-2xl shadow-sm p-5 md:p-6">
        <h2 class="text-xs font-medium text-muted-foreground mb-2">Withholding Credits</h2>
        <div class="tabular-nums text-xl md:text-2xl font-bold">{{ formatJMD(credits) }}</div>
        <p class="text-[11px] text-muted-foreground mt-1">Applied against {{ year }} tax liability</p>
        <Link :href="`/withholding-credits?year=${year}`" class="inline-block mt-3 text-sm text-accent font-medium hover:underline">
            View ledger
        </Link>
    </div>
</template>
