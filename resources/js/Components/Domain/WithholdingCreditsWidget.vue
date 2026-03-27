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
    <div class="border border-border p-6 md:p-8">
        <h2 class="text-xs uppercase tracking-wider text-muted-foreground mb-2">Withholding Credits</h2>
        <div class="font-mono text-2xl font-bold">{{ formatJMD(credits) }}</div>
        <p class="text-xs text-muted-foreground mt-1">Applied against {{ year }} tax liability</p>
        <Link :href="`/withholding-credits?year=${year}`" class="inline-block mt-3 text-xs text-accent uppercase tracking-wider hover:underline">
            View ledger
        </Link>
    </div>
</template>
