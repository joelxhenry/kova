<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import { useConfirm } from 'primevue/useconfirm';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    /**
     * @type {{
     *   month: string,
     *   rows: Array<{category_id:number,name:string,type:string,planned:number,actual:number,variance:number,percent:number,over:boolean,targeted:boolean}>,
     *   totals: {planned:number,actual:number,variance:number}
     * }}
     */
    report: { type: Object, required: true },
    /** @type {Array<{id:number,transaction_category_id:number,type:string,period:string,amount:string,category:{id:number,name:string}|null}>} */
    targets: { type: Array, default: () => [] },
    /** @type {Array<{id:number,name:string,kind:string}>} */
    categories: { type: Array, default: () => [] },
    /** @type {string} */
    month: { type: String, required: true },
});

const { formatJMD } = useCurrencyFormatter();
const confirmDialog = useConfirm();

// Match a report row back to its underlying target row for edit/delete actions.
const targetByKey = computed(() => {
    const map = {};
    for (const t of props.targets) {
        map[`${t.transaction_category_id}-${t.type}`] = t;
    }
    return map;
});

const rowTarget = (row) => targetByKey.value[`${row.category_id}-${row.type}`] ?? null;

const monthLabel = computed(() => {
    const [year, month] = props.month.split('-').map(Number);
    return new Date(year, month - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

const shiftMonth = (delta) => {
    const [year, month] = props.month.split('-').map(Number);
    const d = new Date(year, month - 1 + delta, 1);
    const next = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    router.get('/budget/targets', { month: next }, { preserveState: true, preserveScroll: true });
};

const signedAmount = (row) => (row.type === 'income' ? '+' : '−') + formatJMD(row.actual);

const deleteTarget = (target) => {
    confirmDialog.require({
        message: 'Delete this budget target? Your actual spend is unaffected.',
        header: 'Delete Target',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/budget/targets/${target.id}`, { preserveScroll: true }),
    });
};
</script>

<template>
    <Head title="Budget Targets" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6 md:mb-10">
                <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">Budget vs Actual</h1>
                <Link href="/budget/targets/create" class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent/10 text-accent font-medium text-sm rounded-full hover:bg-accent/20 transition-all duration-200">
                    Set target
                </Link>
            </div>

            <!-- Month selector (FR-6.4) -->
            <div class="flex items-center gap-3 mb-8">
                <Button icon="pi pi-chevron-left" text severity="secondary" size="small" aria-label="Previous month" @click="shiftMonth(-1)" />
                <span class="min-w-44 text-center font-medium tabular-nums">{{ monthLabel }}</span>
                <Button icon="pi pi-chevron-right" text severity="secondary" size="small" aria-label="Next month" @click="shiftMonth(1)" />
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="rounded-2xl border border-border p-5">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Planned</p>
                    <p class="mt-1 text-lg font-bold tabular-nums">{{ formatJMD(report.totals.planned) }}</p>
                </div>
                <div class="rounded-2xl border border-border p-5">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Actual</p>
                    <p class="mt-1 text-lg font-bold tabular-nums">{{ formatJMD(report.totals.actual) }}</p>
                </div>
                <div class="rounded-2xl border border-border p-5">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Remaining</p>
                    <p
                        class="mt-1 text-lg font-bold tabular-nums"
                        :class="report.totals.variance < 0 ? 'text-rose-600' : 'text-emerald-600'"
                    >{{ formatJMD(report.totals.variance) }}</p>
                </div>
            </div>

            <div v-if="report.rows.length === 0" class="py-20 text-center text-muted-foreground">
                <p class="text-lg">Nothing to compare yet.</p>
                <p class="mt-2 text-sm">Set a category target or record some transactions for this month.</p>
            </div>

            <DataTable v-else :value="report.rows" dataKey="category_id">
                <Column field="name" header="Category">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ data.name }}</span>
                            <Tag v-if="!data.targeted" value="no target" severity="secondary" />
                        </div>
                    </template>
                </Column>
                <Column field="type" header="Type">
                    <template #body="{ data }">
                        <span class="capitalize text-muted-foreground text-sm">{{ data.type }}</span>
                    </template>
                </Column>
                <Column field="planned" header="Planned">
                    <template #body="{ data }">
                        <span class="tabular-nums">{{ data.targeted ? formatJMD(data.planned) : '—' }}</span>
                    </template>
                </Column>
                <Column field="actual" header="Actual">
                    <template #body="{ data }">
                        <span class="tabular-nums font-medium" :class="data.type === 'income' ? 'text-emerald-600' : 'text-rose-600'">{{ signedAmount(data) }}</span>
                    </template>
                </Column>
                <Column header="Used" :style="{ width: '14rem' }">
                    <template #body="{ data }">
                        <div v-if="data.targeted" class="flex items-center gap-3">
                            <ProgressBar
                                :value="Math.min(data.percent, 100)"
                                :showValue="false"
                                :style="{ height: '0.5rem', flex: '1' }"
                                :pt="{ value: { class: data.over ? '!bg-rose-500' : '' } }"
                            />
                            <span class="tabular-nums text-xs w-12 text-right" :class="data.over ? 'text-rose-600 font-semibold' : 'text-muted-foreground'">{{ data.percent }}%</span>
                        </div>
                        <span v-else class="text-muted-foreground text-sm">—</span>
                    </template>
                </Column>
                <Column field="variance" header="Variance">
                    <template #body="{ data }">
                        <span
                            v-if="data.targeted"
                            class="tabular-nums"
                            :class="data.variance < 0 ? 'text-rose-600' : 'text-muted-foreground'"
                        >{{ formatJMD(data.variance) }}</span>
                        <span v-else class="text-muted-foreground">—</span>
                    </template>
                </Column>
                <Column header="" :style="{ width: '7rem' }">
                    <template #body="{ data }">
                        <div v-if="rowTarget(data)" class="flex items-center justify-end gap-2">
                            <Link :href="`/budget/targets/${rowTarget(data).id}/edit`">
                                <Button icon="pi pi-pencil" text severity="secondary" size="small" />
                            </Link>
                            <Button icon="pi pi-trash" text severity="danger" size="small" @click="deleteTarget(rowTarget(data))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </section>
    </AuthenticatedLayout>
</template>
