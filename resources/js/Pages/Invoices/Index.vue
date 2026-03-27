<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    invoices: { type: Object, required: true },
    clients: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const { formatJMD } = useCurrencyFormatter();

const statusOptions = [
    { label: 'All statuses', value: null },
    { label: 'Draft', value: 'draft' },
    { label: 'Sent', value: 'sent' },
    { label: 'Paid', value: 'paid' },
    { label: 'Overdue', value: 'overdue' },
    { label: 'Cancelled', value: 'cancelled' },
];

const clientOptions = [
    { label: 'All clients', value: null },
    ...props.clients.map(c => ({ label: c.name, value: c.id })),
];

const status = ref(props.filters.status ?? null);
const clientId = ref(props.filters.client_id ? Number(props.filters.client_id) : null);
const from = ref(props.filters.from ? new Date(props.filters.from) : null);
const to = ref(props.filters.to ? new Date(props.filters.to) : null);

const formatDateParam = (d) => d ? d.toISOString().split('T')[0] : undefined;

const applyFilters = () => {
    router.get('/invoices', {
        status: status.value || undefined,
        client_id: clientId.value || undefined,
        from: formatDateParam(from.value),
        to: formatDateParam(to.value),
    }, { preserveState: true });
};

const statusColors = {
    draft: 'text-muted-foreground',
    sent: 'text-foreground',
    paid: 'text-accent',
    overdue: 'text-accent',
    cancelled: 'text-muted-foreground line-through',
};
</script>

<template>
    <Head title="Invoices" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <div class="h-1 w-16 bg-accent mb-6" />
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">Invoices</h1>
                </div>
                <Link href="/invoices/create" class="inline-flex items-center gap-2 py-3 px-6 border border-foreground text-foreground font-semibold text-sm uppercase tracking-wider transition-all duration-150 hover:bg-foreground hover:text-background">
                    New invoice
                </Link>
            </div>

            <div
                v-if="page.props.flash.status"
                class="mb-6 border border-accent/30 bg-accent/5 px-4 py-3 text-sm text-foreground"
            >
                {{ page.props.flash.status }}
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-8">
                <Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All statuses" @change="applyFilters" />
                <Select v-model="clientId" :options="clientOptions" optionLabel="label" optionValue="value" placeholder="All clients" @change="applyFilters" />
                <DatePicker v-model="from" placeholder="From" dateFormat="yy-mm-dd" showIcon @date-select="applyFilters" />
                <DatePicker v-model="to" placeholder="To" dateFormat="yy-mm-dd" showIcon @date-select="applyFilters" />
            </div>

            <div v-if="invoices.data.length === 0" class="py-20 text-center text-muted-foreground">
                <p class="text-lg">No invoices yet.</p>
                <p class="mt-2 text-sm">Create your first invoice to start tracking income.</p>
            </div>

            <div v-else class="border-t border-border">
                <Link
                    v-for="inv in invoices.data"
                    :key="inv.id"
                    :href="`/invoices/${inv.id}`"
                    class="flex items-center justify-between py-4 border-b border-border hover:bg-muted/50 transition-colors duration-150 -mx-4 px-4"
                >
                    <div>
                        <span class="font-mono text-sm text-muted-foreground">{{ inv.invoice_number }}</span>
                        <span class="ml-3 text-base font-medium text-foreground">{{ inv.client?.name }}</span>
                        <div class="mt-0.5 text-sm text-muted-foreground">
                            {{ inv.issue_date?.split('T')[0] }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-mono text-base font-medium">{{ formatJMD(inv.total) }}</div>
                        <div class="text-xs uppercase tracking-wider font-mono" :class="statusColors[inv.status]">
                            {{ inv.status }}
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="invoices.last_page > 1" class="mt-8 flex gap-2">
                <Link
                    v-for="link in invoices.links"
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
