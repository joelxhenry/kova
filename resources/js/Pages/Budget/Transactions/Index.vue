<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';
import { useConfirm } from 'primevue/useconfirm';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    /** Paginated transactions ({ data, links, last_page, ... }). */
    transactions: { type: Object, required: true },
    /** @type {Array<{id:number,name:string,type:string}>} */
    accounts: { type: Array, default: () => [] },
    /** @type {Array<{id:number,name:string,kind:string}>} */
    categories: { type: Array, default: () => [] },
    /** @type {{account_id?:number,type?:string,category_id?:number,from?:string,to?:string}} */
    filters: { type: Object, default: () => ({}) },
});

const { formatJMD } = useCurrencyFormatter();
const confirmDialog = useConfirm();

const accountOptions = computed(() => [
    { label: 'All accounts', value: null },
    ...props.accounts.map(a => ({ label: a.name, value: a.id })),
]);

const typeOptions = [
    { label: 'All types', value: null },
    { label: 'Income', value: 'income' },
    { label: 'Expense', value: 'expense' },
];

const categoryOptions = computed(() => [
    { label: 'All categories', value: null },
    ...props.categories.map(c => ({ label: c.name, value: c.id })),
]);

const accountId = ref(props.filters.account_id ? Number(props.filters.account_id) : null);
const type = ref(props.filters.type ?? null);
const categoryId = ref(props.filters.category_id ? Number(props.filters.category_id) : null);
const from = ref(props.filters.from ? new Date(props.filters.from) : '');
const to = ref(props.filters.to ? new Date(props.filters.to) : '');

const formatDateParam = (d) => d ? d.toISOString().split('T')[0] : undefined;

const applyFilters = () => {
    router.get('/budget/transactions', {
        account_id: accountId.value || undefined,
        type: type.value || undefined,
        category_id: categoryId.value || undefined,
        from: formatDateParam(from.value),
        to: formatDateParam(to.value),
    }, { preserveState: true, preserveScroll: true });
};

const deleteTransaction = (transaction) => {
    confirmDialog.require({
        message: 'Delete this transaction? The account balance will be adjusted.',
        header: 'Delete Transaction',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/budget/transactions/${transaction.id}`, { preserveScroll: true }),
    });
};

// Income shows as a positive credit, expense as a negative debit.
const signedAmount = (t) => (t.type === 'income' ? '+' : '−') + formatJMD(t.amount);
</script>

<template>
    <Head title="Transactions" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20">
            <div class="flex items-center justify-between mb-6 md:mb-10">
                <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">Transactions</h1>
                <Link href="/budget/transactions/create" class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent/10 text-accent font-medium text-sm rounded-full hover:bg-accent/20 transition-all duration-200">
                    Add transaction
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-8">
                <Select v-model="accountId" :options="accountOptions" optionLabel="label" optionValue="value" placeholder="All accounts" @change="applyFilters" />
                <Select v-model="type" :options="typeOptions" optionLabel="label" optionValue="value" placeholder="All types" @change="applyFilters" />
                <Select v-model="categoryId" :options="categoryOptions" optionLabel="label" optionValue="value" placeholder="All categories" @change="applyFilters" />
                <DatePicker v-model="from" placeholder="From" dateFormat="yy-mm-dd" showIcon @date-select="applyFilters" />
                <DatePicker v-model="to" placeholder="To" dateFormat="yy-mm-dd" showIcon @date-select="applyFilters" />
            </div>

            <div v-if="transactions.data.length === 0" class="py-20 text-center text-muted-foreground">
                <p class="text-lg">No transactions yet.</p>
                <p class="mt-2 text-sm">Record income or expenses to build your ledger.</p>
            </div>

            <DataTable v-else :value="transactions.data" dataKey="id">
                <Column field="date" header="Date">
                    <template #body="{ data }">
                        <span class="tabular-nums text-sm">{{ data.date?.split('T')[0] }}</span>
                    </template>
                </Column>
                <Column field="account" header="Account">
                    <template #body="{ data }">
                        <span class="font-medium">{{ data.account?.name }}</span>
                    </template>
                </Column>
                <Column field="category" header="Category">
                    <template #body="{ data }">
                        <span class="text-muted-foreground">{{ data.category?.name ?? '—' }}</span>
                    </template>
                </Column>
                <Column field="description" header="Description">
                    <template #body="{ data }">
                        <span>{{ data.description }}</span>
                    </template>
                </Column>
                <Column field="amount" header="Amount">
                    <template #body="{ data }">
                        <span
                            class="tabular-nums font-medium"
                            :class="data.type === 'income' ? 'text-emerald-600' : 'text-rose-600'"
                        >{{ signedAmount(data) }}</span>
                    </template>
                </Column>
                <Column header="" :style="{ width: '8rem' }">
                    <template #body="{ data }">
                        <div class="flex items-center justify-end gap-2">
                            <Link :href="`/budget/transactions/${data.id}/edit`">
                                <Button icon="pi pi-pencil" text severity="secondary" size="small" />
                            </Link>
                            <Button icon="pi pi-trash" text severity="danger" size="small" @click="deleteTransaction(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <!-- Pagination -->
            <div v-if="transactions.last_page > 1" class="mt-8 flex gap-2">
                <Link
                    v-for="link in transactions.links"
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
