<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    expenses: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    totals: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const { formatJMD } = useCurrencyFormatter();

const categoryOptions = [
    { label: 'All categories', value: null },
    ...props.categories.map(c => ({ label: c.name, value: c.id })),
];

const categoryId = ref(props.filters.category_id ? Number(props.filters.category_id) : null);
const from = ref(props.filters.from ? new Date(props.filters.from) : null);
const to = ref(props.filters.to ? new Date(props.filters.to) : null);

const formatDateParam = (d) => d ? d.toISOString().split('T')[0] : undefined;

const applyFilters = () => {
    router.get('/expenses', {
        category_id: categoryId.value || undefined,
        from: formatDateParam(from.value),
        to: formatDateParam(to.value),
    }, { preserveState: true });
};

const grandTotal = Object.values(props.totals).reduce((sum, t) => sum + Number(t), 0);

const getCategoryName = (id) => {
    const cat = props.categories.find(c => c.id === id);
    return cat?.name ?? 'Unknown';
};

const deleteExpense = (expense) => {
    if (confirm('Delete this expense?')) {
        router.delete(`/expenses/${expense.id}`);
    }
};
</script>

<template>
    <Head title="Expenses" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">Expenses</h1>
                </div>
                <Link href="/expenses/create" class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent/10 text-accent font-medium text-sm rounded-full hover:bg-accent/20 transition-all duration-200">
                    Add expense
                </Link>
            </div>

            <div
                v-if="page.props.flash.status"
                class="mb-6 bg-accent/10 rounded-xl px-4 py-3 text-sm text-foreground"
            >
                {{ page.props.flash.status }}
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-8">
                <Select v-model="categoryId" :options="categoryOptions" optionLabel="label" optionValue="value" placeholder="All categories" @change="applyFilters" />
                <DatePicker v-model="from" placeholder="From" dateFormat="yy-mm-dd" showIcon @date-select="applyFilters" />
                <DatePicker v-model="to" placeholder="To" dateFormat="yy-mm-dd" showIcon @date-select="applyFilters" />
            </div>

            <!-- Totals by Category -->
            <div v-if="Object.keys(totals).length > 0" class="mb-8 bg-card rounded-2xl shadow-sm p-6">
                <h2 class="text-xs font-medium text-muted-foreground mb-4">Totals by Category</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div v-for="(total, catId) in totals" :key="catId">
                        <div class="text-xs font-medium text-muted-foreground">{{ getCategoryName(Number(catId)) }}</div>
                        <div class="tabular-nums text-base font-medium mt-0.5">{{ formatJMD(total) }}</div>
                    </div>
                </div>
                <div class="mt-4 pt-4 flex justify-between items-center">
                    <span class="text-xs font-medium text-muted-foreground">Total</span>
                    <span class="tabular-nums text-lg font-bold">{{ formatJMD(grandTotal) }}</span>
                </div>
            </div>

            <div v-if="expenses.data.length === 0" class="py-20 text-center text-muted-foreground">
                <p class="text-lg">No expenses yet.</p>
                <p class="mt-2 text-sm">Log business expenses to offset your taxable income.</p>
            </div>

            <div v-else class="border-t border-border">
                <div
                    v-for="expense in expenses.data"
                    :key="expense.id"
                    class="flex items-center justify-between py-4 border-b border-border"
                >
                    <div>
                        <span class="text-base font-medium text-foreground">{{ expense.description }}</span>
                        <div class="mt-0.5 text-sm text-muted-foreground">
                            <span class="text-xs font-medium text-muted-foreground">{{ expense.category?.name }}</span>
                            <span class="mx-2">·</span>
                            {{ expense.date_incurred?.split('T')[0] }}
                            <span v-if="expense.receipt_path" class="ml-2 text-accent text-xs font-medium">Receipt</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="tabular-nums text-base font-medium">{{ formatJMD(expense.amount) }}</span>
                        <div class="flex items-center gap-2">
                            <Link :href="`/expenses/${expense.id}/edit`">
                                <Button label="Edit" text size="small" />
                            </Link>
                            <Button label="Delete" text severity="danger" size="small" @click="deleteExpense(expense)" />
                        </div>
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
