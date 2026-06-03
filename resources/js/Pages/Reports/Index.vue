<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    summary: { type: Object, required: true },
    byStatus: { type: Array, default: () => [] },
    byClient: { type: Array, default: () => [] },
    byCategory: { type: Array, default: () => [] },
    monthly: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
    /** @type {{month:string,rows:Array<{category_id:number,name:string,type:string,planned:number,actual:number,variance:number,percent:number,over:boolean}>,totals:{planned:number,actual:number,variance:number}}|null} */
    budgetAdherence: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
});

const { formatJMD } = useCurrencyFormatter();

const adherenceMonthLabel = computed(() => {
    if (!props.budgetAdherence) return '';
    const [year, month] = props.budgetAdherence.month.split('-').map(Number);
    return new Date(year, month - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

const clientOptions = [
    { label: 'All clients', value: null },
    ...props.clients.map(c => ({ label: c.name, value: c.id })),
];

const from = ref(props.filters.from ? new Date(props.filters.from) : '');
const to = ref(props.filters.to ? new Date(props.filters.to) : '');
const clientId = ref(props.filters.client_id ? Number(props.filters.client_id) : null);

const formatDateParam = (d) => d ? d.toISOString().split('T')[0] : undefined;

const applyFilters = () => {
    router.get('/reports', {
        from: formatDateParam(from.value),
        to: formatDateParam(to.value),
        client_id: clientId.value || undefined,
    }, { preserveState: true });
};

const clearFilters = () => {
    from.value = '';
    to.value = '';
    clientId.value = null;
    router.get('/reports');
};

const hasFilters = () => from.value || to.value || clientId.value;

const maxMonthly = Math.max(...props.monthly.map(m => Math.max(m.invoiced, m.expenses)), 1);

const statusLabels = {
    draft: 'Draft',
    sent: 'Sent',
    paid: 'Paid',
    overdue: 'Overdue',
    cancelled: 'Cancelled',
};
</script>

<template>
    <Head title="Reports" />

    <AuthenticatedLayout>
        <section class="py-4 md:py-12 lg:py-16">
            <div class="flex items-center justify-between mb-4 md:mb-6">
                <h1 class="text-xl md:text-2xl font-bold tracking-tight">Reports</h1>
                <Button v-if="hasFilters()" label="Clear" text severity="secondary" size="small" @click="clearFilters" />
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2 md:gap-3 mb-5 md:mb-8">
                <DatePicker v-model="from" placeholder="From" dateFormat="yy-mm-dd" showIcon @date-select="applyFilters" />
                <DatePicker v-model="to" placeholder="To" dateFormat="yy-mm-dd" showIcon @date-select="applyFilters" />
                <Select v-model="clientId" :options="clientOptions" optionLabel="label" optionValue="value" placeholder="All clients" @change="applyFilters" />
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-3 gap-px md:gap-4 mb-5 md:mb-8 bg-border md:bg-transparent rounded-xl md:rounded-none overflow-hidden">
                <div class="bg-background md:bg-card md:rounded-2xl md:shadow-sm p-3 md:p-4">
                    <div class="text-[11px] text-muted-foreground">Paid</div>
                    <div class="text-sm md:text-lg font-bold tabular-nums mt-0.5">{{ formatJMD(summary.totalPaid) }}</div>
                </div>
                <div class="bg-background md:bg-card md:rounded-2xl md:shadow-sm p-3 md:p-4">
                    <div class="text-[11px] text-muted-foreground">Spent</div>
                    <div class="text-sm md:text-lg font-bold tabular-nums mt-0.5">{{ formatJMD(summary.totalExpenses) }}</div>
                </div>
                <div class="bg-background md:bg-card md:rounded-2xl md:shadow-sm p-3 md:p-4">
                    <div class="text-[11px] text-muted-foreground">Net</div>
                    <div class="text-sm md:text-lg font-bold tabular-nums mt-0.5" :class="summary.netIncome < 0 ? 'text-accent' : ''">
                        {{ formatJMD(summary.netIncome) }}
                    </div>
                </div>
                <div class="bg-background md:bg-card md:rounded-2xl md:shadow-sm p-3 md:p-4">
                    <div class="text-[11px] text-muted-foreground">Invoices</div>
                    <div class="text-sm md:text-lg font-bold tabular-nums mt-0.5">{{ summary.invoiceCount }}</div>
                </div>
                <div class="bg-background md:bg-card md:rounded-2xl md:shadow-sm p-3 md:p-4">
                    <div class="text-[11px] text-muted-foreground">Outstanding</div>
                    <div class="text-sm md:text-lg font-bold tabular-nums mt-0.5">{{ formatJMD(summary.totalPending) }}</div>
                </div>
                <div class="bg-background md:bg-card md:rounded-2xl md:shadow-sm p-3 md:p-4">
                    <div class="text-[11px] text-muted-foreground">Overdue</div>
                    <div class="text-sm md:text-lg font-bold tabular-nums mt-0.5" :class="summary.overdueCount > 0 ? 'text-accent' : ''">
                        {{ summary.overdueCount }}
                    </div>
                </div>
            </div>

            <!-- Monthly breakdown -->
            <div v-if="monthly.length > 0" class="mb-5 md:mb-8 md:bg-card md:rounded-2xl md:shadow-sm md:p-6">
                <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-3 md:mb-4">Monthly</h2>

                <div class="space-y-3">
                    <div v-for="month in monthly" :key="month.label" class="text-sm">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-muted-foreground w-16 shrink-0">{{ month.label }}</span>
                            <div class="flex gap-4 tabular-nums text-xs">
                                <span>{{ formatJMD(month.invoiced) }} in</span>
                                <span class="text-muted-foreground">{{ formatJMD(month.expenses) }} out</span>
                            </div>
                        </div>
                        <div class="flex gap-1 h-1.5 md:h-2">
                            <div
                                class="bg-foreground/20 rounded-full"
                                :style="{ width: `${(month.invoiced / maxMonthly) * 100}%` }"
                            ></div>
                            <div
                                class="bg-accent/30 rounded-full"
                                :style="{ width: `${(month.expenses / maxMonthly) * 100}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budget adherence (current month) -->
            <div v-if="budgetAdherence && budgetAdherence.rows.length > 0" class="mb-5 md:mb-8 md:bg-card md:rounded-2xl md:shadow-sm md:p-6">
                <div class="flex items-center justify-between mb-3 md:mb-4">
                    <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Budget vs actual · {{ adherenceMonthLabel }}</h2>
                    <Link href="/budget/targets" class="text-xs text-accent font-medium hover:underline">Manage targets</Link>
                </div>

                <div class="space-y-3">
                    <div v-for="row in budgetAdherence.rows" :key="`${row.category_id}-${row.type}`" class="text-sm">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="font-medium">{{ row.name }}</span>
                            <div class="flex gap-3 tabular-nums text-xs">
                                <span :class="row.over ? 'text-accent font-semibold' : ''">{{ formatJMD(row.actual) }}</span>
                                <span class="text-muted-foreground">/ {{ formatJMD(row.planned) }}</span>
                            </div>
                        </div>
                        <ProgressBar
                            :value="Math.min(row.percent, 100)"
                            :showValue="false"
                            :style="{ height: '0.5rem' }"
                            :pt="{ value: { class: row.over ? '!bg-rose-500' : '' } }"
                        />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-0 md:gap-6">
                <!-- By status -->
                <div v-if="byStatus.length > 0" class="md:bg-card md:rounded-2xl md:shadow-sm md:p-6 mb-5 md:mb-0">
                    <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-3">By status</h2>
                    <div class="space-y-2">
                        <div v-for="row in byStatus" :key="row.status" class="flex items-center justify-between text-sm">
                            <span>{{ statusLabels[row.status] || row.status }} <span class="text-muted-foreground text-xs">({{ row.count }})</span></span>
                            <span class="tabular-nums font-medium">{{ formatJMD(row.total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- By category -->
                <div v-if="byCategory.length > 0" class="md:bg-card md:rounded-2xl md:shadow-sm md:p-6 mb-5 md:mb-0">
                    <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-3">By category</h2>
                    <div class="space-y-2">
                        <div v-for="row in byCategory" :key="row.category" class="flex items-center justify-between text-sm">
                            <span>{{ row.category }} <span class="text-muted-foreground text-xs">({{ row.count }})</span></span>
                            <span class="tabular-nums font-medium">{{ formatJMD(row.total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- By client -->
                <div v-if="byClient.length > 0" class="md:bg-card md:rounded-2xl md:shadow-sm md:p-6 lg:col-span-2">
                    <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-3">Top clients</h2>
                    <div class="space-y-2">
                        <div v-for="row in byClient" :key="row.client" class="flex items-center justify-between text-sm">
                            <span>{{ row.client }} <span class="text-muted-foreground text-xs">({{ row.count }})</span></span>
                            <span class="tabular-nums font-medium">{{ formatJMD(row.total) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
