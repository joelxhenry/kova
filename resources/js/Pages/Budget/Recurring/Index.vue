<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';
import { useRecurrence } from '@/Composables/useRecurrence.js';

const props = defineProps({
    /** @type {Array<{id:number,type:string,amount:string,frequency:string,start_date:string,end_date:string|null,next_run_date:string,is_active:boolean,description:string,account:{name:string}|null,transfer_account:{name:string,type:string}|null,category:{name:string}|null}>} */
    recurring: { type: Array, default: () => [] },
});

// A recurring transfer into a credit account is a credit-card payment; surface
// it as such so it reads naturally in the list. (Eloquent serialises the
// transferAccount relation as snake_case `transfer_account`.)
const isPayment = (rule) => rule.type === 'transfer' && rule.transfer_account?.type === 'credit';

const { formatJMD } = useCurrencyFormatter();
const { recurrenceLabel } = useRecurrence();
const confirmDialog = useConfirm();

const cancelRule = (rule) => {
    confirmDialog.require({
        message: 'Cancel this recurring rule? Already-generated transactions are kept; no new ones will be created.',
        header: 'Cancel Recurring Rule',
        acceptClass: 'p-button-danger',
        accept: () => router.post(`/budget/recurring/${rule.id}/cancel`, {}, { preserveScroll: true }),
    });
};

const signedAmount = (rule) => {
    if (rule.type === 'income') return '+' + formatJMD(rule.amount);
    if (rule.type === 'expense') return '−' + formatJMD(rule.amount);
    return formatJMD(rule.amount);
};

const amountClass = (rule) => {
    if (rule.type === 'income') return 'text-emerald-600';
    if (rule.type === 'expense') return 'text-rose-600';
    return 'text-muted-foreground';
};
</script>

<template>
    <Head title="Recurring" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20">
            <div class="flex items-center justify-between mb-6 md:mb-10">
                <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">Recurring</h1>
                <Link href="/budget/recurring/create" class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent/10 text-accent font-medium text-sm rounded-full hover:bg-accent/20 transition-all duration-200">
                    Add recurring rule
                </Link>
            </div>

            <div v-if="recurring.length === 0" class="py-20 text-center text-muted-foreground">
                <p class="text-lg">No recurring rules yet.</p>
                <p class="mt-2 text-sm">Schedule salaries, rent, or subscriptions to post automatically.</p>
            </div>

            <DataTable v-else :value="recurring" dataKey="id">
                <Column field="description" header="Description">
                    <template #body="{ data }">
                        <span class="font-medium">{{ data.description }}</span>
                        <Tag v-if="isPayment(data)" value="Payment" severity="info" class="ml-2" />
                    </template>
                </Column>
                <Column field="account" header="Account">
                    <template #body="{ data }">
                        <span>{{ data.account?.name }}</span>
                        <span v-if="data.type === 'transfer' && data.transfer_account" class="text-muted-foreground"> → {{ data.transfer_account.name }}</span>
                    </template>
                </Column>
                <Column field="frequency" header="Schedule">
                    <template #body="{ data }">
                        <span class="text-sm text-muted-foreground">{{ recurrenceLabel(data) }}</span>
                    </template>
                </Column>
                <Column field="next_run_date" header="Next run">
                    <template #body="{ data }">
                        <span class="tabular-nums text-sm">{{ data.is_active ? data.next_run_date?.split('T')[0] : '—' }}</span>
                    </template>
                </Column>
                <Column field="amount" header="Amount">
                    <template #body="{ data }">
                        <span class="tabular-nums font-medium" :class="amountClass(data)">{{ signedAmount(data) }}</span>
                    </template>
                </Column>
                <Column field="is_active" header="Status">
                    <template #body="{ data }">
                        <Tag :value="data.is_active ? 'Active' : 'Cancelled'" :severity="data.is_active ? 'success' : 'secondary'" />
                    </template>
                </Column>
                <Column header="" :style="{ width: '10rem' }">
                    <template #body="{ data }">
                        <div class="flex items-center justify-end gap-2">
                            <Link :href="`/budget/recurring/${data.id}/edit`">
                                <Button icon="pi pi-pencil" text severity="secondary" size="small" />
                            </Link>
                            <Button
                                v-if="data.is_active"
                                icon="pi pi-ban"
                                text
                                severity="danger"
                                size="small"
                                @click="cancelRule(data)"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </section>
    </AuthenticatedLayout>
</template>
