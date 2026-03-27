<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    clients: { type: Array, default: () => [] },
});

const page = usePage();

const deleteClient = (client) => {
    if (confirm(`Delete "${client.name}"? This will also delete all their invoices.`)) {
        router.delete(`/clients/${client.id}`);
    }
};
</script>

<template>
    <Head title="Clients" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <div class="h-1 w-16 bg-accent mb-6" />
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">Clients</h1>
                </div>
                <Link href="/clients/create" class="inline-flex items-center gap-2 py-3 px-6 border border-foreground text-foreground font-semibold text-sm uppercase tracking-wider transition-all duration-150 hover:bg-foreground hover:text-background">
                    Add client
                </Link>
            </div>

            <div
                v-if="page.props.flash.status"
                class="mb-6 border border-accent/30 bg-accent/5 px-4 py-3 text-sm text-foreground"
            >
                {{ page.props.flash.status }}
            </div>

            <div v-if="clients.length === 0" class="py-20 text-center text-muted-foreground">
                <p class="text-lg">No clients yet.</p>
                <p class="mt-2 text-sm">Add your first client to start creating invoices.</p>
            </div>

            <div v-else class="border-t border-border">
                <div
                    v-for="client in clients"
                    :key="client.id"
                    class="flex items-center justify-between py-4 border-b border-border"
                >
                    <div>
                        <span class="text-base font-medium text-foreground">{{ client.name }}</span>
                        <span v-if="client.is_designated_entity" class="ml-2 text-xs font-mono uppercase tracking-wider text-accent">
                            Designated
                        </span>
                        <div class="mt-0.5 text-sm text-muted-foreground">
                            <span v-if="client.email">{{ client.email }}</span>
                            <span v-if="client.email && client.phone"> · </span>
                            <span v-if="client.phone">{{ client.phone }}</span>
                            <span v-if="client.invoices_count !== undefined" class="ml-2">· {{ client.invoices_count }} invoice{{ client.invoices_count !== 1 ? 's' : '' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <Link :href="`/clients/${client.id}/edit`" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">
                            Edit
                        </Link>
                        <button @click="deleteClient(client)" class="text-sm text-muted-foreground hover:text-accent transition-colors duration-150">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
