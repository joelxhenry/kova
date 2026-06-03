<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';
import { useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    /** @type {Array<{id:number,name:string,type:string,current_balance:string,is_active:boolean}>} */
    accounts: { type: Array, default: () => [] },
    /** @type {{debit_total:number,credit_total:number,net_worth:number}} */
    summary: { type: Object, required: true },
});

const { formatJMD } = useCurrencyFormatter();
const confirmDialog = useConfirm();

// Sort so DataTable subheader grouping keeps debit accounts above credit ones.
const sortedAccounts = computed(() =>
    [...props.accounts].sort((a, b) => a.type.localeCompare(b.type)),
);

const typeLabel = (type) => (type === 'debit' ? 'Debit accounts' : 'Credit accounts');

const toggleActive = (account) => {
    router.put(`/budget/accounts/${account.id}`, {
        is_active: account.is_active,
    }, { preserveScroll: true, preserveState: true });
};

const deleteAccount = (account) => {
    confirmDialog.require({
        message: `Delete "${account.name}"? Accounts with transactions cannot be deleted.`,
        header: 'Delete Account',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/budget/accounts/${account.id}`, { preserveScroll: true }),
    });
};

// Transfer dialog
const showTransfer = ref(false);
const accountOptions = computed(() =>
    props.accounts.map(a => ({ label: `${a.name} (${typeLabel(a.type).replace(' accounts', '')})`, value: a.id })),
);

const transferForm = useForm({
    from_account_id: null,
    to_account_id: null,
    amount: null,
    date: new Date(),
    description: '',
});

const formatDate = (d) => d ? d.toISOString().split('T')[0] : null;

const openTransfer = () => {
    transferForm.reset();
    transferForm.date = new Date();
    transferForm.clearErrors();
    showTransfer.value = true;
};

const submitTransfer = () => {
    transferForm.transform((data) => ({
        ...data,
        date: formatDate(data.date),
    })).post('/budget/transfers', {
        preserveScroll: true,
        onSuccess: () => { showTransfer.value = false; },
    });
};
</script>

<template>
    <Head title="Accounts" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20">
            <div class="flex items-center justify-between mb-6 md:mb-10">
                <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">Accounts</h1>
                <div class="flex items-center gap-3">
                    <Button label="Transfer" icon="pi pi-arrow-right-arrow-left" outlined size="small" @click="openTransfer" />
                    <Link href="/budget/accounts/create" class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent/10 text-accent font-medium text-sm rounded-full hover:bg-accent/20 transition-all duration-200">
                        Add account
                    </Link>
                </div>
            </div>

            <!-- Net worth summary -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="p-5 rounded-2xl border border-border">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Assets (debit)</p>
                    <p class="mt-1 text-xl font-bold tabular-nums">{{ formatJMD(summary.debit_total) }}</p>
                </div>
                <div class="p-5 rounded-2xl border border-border">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Liabilities (credit)</p>
                    <p class="mt-1 text-xl font-bold tabular-nums">{{ formatJMD(summary.credit_total) }}</p>
                </div>
                <div class="p-5 rounded-2xl border border-border bg-accent/5">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Net worth</p>
                    <p class="mt-1 text-xl font-bold tabular-nums">{{ formatJMD(summary.net_worth) }}</p>
                </div>
            </div>

            <div v-if="accounts.length === 0" class="py-20 text-center text-muted-foreground">
                <p class="text-lg">No accounts yet.</p>
                <p class="mt-2 text-sm">Add a debit or credit account to start tracking balances.</p>
            </div>

            <DataTable
                v-else
                :value="sortedAccounts"
                rowGroupMode="subheader"
                groupRowsBy="type"
                sortField="type"
                :sortOrder="1"
                dataKey="id"
            >
                <template #groupheader="slotProps">
                    <span class="font-semibold text-sm">{{ typeLabel(slotProps.data.type) }}</span>
                </template>
                <Column field="name" header="Name">
                    <template #body="{ data }">
                        <span class="font-medium">{{ data.name }}</span>
                    </template>
                </Column>
                <Column field="current_balance" header="Balance">
                    <template #body="{ data }">
                        <span class="tabular-nums">{{ formatJMD(data.current_balance) }}</span>
                    </template>
                </Column>
                <Column header="Active">
                    <template #body="{ data }">
                        <Checkbox v-model="data.is_active" :binary="true" @change="toggleActive(data)" />
                    </template>
                </Column>
                <Column header="" :style="{ width: '8rem' }">
                    <template #body="{ data }">
                        <div class="flex items-center justify-end gap-2">
                            <Link :href="`/budget/accounts/${data.id}/edit`">
                                <Button icon="pi pi-pencil" text severity="secondary" size="small" />
                            </Link>
                            <Button icon="pi pi-trash" text severity="danger" size="small" @click="deleteAccount(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </section>

        <!-- Transfer dialog -->
        <Dialog v-model:visible="showTransfer" modal header="Transfer between accounts" :style="{ width: '28rem' }">
            <form @submit.prevent="submitTransfer" class="space-y-5">
                <div>
                    <InputLabel value="From" />
                    <Select v-model="transferForm.from_account_id" :options="accountOptions" optionLabel="label" optionValue="value" placeholder="Source account" fluid :invalid="!!transferForm.errors.from_account_id" />
                    <InputError :message="transferForm.errors.from_account_id" />
                </div>
                <div>
                    <InputLabel value="To" />
                    <Select v-model="transferForm.to_account_id" :options="accountOptions" optionLabel="label" optionValue="value" placeholder="Destination account" fluid :invalid="!!transferForm.errors.to_account_id" />
                    <InputError :message="transferForm.errors.to_account_id" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Amount" />
                        <InputNumber v-model="transferForm.amount" :min="0.01" :minFractionDigits="2" :maxFractionDigits="2" fluid :invalid="!!transferForm.errors.amount" />
                        <InputError :message="transferForm.errors.amount" />
                    </div>
                    <div>
                        <InputLabel value="Date" />
                        <DatePicker v-model="transferForm.date" dateFormat="yy-mm-dd" showIcon fluid :invalid="!!transferForm.errors.date" />
                        <InputError :message="transferForm.errors.date" />
                    </div>
                </div>
                <div>
                    <InputLabel value="Description" />
                    <InputText v-model="transferForm.description" placeholder="Optional note" fluid :invalid="!!transferForm.errors.description" />
                    <InputError :message="transferForm.errors.description" />
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Button type="button" label="Cancel" text severity="secondary" @click="showTransfer = false" />
                    <Button type="submit" label="Transfer" :loading="transferForm.processing" />
                </div>
            </form>
        </Dialog>
    </AuthenticatedLayout>
</template>
