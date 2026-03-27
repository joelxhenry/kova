<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    entries: { type: Object, required: true },
});

const page = usePage();
const { formatJMD } = useCurrencyFormatter();

const deleteEntry = (entry) => {
    if (confirm('Delete this income entry?')) {
        router.delete(`/income/${entry.id}`);
    }
};
</script>

<template>
    <Head title="Income" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">Income</h1>
                    <p class="mt-2 text-muted-foreground text-sm">Non-invoice income: cash jobs, ad-hoc payments, etc.</p>
                </div>
                <Link href="/income/create" class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent/10 text-accent font-medium text-sm rounded-full hover:bg-accent/20 transition-all duration-200">
                    Add entry
                </Link>
            </div>

            <div
                v-if="page.props.flash.status"
                class="mb-6 bg-accent/10 rounded-xl px-4 py-3 text-sm text-foreground"
            >
                {{ page.props.flash.status }}
            </div>

            <div v-if="entries.data.length === 0" class="py-20 text-center text-muted-foreground">
                <p class="text-lg">No income entries yet.</p>
                <p class="mt-2 text-sm">Log income that doesn't come from a formal invoice.</p>
            </div>

            <div v-else class="rounded-2xl">
                <div
                    v-for="entry in entries.data"
                    :key="entry.id"
                    class="flex items-center justify-between py-4 border-b border-border"
                >
                    <div>
                        <span class="text-base font-medium text-foreground">{{ entry.source }}</span>
                        <div class="mt-0.5 text-sm text-muted-foreground">
                            {{ entry.date_received?.split('T')[0] }}
                            <span v-if="entry.description"> · {{ entry.description }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="text-right">
                            <div class="tabular-nums text-base font-medium">{{ formatJMD(entry.amount) }}</div>
                            <div v-if="Number(entry.withholding_tax_applied) > 0" class="text-xs text-muted-foreground tabular-nums">
                                WHT: {{ formatJMD(entry.withholding_tax_applied) }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Link :href="`/income/${entry.id}/edit`">
                                <Button label="Edit" text size="small" />
                            </Link>
                            <Button label="Delete" text severity="danger" size="small" @click="deleteEntry(entry)" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="entries.last_page > 1" class="mt-8 flex gap-2">
                <Link
                    v-for="link in entries.links"
                    :key="link.label"
                    :href="link.url"
                    class="px-3 py-1 text-sm border border-border transition-colors duration-150"
                    :class="link.active ? 'bg-foreground text-background' : 'text-muted-foreground hover:text-foreground'"
                    v-html="link.label"
                />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
