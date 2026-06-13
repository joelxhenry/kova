<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Chart from 'primevue/chart';
import SelectButton from 'primevue/selectbutton';
import MultiSelect from 'primevue/multiselect';
import Message from 'primevue/message';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    /**
     * @type {{
     *   labels: string[],
     *   datasets: Array<{account_id:number,name:string,type:string,points:number[]}>,
     *   aggregate: number[],
     *   alerts: Array<{account_id:number,name:string,date:string,balance:number}>,
     *   starting_net_worth: number,
     *   ending_net_worth: number,
     *   lowest_net_worth: number,
     *   expected_events: Array<{account_id:number|null,name:string,date:string,type:string,amount:number,signed_delta:number}>,
     *   interest: {cost:number,earned:number,net_worth_impact:number,by_account:Array<{account_id:number,name:string,type:string,accrued:number}>}
     * }}
     */
    projection: { type: Object, required: true },
    /** @type {Array<{id:number,name:string,type:string}>} */
    accounts: { type: Array, default: () => [] },
    /** @type {{timeframe:string, account_ids:number[]}} */
    filters: { type: Object, default: () => ({ timeframe: '30d', account_ids: [] }) },
});

const { formatJMD } = useCurrencyFormatter();

const timeframeOptions = [
    { label: '30 days', value: '30d' },
    { label: '3 months', value: '3m' },
    { label: '6 months', value: '6m' },
    { label: '1 year', value: '1y' },
];

const accountOptions = computed(() =>
    props.accounts.map(a => ({ label: a.name, value: a.id })),
);

const timeframe = ref(props.filters.timeframe ?? '30d');
const selectedAccounts = ref([...(props.filters.account_ids ?? [])]);

const applyFilters = () => {
    router.get('/budget/projections', {
        timeframe: timeframe.value,
        account_ids: selectedAccounts.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

watch([timeframe, selectedAccounts], applyFilters, { deep: true });

// The earliest debit breach drives a highlighted marker on the net-worth line.
const breachIndex = computed(() => {
    if (!props.projection.alerts.length) return -1;
    const earliest = [...props.projection.alerts].sort((a, b) => a.date.localeCompare(b.date))[0];
    return props.projection.labels.indexOf(earliest.date);
});

const PALETTE = ['#10b981', '#f59e0b', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6', '#f43f5e', '#0ea5e9'];

// Indices on the net-worth line where a pending expected item lands (FR-5.4) —
// rendered as distinct amber markers so they read apart from recurring flow.
const expectedIndices = computed(() => {
    const set = new Set();
    for (const e of props.projection.expected_events ?? []) {
        const i = props.projection.labels.indexOf(e.date);
        if (i !== -1) set.add(i);
    }
    return set;
});

const chartData = computed(() => {
    const idx = breachIndex.value;
    const expected = expectedIndices.value;

    const aggregate = {
        label: 'Net worth',
        data: props.projection.aggregate,
        borderColor: '#111827',
        backgroundColor: 'rgba(17, 24, 39, 0.06)',
        borderWidth: 2.5,
        fill: true,
        tension: 0.25,
        pointRadius: props.projection.labels.map((_, i) => (i === idx ? 5 : expected.has(i) ? 4 : 0)),
        pointBackgroundColor: props.projection.labels.map((_, i) => (i === idx ? '#ef4444' : expected.has(i) ? '#f59e0b' : '#111827')),
    };

    const perAccount = props.projection.datasets.map((d, i) => ({
        label: d.name,
        data: d.points,
        borderColor: PALETTE[i % PALETTE.length],
        borderWidth: 1.5,
        borderDash: [4, 4],
        fill: false,
        tension: 0.25,
        pointRadius: 0,
    }));

    return { labels: props.projection.labels, datasets: [aggregate, ...perAccount] };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
        tooltip: {
            callbacks: {
                label: (ctx) => `${ctx.dataset.label}: $${Number(ctx.parsed.y).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
            },
        },
    },
    scales: {
        x: { ticks: { maxTicksLimit: 12, maxRotation: 0, autoSkip: true }, grid: { display: false } },
        y: {
            ticks: { callback: (value) => '$' + Number(value).toLocaleString('en-US') },
        },
    },
};
</script>

<template>
    <Head title="Projections" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6 md:mb-10">
                <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">Projections</h1>
                <div class="flex flex-wrap items-center gap-3">
                    <MultiSelect
                        v-model="selectedAccounts"
                        :options="accountOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="All accounts"
                        :maxSelectedLabels="2"
                        selectedItemsLabel="{0} accounts"
                        class="w-56"
                    />
                    <SelectButton v-model="timeframe" :options="timeframeOptions" optionLabel="label" optionValue="value" :allowEmpty="false" />
                </div>
            </div>

            <!-- Negative-balance alert (FR-4.5) -->
            <Message v-if="projection.alerts.length" severity="warn" :closable="false" class="mb-6">
                <div class="space-y-1">
                    <p class="font-medium">Projected to go below zero:</p>
                    <p v-for="alert in projection.alerts" :key="alert.account_id" class="text-sm">
                        <span class="font-medium">{{ alert.name }}</span> reaches {{ formatJMD(alert.balance) }} on {{ alert.date }}.
                    </p>
                </div>
            </Message>

            <!-- Summary cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="p-5 rounded-2xl border border-border">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Starting net worth</p>
                    <p class="mt-1 text-xl font-bold tabular-nums">{{ formatJMD(projection.starting_net_worth) }}</p>
                </div>
                <div class="p-5 rounded-2xl border border-border bg-accent/5">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Projected end</p>
                    <p class="mt-1 text-xl font-bold tabular-nums">{{ formatJMD(projection.ending_net_worth) }}</p>
                </div>
                <div class="p-5 rounded-2xl border border-border">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Lowest point</p>
                    <p class="mt-1 text-xl font-bold tabular-nums" :class="projection.lowest_net_worth < 0 ? 'text-rose-600' : ''">{{ formatJMD(projection.lowest_net_worth) }}</p>
                </div>
            </div>

            <div v-if="accounts.length === 0" class="py-20 text-center text-muted-foreground">
                <p class="text-lg">No active accounts to project.</p>
                <p class="mt-2 text-sm">Add an account and some recurring rules to see a forecast.</p>
            </div>

            <div v-else class="p-4 sm:p-6 rounded-2xl border border-border">
                <Chart type="line" :data="chartData" :options="chartOptions" class="h-[22rem] sm:h-[28rem]" />
            </div>

            <!-- Interest accrued over the window on rate-bearing accounts (B9) -->
            <div v-if="projection.interest && projection.interest.by_account.length" class="mt-6">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-violet-500"></span>
                    <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Projected interest in this window</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div class="p-5 rounded-2xl border border-border">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">Interest cost (credit)</p>
                        <p class="mt-1 text-xl font-bold tabular-nums text-rose-600">{{ formatJMD(projection.interest.cost) }}</p>
                    </div>
                    <div class="p-5 rounded-2xl border border-border">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">Interest earned (savings)</p>
                        <p class="mt-1 text-xl font-bold tabular-nums text-emerald-600">{{ formatJMD(projection.interest.earned) }}</p>
                    </div>
                    <div class="p-5 rounded-2xl border border-border bg-accent/5">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">Net worth impact</p>
                        <p class="mt-1 text-xl font-bold tabular-nums" :class="projection.interest.net_worth_impact < 0 ? 'text-rose-600' : 'text-emerald-600'">{{ formatJMD(projection.interest.net_worth_impact) }}</p>
                    </div>
                </div>
                <div class="rounded-2xl border border-border divide-y divide-border">
                    <div
                        v-for="row in projection.interest.by_account"
                        :key="row.account_id"
                        class="flex items-center justify-between p-4"
                    >
                        <div class="min-w-0">
                            <div class="text-sm font-medium">{{ row.name }}</div>
                            <div class="text-xs text-muted-foreground">{{ row.type === 'credit' ? 'Interest charged' : 'Interest earned' }}</div>
                        </div>
                        <span
                            class="tabular-nums text-sm font-medium shrink-0 ml-2"
                            :class="row.type === 'credit' ? 'text-rose-600' : 'text-emerald-600'"
                        >{{ '+' + formatJMD(row.accrued) }}</span>
                    </div>
                </div>
            </div>

            <!-- Pending expected items folded into the forecast (B6) -->
            <div v-if="projection.expected_events && projection.expected_events.length" class="mt-6">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Expected items in this window</h2>
                </div>
                <div class="rounded-2xl border border-border divide-y divide-border">
                    <div
                        v-for="(e, i) in projection.expected_events"
                        :key="i"
                        class="flex items-center justify-between p-4"
                    >
                        <div class="min-w-0">
                            <div class="text-sm font-medium">{{ e.name }}</div>
                            <div class="text-xs text-muted-foreground tabular-nums">{{ e.date }}</div>
                        </div>
                        <span
                            class="tabular-nums text-sm font-medium shrink-0 ml-2"
                            :class="e.type === 'income' ? 'text-emerald-600' : 'text-rose-600'"
                        >{{ (e.type === 'income' ? '+' : '−') + formatJMD(e.amount) }}</span>
                    </div>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
