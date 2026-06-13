<script setup>
import { useForm, Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';
import { useConfirm } from 'primevue/useconfirm';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    /** @type {Array<{id:number,type:string,amount:string,expected_date:string,description:string,status:string,account_id:number|null,transfer_account_id:number|null,account:{name:string}|null,transfer_account:{name:string,type:string}|null,category:{name:string}|null}>} */
    expected: { type: Array, default: () => [] },
    /** @type {Array<{id:number,name:string,type:string}>} */
    accounts: { type: Array, default: () => [] },
    /** @type {{status?:string,type?:string}} */
    filters: { type: Object, default: () => ({}) },
});

const { formatJMD } = useCurrencyFormatter();
const confirmDialog = useConfirm();

const accountOptions = computed(() => props.accounts.map(a => ({ label: a.name, value: a.id })));
const debitOptions = computed(() => props.accounts.filter(a => a.type === 'debit').map(a => ({ label: a.name, value: a.id })));

const statusFilterOptions = [
    { label: 'All statuses', value: null },
    { label: 'Pending', value: 'pending' },
    { label: 'Realized', value: 'realized' },
    { label: 'Cancelled', value: 'cancelled' },
];

const typeFilterOptions = [
    { label: 'All types', value: null },
    { label: 'Income', value: 'income' },
    { label: 'Expense', value: 'expense' },
    { label: 'Payment', value: 'transfer' },
];

const isPayment = (item) => item.type === 'transfer';

const status = ref(props.filters.status ?? null);
const type = ref(props.filters.type ?? null);

const applyFilters = () => {
    router.get('/budget/expected', {
        status: status.value || undefined,
        type: type.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const signedAmount = (item) => {
    if (item.type === 'transfer') return formatJMD(item.amount);
    return (item.type === 'income' ? '+' : '−') + formatJMD(item.amount);
};

const amountClass = (item) => {
    if (item.type === 'transfer') return 'text-violet-600';
    return item.type === 'income' ? 'text-emerald-600' : 'text-rose-600';
};

const statusSeverity = (s) => (s === 'pending' ? 'info' : s === 'realized' ? 'success' : 'secondary');

const deleteItem = (item) => {
    confirmDialog.require({
        message: 'Delete this expected item? It will no longer appear in your projection.',
        header: 'Delete Expected Item',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/budget/expected/${item.id}`, { preserveScroll: true }),
    });
};

const cancelItem = (item) => {
    confirmDialog.require({
        message: 'Cancel this expected item? It is kept for history but removed from the projection.',
        header: 'Cancel Expected Item',
        acceptClass: 'p-button-danger',
        accept: () => router.post(`/budget/expected/${item.id}/cancel`, {}, { preserveScroll: true }),
    });
};

// Realize dialog state — confirm/adjust the account, date, and amount.
const realizeOpen = ref(false);
const realizeItem = ref(null);
const realizeForm = useForm({
    account_id: null,
    date: new Date(),
    amount: null,
});

// A payment realizes from a cash account; income/expense from any account.
const realizeAccountOptions = computed(() =>
    realizeItem.value && isPayment(realizeItem.value) ? debitOptions.value : accountOptions.value,
);

const openRealize = (item) => {
    realizeForm.clearErrors();
    realizeForm.account_id = item.account_id ?? null;
    realizeForm.date = item.expected_date ? new Date(item.expected_date) : new Date();
    realizeForm.amount = Number(item.amount);
    realizeItem.value = item;
    realizeOpen.value = true;
};

const formatDate = (d) => d ? d.toISOString().split('T')[0] : null;

const submitRealize = () => {
    realizeForm.transform((data) => ({
        account_id: data.account_id,
        date: formatDate(data.date),
        amount: data.amount,
    })).post(`/budget/expected/${realizeItem.value.id}/realize`, {
        preserveScroll: true,
        onSuccess: () => { realizeOpen.value = false; },
    });
};
</script>

<template>
    <Head title="Expected" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20">
            <div class="flex items-center justify-between mb-6 md:mb-10">
                <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">Expected</h1>
                <Link href="/budget/expected/create" class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent/10 text-accent font-medium text-sm rounded-full hover:bg-accent/20 transition-all duration-200">
                    Add expected item
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-8">
                <Select v-model="status" :options="statusFilterOptions" optionLabel="label" optionValue="value" placeholder="All statuses" @change="applyFilters" />
                <Select v-model="type" :options="typeFilterOptions" optionLabel="label" optionValue="value" placeholder="All types" @change="applyFilters" />
            </div>

            <div v-if="expected.length === 0" class="py-20 text-center text-muted-foreground">
                <p class="text-lg">No expected items yet.</p>
                <p class="mt-2 text-sm">Anticipate one-off income or expenses to sharpen your forecast.</p>
            </div>

            <DataTable v-else :value="expected" dataKey="id">
                <Column field="expected_date" header="Expected">
                    <template #body="{ data }">
                        <span class="tabular-nums text-sm">{{ data.expected_date?.split('T')[0] }}</span>
                    </template>
                </Column>
                <Column field="description" header="Description">
                    <template #body="{ data }">
                        <span class="font-medium">{{ data.description }}</span>
                    </template>
                </Column>
                <Column field="account" header="Account">
                    <template #body="{ data }">
                        <span :class="data.account ? '' : 'text-muted-foreground'">{{ data.account?.name ?? 'Unassigned' }}</span>
                        <span v-if="isPayment(data) && data.transfer_account" class="text-muted-foreground"> → {{ data.transfer_account.name }}</span>
                    </template>
                </Column>
                <Column field="category" header="Category">
                    <template #body="{ data }">
                        <Tag v-if="isPayment(data)" value="Payment" severity="info" />
                        <span v-else class="text-muted-foreground">{{ data.category?.name ?? '—' }}</span>
                    </template>
                </Column>
                <Column field="amount" header="Amount">
                    <template #body="{ data }">
                        <span class="tabular-nums font-medium" :class="amountClass(data)">{{ signedAmount(data) }}</span>
                    </template>
                </Column>
                <Column field="status" header="Status">
                    <template #body="{ data }">
                        <Tag :value="data.status" :severity="statusSeverity(data.status)" class="capitalize" />
                    </template>
                </Column>
                <Column header="" :style="{ width: '12rem' }">
                    <template #body="{ data }">
                        <div class="flex items-center justify-end gap-2">
                            <template v-if="data.status === 'pending'">
                                <Button icon="pi pi-check" label="Realize" text severity="success" size="small" @click="openRealize(data)" />
                                <Link :href="`/budget/expected/${data.id}/edit`">
                                    <Button icon="pi pi-pencil" text severity="secondary" size="small" />
                                </Link>
                                <Button icon="pi pi-ban" text severity="danger" size="small" @click="cancelItem(data)" />
                            </template>
                            <Button v-else icon="pi pi-trash" text severity="danger" size="small" @click="deleteItem(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </section>

        <!-- Realize dialog (FR-5.3) -->
        <Dialog v-model:visible="realizeOpen" modal :header="realizeItem && isPayment(realizeItem) ? 'Make this payment' : 'Realize Expected Item'" :style="{ width: '28rem' }">
            <p class="text-sm text-muted-foreground mb-5">
                This posts a real ledger transaction and adjusts the account balance. Confirm or adjust the details below.
            </p>
            <form @submit.prevent="submitRealize" class="space-y-5">
                <div v-if="realizeItem && isPayment(realizeItem) && realizeItem.transfer_account" class="p-3 rounded-xl border border-border bg-accent/5 text-sm">
                    Paying <span class="font-medium">{{ realizeItem.transfer_account.name }}</span>
                </div>
                <div>
                    <InputLabel :value="realizeItem && isPayment(realizeItem) ? 'Pay from' : 'Account'" />
                    <Select v-model="realizeForm.account_id" :options="realizeAccountOptions" optionLabel="label" optionValue="value" placeholder="Select an account" fluid :invalid="!!realizeForm.errors.account_id" />
                    <InputError :message="realizeForm.errors.account_id" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Amount" />
                        <InputNumber v-model="realizeForm.amount" :min="0.01" :minFractionDigits="2" :maxFractionDigits="2" fluid :invalid="!!realizeForm.errors.amount" />
                        <InputError :message="realizeForm.errors.amount" />
                    </div>
                    <div>
                        <InputLabel value="Date" />
                        <DatePicker v-model="realizeForm.date" dateFormat="yy-mm-dd" showIcon fluid :invalid="!!realizeForm.errors.date" />
                        <InputError :message="realizeForm.errors.date" />
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Button type="button" label="Cancel" text severity="secondary" @click="realizeOpen = false" />
                    <Button type="submit" label="Realize" :loading="realizeForm.processing" />
                </div>
            </form>
        </Dialog>
    </AuthenticatedLayout>
</template>
