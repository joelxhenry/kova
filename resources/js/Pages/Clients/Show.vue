<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';
import { useConfirm } from 'primevue/useconfirm';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    client: { type: Object, required: true },
    invoices: { type: Array, default: () => [] },
    summary: { type: Object, required: true },
});

const confirmDialog = useConfirm();
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
    confirmDialog.require({
        message: `Delete "${props.client.name}"? This will also delete all their invoices.`,
        header: 'Delete Client',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/clients/${props.client.id}`),
    });
};
</script>

<template>
    <Head :title="client.name" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20 max-w-4xl">
            <!-- Header -->
            <div class="mb-6 md:mb-8">
                <Link href="/clients" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">
                    &larr; Clients
                </Link>

                <div class="flex items-start justify-between mt-3">
                    <div class="min-w-0 flex-1">
                        <h1 class="text-xl md:text-2xl font-bold tracking-tight leading-tight truncate">
                            {{ client.name }}
                        </h1>
                        <div class="mt-1 text-sm text-muted-foreground space-y-0.5">
                            <div v-if="client.email">{{ client.email }}</div>
                            <div v-if="client.phone">{{ client.phone }}</div>
                            <div v-if="formatAddress(client)">{{ formatAddress(client) }}</div>
                            <div v-if="client.trn" class="tabular-nums">TRN: {{ client.trn }}</div>
                        </div>
                        <span v-if="client.is_designated_entity" class="inline-block mt-2 text-[11px] font-medium text-accent bg-accent/10 px-2 py-0.5 rounded-full">
                            Designated Entity
                        </span>
                    </div>
                    <div class="flex items-center gap-2 ml-4 shrink-0">
                        <Link :href="`/clients/${client.id}/edit`">
                            <Button icon="pi pi-pencil" text size="small" />
                        </Link>
                        <Button icon="pi pi-trash" text severity="danger" size="small" @click="deleteClient" />
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="grid grid-cols-2 gap-3 md:gap-4 mb-6 md:mb-8">
                <div class="bg-card rounded-2xl shadow-sm p-4 md:p-5">
                    <div class="text-xs text-muted-foreground">Total Invoiced</div>
                    <div class="text-lg md:text-xl font-bold tabular-nums mt-1">{{ formatJMD(summary.totalInvoiced) }}</div>
                </div>
                <div class="bg-card rounded-2xl shadow-sm p-4 md:p-5">
                    <div class="text-xs text-muted-foreground">Balance Due</div>
                    <div class="text-lg md:text-xl font-bold tabular-nums mt-1" :class="summary.balanceDue > 0 ? 'text-accent' : ''">
                        {{ formatJMD(summary.balanceDue) }}
                    </div>
                </div>
            </div>

            <!-- Contacts -->
            <div v-if="client.contacts?.length" class="mb-6 md:mb-8">
                <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-3">Contacts</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                    <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Invoices</h2>
                    <Link :href="`/invoices/create`" class="text-sm text-accent font-medium hover:underline">
                        New invoice
                    </Link>
                </div>

                <div v-if="invoices.length === 0" class="text-sm text-muted-foreground py-8 text-center">
                    No invoices for this client yet.
                </div>

                <div v-else class="rounded-2xl">
                    <Link
                        v-for="inv in invoices"
                        :key="inv.id"
                        :href="`/invoices/${inv.id}`"
                        class="flex items-center justify-between py-4 border-b border-border hover:bg-muted/50 transition-colors duration-150 -mx-4 px-4"
                    >
                        <div>
                            <span class="tabular-nums text-sm text-muted-foreground">{{ inv.invoice_number }}</span>
                            <div class="mt-0.5 text-sm text-muted-foreground">
                                {{ inv.issue_date?.split('T')[0] }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="tabular-nums text-base font-medium">{{ formatJMD(inv.total) }}</div>
                            <div class="text-xs font-medium" :class="statusColors[inv.status]">
                                {{ inv.status }}
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
