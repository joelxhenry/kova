<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    client: { type: Object, required: true },
    invoices: { type: Array, default: () => [] },
    summary: { type: Object, required: true },
});

const page = usePage();
const { formatJMD } = useCurrencyFormatter();

const statusColors = {
    draft: 'text-muted-foreground',
    sent: 'text-foreground',
    paid: 'text-accent',
    overdue: 'text-accent',
    cancelled: 'text-muted-foreground line-through',
};

const formatAddress = (c) => {
    return [c.address_line_1, c.address_line_2, c.city, c.state_or_parish, c.postal_code, c.country]
        .filter(Boolean).join(', ');
};

const deleteClient = () => {
    if (confirm(`Delete "${props.client.name}"? This will also delete all their invoices.`)) {
        router.delete(`/clients/${props.client.id}`);
    }
};
</script>

<template>
    <Head :title="client.name" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20 max-w-4xl">
            <div class="flex items-start justify-between mb-8">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tight leading-tight">
                        {{ client.name }}
                    </h1>
                    <div class="mt-2 flex items-center gap-3 text-sm text-muted-foreground">
                        <span v-if="client.email">{{ client.email }}</span>
                        <span v-if="client.email && client.phone">·</span>
                        <span v-if="client.phone">{{ client.phone }}</span>
                        <span v-if="client.is_designated_entity" class="text-xs font-medium text-accent bg-accent/10 px-2 py-0.5 rounded-full">Designated Entity</span>
                    </div>
                    <p v-if="formatAddress(client)" class="mt-1 text-sm text-muted-foreground">{{ formatAddress(client) }}</p>
                    <p v-if="client.trn" class="mt-1 text-xs text-muted-foreground">TRN: {{ client.trn }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="`/clients/${client.id}/edit`">
                        <Button label="Edit" severity="secondary" outlined size="small" />
                    </Link>
                    <Button label="Delete" severity="danger" text size="small" @click="deleteClient" />
                </div>
            </div>

            <div
                v-if="page.props.flash.status"
                class="mb-6 bg-accent/10 rounded-xl px-4 py-3 text-sm text-foreground"
            >
                {{ page.props.flash.status }}
            </div>

            <!-- Financial Summary -->
            <div class="grid grid-cols-3 gap-4 mb-8">
                <div class="bg-card rounded-2xl shadow-sm p-5">
                    <div class="text-xs text-muted-foreground">Total Invoiced</div>
                    <div class="text-xl font-bold tabular-nums mt-1">{{ formatJMD(summary.totalInvoiced) }}</div>
                </div>
                <div class="bg-card rounded-2xl shadow-sm p-5">
                    <div class="text-xs text-muted-foreground">Total Paid</div>
                    <div class="text-xl font-bold tabular-nums mt-1">{{ formatJMD(summary.totalPaid) }}</div>
                </div>
                <div class="bg-card rounded-2xl shadow-sm p-5">
                    <div class="text-xs text-muted-foreground">Balance Due</div>
                    <div class="text-xl font-bold tabular-nums mt-1" :class="summary.balanceDue > 0 ? 'text-accent' : ''">
                        {{ formatJMD(summary.balanceDue) }}
                    </div>
                </div>
            </div>

            <!-- Contacts -->
            <div class="mb-8">
                <h2 class="text-sm font-medium text-muted-foreground mb-3">Contacts</h2>
                <div v-if="!client.contacts || client.contacts.length === 0" class="text-sm text-muted-foreground py-4">
                    No contacts added. <Link :href="`/clients/${client.id}/edit`" class="text-accent hover:underline">Add contacts</Link>
                </div>
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div
                        v-for="contact in client.contacts"
                        :key="contact.id"
                        class="bg-card rounded-xl shadow-sm p-4"
                    >
                        <div class="font-medium text-sm">{{ contact.first_name }} {{ contact.last_name }}</div>
                        <div v-if="contact.email" class="text-xs text-muted-foreground mt-0.5">{{ contact.email }}</div>
                        <div v-if="contact.phone" class="text-xs text-muted-foreground">{{ contact.phone }}</div>
                    </div>
                </div>
            </div>

            <!-- Invoices -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-medium text-muted-foreground">Invoices</h2>
                    <Link :href="`/invoices/create`">
                        <Button label="New Invoice" size="small" />
                    </Link>
                </div>

                <div v-if="invoices.length === 0" class="text-sm text-muted-foreground py-8 text-center">
                    No invoices for this client yet.
                </div>

                <div v-else class="bg-card rounded-2xl shadow-sm overflow-hidden">
                    <Link
                        v-for="inv in invoices"
                        :key="inv.id"
                        :href="`/invoices/${inv.id}`"
                        class="flex items-center justify-between py-3 px-5 border-b border-border/50 last:border-b-0 hover:bg-muted/20 transition-colors duration-200"
                    >
                        <div>
                            <span class="text-sm tabular-nums text-muted-foreground">{{ inv.invoice_number }}</span>
                            <span class="ml-2 text-sm text-foreground">{{ inv.issue_date?.split('T')[0] }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="tabular-nums text-sm font-medium">{{ formatJMD(inv.total) }}</span>
                            <span class="text-xs font-medium" :class="statusColors[inv.status]">{{ inv.status }}</span>
                        </div>
                    </Link>
                </div>
            </div>

            <div class="mt-8">
                <Link href="/clients" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-200">
                    Back to clients
                </Link>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
