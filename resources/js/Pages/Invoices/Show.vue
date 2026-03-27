<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputLabel from '@/Components/UI/InputLabel.vue';
import { useConfirm } from 'primevue/useconfirm';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    invoice: { type: Object, required: true },
    business: { type: Object, default: () => ({}) },
    availableRecipients: { type: Array, default: () => [] },
});

const page = usePage();
const confirmDialog = useConfirm();
const { formatJMD } = useCurrencyFormatter();

const statusOptions = [
    { label: 'Draft', value: 'draft' },
    { label: 'Sent', value: 'sent' },
    { label: 'Paid', value: 'paid' },
    { label: 'Overdue', value: 'overdue' },
    { label: 'Cancelled', value: 'cancelled' },
];

const statusColors = {
    draft: 'bg-muted/50 text-muted-foreground',
    sent: 'bg-foreground/10 text-foreground',
    paid: 'bg-accent/10 text-accent',
    overdue: 'bg-accent/20 text-accent',
    cancelled: 'bg-muted/50 text-muted-foreground',
};

const selectedStatus = ref(props.invoice.status);

const updateStatus = () => {
    router.put(`/invoices/${props.invoice.id}/status`, {
        status: selectedStatus.value,
    });
};

const duplicateInvoice = () => {
    router.post(`/invoices/${props.invoice.id}/duplicate`);
};

// Send dialog
const showSendDialog = ref(false);
const selectedRecipients = ref([]);
const customEmail = ref('');
const sending = ref(false);

const openSendDialog = () => {
    selectedRecipients.value = props.availableRecipients.map(r => r.email);
    customEmail.value = '';
    showSendDialog.value = true;
};

const addCustomEmail = () => {
    const email = customEmail.value.trim();
    if (email && !selectedRecipients.value.includes(email)) {
        selectedRecipients.value.push(email);
    }
    customEmail.value = '';
};

const sendInvoice = () => {
    if (selectedRecipients.value.length === 0) return;
    sending.value = true;
    router.post(`/invoices/${props.invoice.id}/send`, {
        recipients: selectedRecipients.value,
    }, {
        onFinish: () => {
            sending.value = false;
            showSendDialog.value = false;
        },
    });
};

const deleteInvoice = () => {
    confirmDialog.require({
        message: 'Delete this invoice? This action cannot be undone.',
        header: 'Delete Invoice',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/invoices/${props.invoice.id}`),
    });
};


const formatDate = (d) => {
    if (!d) return '';
    const date = new Date(d);
    return date.toLocaleDateString('en-JM', { year: 'numeric', month: 'long', day: 'numeric' });
};

const hasDeductions = Number(props.invoice.withholding_tax_amount) > 0 || Number(props.invoice.contractors_levy_amount) > 0;

const businessAddress = [
    props.business.business_address_line_1,
    props.business.business_address_line_2,
    [props.business.business_city, props.business.business_state_or_parish].filter(Boolean).join(', '),
    props.business.business_postal_code,
    props.business.business_country,
].filter(Boolean);

const clientAddress = [
    props.invoice.client?.address_line_1,
    props.invoice.client?.address_line_2,
    [props.invoice.client?.city, props.invoice.client?.state_or_parish].filter(Boolean).join(', '),
    props.invoice.client?.postal_code,
    props.invoice.client?.country,
].filter(Boolean);
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20 max-w-4xl">
            <!-- Action Bar (hidden in print) -->
            <div class="flex flex-wrap items-center gap-3 mb-8">
                <Link href="/invoices" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">
                    &larr; Invoices
                </Link>
                <div class="flex-1"></div>
                <div class="flex items-center gap-2">
                    <Select
                        v-model="selectedStatus"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        @change="updateStatus"
                        class="w-36"
                    />
                </div>
                <Button label="Send" icon="pi pi-send" text size="small" @click="openSendDialog" />
                <a :href="`/invoices/${invoice.id}/pdf`" target="_blank">
                    <Button label="PDF" icon="pi pi-file-pdf" text size="small" />
                </a>
<Button label="Duplicate" icon="pi pi-copy" text size="small" @click="duplicateInvoice" />
                <Link :href="`/invoices/${invoice.id}/edit`">
                    <Button label="Edit" icon="pi pi-pencil" text size="small" />
                </Link>
                <Button label="Delete" icon="pi pi-trash" text severity="danger" size="small" @click="deleteInvoice" />
            </div>

            <!-- Invoice Document -->
            <div class="bg-white rounded-2xl shadow-sm border border-border p-8 md:p-12">
                <!-- Header -->
                <div class="flex items-start justify-between mb-10">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold tracking-tighter leading-none">
                            {{ business.business_name || page.props.auth.user.name }}
                        </h1>
                        <div v-if="businessAddress.length" class="mt-2 text-sm text-muted-foreground leading-relaxed">
                            <div v-for="(line, i) in businessAddress" :key="i">{{ line }}</div>
                        </div>
                        <div class="mt-2 text-sm text-muted-foreground">
                            <div v-if="business.business_phone">{{ business.business_phone }}</div>
                            <div v-if="business.business_email">{{ business.business_email }}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Invoice</div>
                        <div class="text-xl font-bold tabular-nums mt-1">{{ invoice.invoice_number }}</div>
                        <span class="inline-block mt-2 text-xs font-medium px-3 py-1 rounded-full" :class="statusColors[invoice.status]">
                            {{ invoice.status }}
                        </span>
                    </div>
                </div>

                <!-- From/To + Meta -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                    <!-- Bill To -->
                    <div>
                        <div class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-2">Bill To</div>
                        <div class="text-base font-medium">{{ invoice.client?.name }}</div>
                        <div v-if="invoice.client?.trn" class="text-sm text-muted-foreground tabular-nums">TRN: {{ invoice.client.trn }}</div>
                        <div v-if="clientAddress.length" class="mt-1 text-sm text-muted-foreground leading-relaxed">
                            <div v-for="(line, i) in clientAddress" :key="i">{{ line }}</div>
                        </div>
                        <div class="mt-1 text-sm text-muted-foreground">
                            <div v-if="invoice.client?.email">{{ invoice.client.email }}</div>
                            <div v-if="invoice.client?.phone">{{ invoice.client.phone }}</div>
                        </div>
                        <!-- Contacts -->
                        <div v-if="invoice.client?.contacts?.length" class="mt-3">
                            <div class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-1">Contacts</div>
                            <div v-for="contact in invoice.client.contacts" :key="contact.id" class="text-sm text-muted-foreground">
                                {{ contact.first_name }} {{ contact.last_name }}
                                <span v-if="contact.email"> &middot; {{ contact.email }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Details -->
                    <div class="md:text-right">
                        <div class="grid grid-cols-2 md:flex md:flex-col gap-4">
                            <div>
                                <div class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-1">Issue Date</div>
                                <div class="text-sm tabular-nums">{{ formatDate(invoice.issue_date) }}</div>
                            </div>
                            <div v-if="invoice.due_date">
                                <div class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-1">Due Date</div>
                                <div class="text-sm tabular-nums">{{ formatDate(invoice.due_date) }}</div>
                            </div>
                            <div v-if="business.payment_terms">
                                <div class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-1">Terms</div>
                                <div class="text-sm text-muted-foreground">{{ business.payment_terms }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="mb-8">
                    <div class="grid grid-cols-12 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider border-b-2 border-foreground/10">
                        <div class="col-span-5">Description</div>
                        <div class="col-span-2">Unit</div>
                        <div class="col-span-1 text-right">Qty</div>
                        <div class="col-span-2 text-right">Unit Price</div>
                        <div class="col-span-2 text-right">Amount</div>
                    </div>
                    <div
                        v-for="item in invoice.items"
                        :key="item.id"
                        class="grid grid-cols-12 py-3 border-b border-border text-sm"
                    >
                        <div class="col-span-5">{{ item.description }}</div>
                        <div class="col-span-2 text-muted-foreground">{{ item.unit || '—' }}</div>
                        <div class="col-span-1 text-right tabular-nums">{{ item.quantity }}</div>
                        <div class="col-span-2 text-right tabular-nums">{{ formatJMD(item.unit_price) }}</div>
                        <div class="col-span-2 text-right tabular-nums font-medium">{{ formatJMD(item.amount) }}</div>
                    </div>
                </div>

                <!-- Totals -->
                <div class="flex justify-end">
                    <div class="w-full max-w-xs space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span class="tabular-nums">{{ formatJMD(invoice.subtotal) }}</span>
                        </div>
                        <div v-if="Number(invoice.gct_amount) > 0" class="flex justify-between">
                            <span class="text-muted-foreground">GCT (15%)</span>
                            <span class="tabular-nums">{{ formatJMD(invoice.gct_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-semibold border-t border-border pt-2">
                            <span>Total</span>
                            <span class="tabular-nums">{{ formatJMD(invoice.total) }}</span>
                        </div>
                        <template v-if="hasDeductions">
                            <div v-if="Number(invoice.withholding_tax_amount) > 0" class="flex justify-between text-muted-foreground">
                                <span>Less: Withholding Tax</span>
                                <span class="tabular-nums">-{{ formatJMD(invoice.withholding_tax_amount) }}</span>
                            </div>
                            <div v-if="Number(invoice.contractors_levy_amount) > 0" class="flex justify-between text-muted-foreground">
                                <span>Less: Contractors Levy</span>
                                <span class="tabular-nums">-{{ formatJMD(invoice.contractors_levy_amount) }}</span>
                            </div>
                            <div class="flex justify-between text-base font-semibold border-t border-border pt-2">
                                <span>Net Receivable</span>
                                <span class="tabular-nums">{{ formatJMD(invoice.net_receivable) }}</span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Notes & Payment Instructions -->
                <div v-if="invoice.notes || business.payment_instructions" class="mt-10 border-t border-border pt-6 space-y-4">
                    <div v-if="invoice.notes">
                        <div class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-1">Notes</div>
                        <p class="text-sm text-muted-foreground whitespace-pre-line">{{ invoice.notes }}</p>
                    </div>
                    <div v-if="business.payment_instructions">
                        <div class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-1">Payment Instructions</div>
                        <p class="text-sm text-muted-foreground whitespace-pre-line">{{ business.payment_instructions }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Send Email Dialog -->
        <Dialog v-model:visible="showSendDialog" header="Send Invoice" modal :style="{ width: '28rem' }" class="print:hidden">
            <div class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    Select recipients for <span class="font-medium text-foreground">{{ invoice.invoice_number }}</span>
                </p>

                <div v-if="availableRecipients.length" class="space-y-3">
                    <div v-for="recipient in availableRecipients" :key="recipient.email" class="flex items-center gap-3">
                        <Checkbox
                            :modelValue="selectedRecipients.includes(recipient.email)"
                            @update:modelValue="(checked) => {
                                if (checked) selectedRecipients.push(recipient.email);
                                else selectedRecipients = selectedRecipients.filter(e => e !== recipient.email);
                            }"
                            :binary="true"
                        />
                        <div class="text-sm">
                            <div class="font-medium text-foreground">{{ recipient.label }}</div>
                            <div class="text-muted-foreground">{{ recipient.email }}
                                <span class="ml-1 text-xs">· {{ recipient.type }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-sm text-muted-foreground">
                    No emails found for this client. Add one below.
                </div>

                <div class="border-t border-border pt-4">
                    <InputLabel value="Add another email" />
                    <div class="flex gap-2 mt-1">
                        <InputText
                            v-model="customEmail"
                            type="email"
                            placeholder="email@example.com"
                            fluid
                            @keydown.enter.prevent="addCustomEmail"
                        />
                        <Button label="Add" text size="small" @click="addCustomEmail" :disabled="!customEmail.trim()" />
                    </div>
                </div>

                <!-- Show manually added emails -->
                <div v-if="selectedRecipients.filter(e => !availableRecipients.find(r => r.email === e)).length" class="space-y-2">
                    <div
                        v-for="email in selectedRecipients.filter(e => !availableRecipients.find(r => r.email === e))"
                        :key="email"
                        class="flex items-center justify-between text-sm bg-muted/20 rounded-lg px-3 py-2"
                    >
                        <span>{{ email }}</span>
                        <Button
                            icon="pi pi-times"
                            text
                            severity="danger"
                            size="small"
                            @click="selectedRecipients = selectedRecipients.filter(e => e !== email)"
                        />
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex items-center gap-3 justify-end">
                    <Button label="Cancel" text size="small" @click="showSendDialog = false" />
                    <Button
                        label="Send"
                        icon="pi pi-send"
                        size="small"
                        :loading="sending"
                        :disabled="selectedRecipients.length === 0"
                        @click="sendInvoice"
                    />
                </div>
            </template>
        </Dialog>
    </AuthenticatedLayout>
</template>
