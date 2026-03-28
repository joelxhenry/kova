<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    recentInvoices: { type: Array, default: () => [] },
    recentExpenses: { type: Array, default: () => [] },
});

const { formatJMD } = useCurrencyFormatter();

const statusColors = {
    draft: 'text-muted-foreground',
    sent: 'text-foreground',
    paid: 'text-accent',
    overdue: 'text-accent',
    cancelled: 'text-muted-foreground',
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <section class="py-4 md:py-12 lg:py-16">
            <!-- Quick actions -->
            <div class="flex gap-2 mb-5 md:mb-8 overflow-x-auto -mx-3 px-3 md:mx-0 md:px-0">
                <Link href="/invoices/create">
                    <Button icon="pi pi-plus" label="New invoice" size="small" />
                </Link>
                <Link href="/expenses/create">
                    <Button icon="pi pi-plus" label="Log expense" outlined severity="secondary" size="small" />
                </Link>
                <Link href="/clients/create">
                    <Button icon="pi pi-user-plus" label="Add client" text severity="secondary" size="small" />
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-0 md:gap-6">
                <!-- Recent Invoices -->
                <div class="md:bg-card md:rounded-2xl md:shadow-sm md:p-6">
                    <div class="flex items-center justify-between mb-2 md:mb-3">
                        <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Invoices</h2>
                        <Link href="/invoices" class="text-xs text-accent font-medium hover:underline">View all</Link>
                    </div>

                    <div v-if="recentInvoices.length === 0" class="text-sm text-muted-foreground py-6 text-center">
                        No invoices yet.
                        <Link href="/invoices/create" class="block mt-1 text-accent hover:underline">Create one</Link>
                    </div>

                    <div v-else>
                        <Link
                            v-for="inv in recentInvoices"
                            :key="inv.id"
                            :href="`/invoices/${inv.id}`"
                            class="flex items-center justify-between py-2.5 border-b border-border last:border-0 hover:bg-muted/20 md:-mx-2 md:px-2 rounded-lg transition-colors duration-150"
                        >
                            <div class="min-w-0">
                                <div class="text-sm font-medium truncate">{{ inv.client?.name }}</div>
                                <div class="text-xs text-muted-foreground tabular-nums">{{ inv.invoice_number }}</div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0 ml-2">
                                <span class="tabular-nums text-sm font-medium">{{ formatJMD(inv.total) }}</span>
                                <span class="text-[11px] font-medium" :class="statusColors[inv.status]">{{ inv.status }}</span>
                            </div>
                        </Link>
                    </div>

                    <div class="border-b border-border mt-4 mb-4 md:hidden"></div>
                </div>

                <!-- Recent Expenses -->
                <div class="md:bg-card md:rounded-2xl md:shadow-sm md:p-6">
                    <div class="flex items-center justify-between mb-2 md:mb-3">
                        <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Expenses</h2>
                        <Link href="/expenses" class="text-xs text-accent font-medium hover:underline">View all</Link>
                    </div>

                    <div v-if="recentExpenses.length === 0" class="text-sm text-muted-foreground py-6 text-center">
                        No expenses yet.
                        <Link href="/expenses/create" class="block mt-1 text-accent hover:underline">Log one</Link>
                    </div>

                    <div v-else>
                        <div
                            v-for="expense in recentExpenses"
                            :key="expense.id"
                            class="flex items-center justify-between py-2.5 border-b border-border last:border-0"
                        >
                            <div class="min-w-0">
                                <div class="text-sm font-medium truncate">{{ expense.description }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ expense.category?.name }} · {{ expense.date_incurred?.split('T')[0] }}
                                </div>
                            </div>
                            <span class="tabular-nums text-sm font-medium shrink-0 ml-2">{{ formatJMD(expense.amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
