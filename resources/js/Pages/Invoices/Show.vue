<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    invoice: { type: Object, required: true },
});

const { formatJMD } = useCurrencyFormatter();

const statusColors = {
    draft: 'text-muted-foreground border-border',
    sent: 'text-foreground border-foreground',
    paid: 'text-accent border-accent',
    overdue: 'text-accent border-accent',
    cancelled: 'text-muted-foreground border-border line-through',
};

const deleteInvoice = () => {
    if (confirm('Delete this invoice? This action cannot be undone.')) {
        router.delete(`/invoices/${props.invoice.id}`);
    }
};
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20 max-w-3xl">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <div class="h-1 w-16 bg-accent mb-6" />
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none font-mono">
                        {{ invoice.invoice_number }}
                    </h1>
                    <p class="mt-2 text-muted-foreground">
                        {{ invoice.client?.name }}
                    </p>
                </div>
                <span class="text-xs uppercase tracking-widest font-mono border px-3 py-1" :class="statusColors[invoice.status]">
                    {{ invoice.status }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-10 text-sm">
                <div>
                    <span class="text-muted-foreground uppercase tracking-wider text-xs">Issue Date</span>
                    <div class="mt-1 font-mono">{{ invoice.issue_date?.split('T')[0] }}</div>
                </div>
                <div v-if="invoice.due_date">
                    <span class="text-muted-foreground uppercase tracking-wider text-xs">Due Date</span>
                    <div class="mt-1 font-mono">{{ invoice.due_date?.split('T')[0] }}</div>
                </div>
            </div>

            <!-- Line Items -->
            <div class="border-t border-border">
                <div class="grid grid-cols-12 py-3 text-xs uppercase tracking-wider text-muted-foreground border-b border-border">
                    <div class="col-span-6">Description</div>
                    <div class="col-span-2 text-right">Qty</div>
                    <div class="col-span-2 text-right">Unit Price</div>
                    <div class="col-span-2 text-right">Amount</div>
                </div>
                <div
                    v-for="item in invoice.items"
                    :key="item.id"
                    class="grid grid-cols-12 py-3 border-b border-border text-sm"
                >
                    <div class="col-span-6">{{ item.description }}</div>
                    <div class="col-span-2 text-right font-mono">{{ item.quantity }}</div>
                    <div class="col-span-2 text-right font-mono">{{ formatJMD(item.unit_price) }}</div>
                    <div class="col-span-2 text-right font-mono">{{ formatJMD(item.amount) }}</div>
                </div>
            </div>

            <!-- Totals -->
            <div class="mt-6 space-y-2 text-right text-sm">
                <div class="flex justify-end gap-8">
                    <span class="text-muted-foreground">Subtotal</span>
                    <span class="font-mono w-32">{{ formatJMD(invoice.subtotal) }}</span>
                </div>
                <div v-if="Number(invoice.gct_amount) > 0" class="flex justify-end gap-8">
                    <span class="text-muted-foreground">GCT</span>
                    <span class="font-mono w-32">{{ formatJMD(invoice.gct_amount) }}</span>
                </div>
                <div class="flex justify-end gap-8 text-base font-medium border-t border-border pt-2">
                    <span>Total</span>
                    <span class="font-mono w-32">{{ formatJMD(invoice.total) }}</span>
                </div>
                <div v-if="Number(invoice.withholding_tax_amount) > 0" class="flex justify-end gap-8 text-muted-foreground">
                    <span>Less: Withholding Tax</span>
                    <span class="font-mono w-32">-{{ formatJMD(invoice.withholding_tax_amount) }}</span>
                </div>
                <div v-if="Number(invoice.contractors_levy_amount) > 0" class="flex justify-end gap-8 text-muted-foreground">
                    <span>Less: Contractors Levy</span>
                    <span class="font-mono w-32">-{{ formatJMD(invoice.contractors_levy_amount) }}</span>
                </div>
                <div v-if="Number(invoice.withholding_tax_amount) > 0 || Number(invoice.contractors_levy_amount) > 0" class="flex justify-end gap-8 text-base font-medium border-t border-border pt-2">
                    <span>Net Receivable</span>
                    <span class="font-mono w-32">{{ formatJMD(invoice.net_receivable) }}</span>
                </div>
            </div>

            <div v-if="invoice.notes" class="mt-10 border-t border-border pt-6">
                <span class="text-xs uppercase tracking-wider text-muted-foreground">Notes</span>
                <p class="mt-2 text-sm text-muted-foreground">{{ invoice.notes }}</p>
            </div>

            <div class="mt-10 flex items-center gap-4 border-t border-border pt-6">
                <Link :href="`/invoices/${invoice.id}/edit`">
                    <Button label="Edit" text size="small" />
                </Link>
                <Button label="Delete" text severity="danger" size="small" @click="deleteInvoice" />
                <Link href="/invoices" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150 ml-auto">
                    Back to invoices
                </Link>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
