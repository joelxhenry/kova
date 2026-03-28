<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from 'primevue/button';

const props = defineProps({
    rates: { type: Array, default: () => [] },
});

const formatValue = (rate) => {
    if (rate.key.includes('rate') || rate.key.includes('levy')) {
        return `${Number(rate.current_value).toFixed(2)}%`;
    }
    return `$${Number(rate.current_value).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
};

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-JM', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>

<template>
    <Head title="Statutory Rates" />

    <AdminLayout>
        <section class="py-12 md:py-20">
            <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none mb-2">Statutory Rates</h1>
            <p class="text-sm text-muted-foreground mb-10">Versioned rates — each key maintains a history of values by effective date.</p>

            <div class="bg-card rounded-2xl shadow-sm overflow-hidden">
                <div class="grid grid-cols-12 py-3 px-6 text-xs font-medium text-muted-foreground uppercase tracking-wider border-b border-border">
                    <div class="col-span-4">Rate</div>
                    <div class="col-span-3">Current Value</div>
                    <div class="col-span-2">Effective From</div>
                    <div class="col-span-1 text-center">Versions</div>
                    <div class="col-span-2 text-right">Action</div>
                </div>

                <div
                    v-for="rate in rates"
                    :key="rate.key"
                    class="grid grid-cols-12 items-center py-4 px-6 border-b border-border last:border-0"
                >
                    <div class="col-span-4">
                        <div class="text-sm font-medium">{{ rate.label }}</div>
                        <div class="text-xs text-muted-foreground mt-0.5">{{ rate.description }}</div>
                    </div>
                    <div class="col-span-3 tabular-nums text-sm font-medium">
                        {{ formatValue(rate) }}
                    </div>
                    <div class="col-span-2 text-sm text-muted-foreground tabular-nums">
                        {{ formatDate(rate.effective_from) }}
                    </div>
                    <div class="col-span-1 text-center text-sm text-muted-foreground tabular-nums">
                        {{ rate.version_count }}
                    </div>
                    <div class="col-span-2 text-right">
                        <Link :href="`/admin/statutory-rates/${rate.key}`">
                            <Button label="View" text size="small" />
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </AdminLayout>
</template>
