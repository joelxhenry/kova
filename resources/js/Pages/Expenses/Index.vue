<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';
import { useConfirm } from 'primevue/useconfirm';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    expenses: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const { formatJMD } = useCurrencyFormatter();
const confirmDialog = useConfirm();

const categoryOptions = [
    { label: 'All categories', value: null },
    ...props.categories.map(c => ({ label: c.name, value: c.id })),
];

const categoryId = ref(props.filters.category_id ? Number(props.filters.category_id) : null);
const from = ref(props.filters.from ? new Date(props.filters.from) : '');
const to = ref(props.filters.to ? new Date(props.filters.to) : '');

const formatDateParam = (d) => d ? d.toISOString().split('T')[0] : undefined;

const applyFilters = () => {
    router.get('/expenses', {
        category_id: categoryId.value || undefined,
        from: formatDateParam(from.value),
        to: formatDateParam(to.value),
    }, { preserveState: true });
};

const deleteExpense = (expense) => {
    confirmDialog.require({
        message: 'Delete this expense?',
        header: 'Delete Expense',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/expenses/${expense.id}`),
    });
};
</script>

<template>
    <Head title="Expenses" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20">
            <div class="flex items-center justify-between mb-6 md:mb-10">
                <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">Expenses</h1>
                <Link href="/expenses/create" class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent/10 text-accent font-medium text-sm rounded-full hover:bg-accent/20 transition-all duration-200">
                    Add expense
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-8">
                <Select v-model="categoryId" :options="categoryOptions" optionLabel="label" optionValue="value" placeholder="All categories" @change="applyFilters" />
                <DatePicker v-model="from" placeholder="From" dateFormat="yy-mm-dd" showIcon @date-select="applyFilters" />
                <DatePicker v-model="to" placeholder="To" dateFormat="yy-mm-dd" showIcon @date-select="applyFilters" />
            </div>

            <div v-if="expenses.data.length === 0" class="py-20 text-center text-muted-foreground">
                <p class="text-lg">No expenses yet.</p>
                <p class="mt-2 text-sm">Log business expenses to track your spending.</p>
            </div>

            <div v-else>
                <div
                    v-for="expense in expenses.data"
                    :key="expense.id"
                    class="flex items-center justify-between py-4 border-b border-border"
                >
                    <div class="min-w-0 flex-1">
                        <span class="text-sm font-medium text-foreground">{{ expense.description }}</span>
                        <div class="mt-0.5 text-xs text-muted-foreground">
                            {{ expense.category?.name }}
                            <span class="mx-1">·</span>
                            {{ expense.date_incurred?.split('T')[0] }}
                        </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0 ml-3">
                        <span class="tabular-nums text-sm font-medium">{{ formatJMD(expense.amount) }}</span>
                        <Link :href="`/expenses/${expense.id}/edit`">
                            <Button icon="pi pi-pencil" text severity="secondary" size="small" />
                        </Link>
                        <Button icon="pi pi-trash" text severity="danger" size="small" @click="deleteExpense(expense)" />
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="expenses.last_page > 1" class="mt-8 flex gap-2">
                <Link
                    v-for="link in expenses.links"
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
