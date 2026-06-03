<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Chart from 'primevue/chart';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    /** @type {Array<{id:number,name:string,type:string,current_balance:string,is_active:boolean}>} */
    accounts: { type: Array, default: () => [] },
    /** @type {{debit_total:number, credit_total:number, net_worth:number}} */
    summary: { type: Object, required: true },
    /** @type {Array<{id:number,type:string,amount:string,date:string,description:string,account:{name:string}|null,category:{name:string}|null}>} */
    recentTransactions: { type: Array, default: () => [] },
    /** @type {Array<{id:number,type:string,amount:string,expected_date:string,description:string,account:{name:string}|null}>} */
    upcomingExpected: { type: Array, default: () => [] },
    /** @type {{labels:string[], aggregate:number[], alerts:Array<{name:string,date:string,balance:number}>}} */
    projection: { type: Object, required: true },
});

const { formatJMD } = useCurrencyFormatter();

const activeAccounts = computed(() => props.accounts.filter(a => a.is_active));

const signedAmount = (t) => (t.type === 'income' ? '+' : '−') + formatJMD(t.amount);

const chartData = computed(() => ({
    labels: props.projection.labels,
    datasets: [{
        label: 'Net worth',
        data: props.projection.aggregate,
        borderColor: '#111827',
        backgroundColor: 'rgba(17, 24, 39, 0.06)',
        borderWidth: 2,
        fill: true,
        tension: 0.25,
        pointRadius: 0,
    }],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { intersect: false, mode: 'index' } },
    scales: {
        x: { ticks: { maxTicksLimit: 6, maxRotation: 0 }, grid: { display: false } },
        y: { ticks: { callback: (v) => '$' + Number(v).toLocaleString('en-US') } },
    },
};
</script>

<template>
    <Head title="Budget" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6 md:mb-10">
                <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">Budget</h1>
                <div class="flex flex-wrap items-center gap-2">
                    <Link href="/budget/accounts"><Button label="Accounts" icon="pi pi-wallet" outlined severity="secondary" size="small" /></Link>
                    <Link href="/budget/transactions"><Button label="Transactions" icon="pi pi-list" outlined severity="secondary" size="small" /></Link>
                    <Link href="/budget/recurring"><Button label="Recurring" icon="pi pi-sync" outlined severity="secondary" size="small" /></Link>
                    <Link href="/budget/expected"><Button label="Expected" icon="pi pi-calendar-clock" outlined severity="secondary" size="small" /></Link>
                    <Link href="/budget/targets"><Button label="Targets" icon="pi pi-bullseye" outlined severity="secondary" size="small" /></Link>
                    <Link href="/budget/projections"><Button label="Projections" icon="pi pi-chart-line" outlined severity="secondary" size="small" /></Link>
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
                <p class="text-lg">Welcome to budgeting.</p>
                <p class="mt-2 text-sm">Start by adding a debit or credit account.</p>
                <Link href="/budget/accounts/create" class="inline-flex items-center gap-2 mt-5 px-5 py-2.5 bg-accent/10 text-accent font-medium text-sm rounded-full hover:bg-accent/20 transition-all duration-200">
                    Add account
                </Link>
            </div>

            <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Account balance cards -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Accounts</h2>
                        <Link href="/budget/accounts" class="text-xs text-accent font-medium hover:underline">Manage</Link>
                    </div>
                    <Link
                        v-for="account in activeAccounts"
                        :key="account.id"
                        href="/budget/accounts"
                        class="flex items-center justify-between p-4 rounded-2xl border border-border hover:bg-muted/20 transition-colors duration-150"
                    >
                        <div class="flex items-center gap-3">
                            <span class="font-medium">{{ account.name }}</span>
                            <Tag :value="account.type === 'credit' ? 'Credit' : 'Debit'" :severity="account.type === 'credit' ? 'warn' : 'info'" />
                        </div>
                        <span class="tabular-nums font-semibold">{{ formatJMD(account.current_balance) }}</span>
                    </Link>
                </div>

                <!-- Recent ledger entries -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Recent activity</h2>
                        <Link href="/budget/transactions" class="text-xs text-accent font-medium hover:underline">View all</Link>
                    </div>
                    <div v-if="recentTransactions.length === 0" class="text-sm text-muted-foreground py-6 text-center">
                        No transactions yet.
                        <Link href="/budget/transactions/create" class="block mt-1 text-accent hover:underline">Record one</Link>
                    </div>
                    <div v-else class="rounded-2xl border border-border divide-y divide-border">
                        <div
                            v-for="t in recentTransactions"
                            :key="t.id"
                            class="flex items-center justify-between p-4"
                        >
                            <div class="min-w-0">
                                <div class="text-sm font-medium truncate">{{ t.description }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ t.account?.name }} · {{ t.date?.split('T')[0] }}
                                </div>
                            </div>
                            <span
                                class="tabular-nums text-sm font-medium shrink-0 ml-2"
                                :class="t.type === 'income' ? 'text-emerald-600' : 'text-rose-600'"
                            >{{ signedAmount(t) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Upcoming expected items (B6) -->
                <div class="lg:col-span-2 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Upcoming expected</h2>
                        <Link href="/budget/expected" class="text-xs text-accent font-medium hover:underline">Manage</Link>
                    </div>
                    <div v-if="upcomingExpected.length === 0" class="text-sm text-muted-foreground py-6 text-center">
                        Nothing anticipated yet.
                        <Link href="/budget/expected/create" class="block mt-1 text-accent hover:underline">Add an expected item</Link>
                    </div>
                    <div v-else class="rounded-2xl border border-border divide-y divide-border">
                        <div
                            v-for="e in upcomingExpected"
                            :key="e.id"
                            class="flex items-center justify-between p-4"
                        >
                            <div class="min-w-0">
                                <div class="text-sm font-medium truncate">{{ e.description }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ e.account?.name ?? 'Unassigned' }} · {{ e.expected_date?.split('T')[0] }}
                                </div>
                            </div>
                            <span
                                class="tabular-nums text-sm font-medium shrink-0 ml-2"
                                :class="e.type === 'income' ? 'text-emerald-600' : 'text-rose-600'"
                            >{{ signedAmount(e) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Mini projection preview -->
                <div class="lg:col-span-2 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider">30-day projection</h2>
                        <Link href="/budget/projections" class="text-xs text-accent font-medium hover:underline">Full projection</Link>
                    </div>
                    <p v-if="projection.alerts.length" class="text-sm text-rose-600">
                        Heads up: {{ projection.alerts[0].name }} is projected to go negative on {{ projection.alerts[0].date }}.
                    </p>
                    <div class="p-4 sm:p-6 rounded-2xl border border-border">
                        <Chart type="line" :data="chartData" :options="chartOptions" class="h-48 sm:h-56" />
                    </div>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
