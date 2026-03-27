<script setup>
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Select from 'primevue/select';
import Button from 'primevue/button';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';
import { useFiscalYear } from '@/Composables/useFiscalYear.js';

const props = defineProps({
    formData: { type: Object, required: true },
    year: { type: Number, required: true },
    snapshots: { type: Array, default: () => [] },
    viewingSnapshot: { type: Number, default: null },
});

const page = usePage();
const { formatJMD } = useCurrencyFormatter();
const { year: selectedYear, years, changeYear } = useFiscalYear(props.year);

const generate = () => {
    router.post('/tax-form/generate', { year: props.year });
};

const printForm = () => {
    window.print();
};

const comp = props.formData.computation;
const expenses = props.formData.expenses;
const taxpayer = props.formData.taxpayer;
</script>

<template>
    <Head :title="`Tax Form ${year}`" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20 max-w-3xl">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tight leading-tight">
                        Tax Form — S04
                    </h1>
                    <p class="mt-2 text-muted-foreground text-sm">
                        Self-Employed Annual Return preview for TAJ filing.
                    </p>
                </div>
                <Select
                    v-model="selectedYear"
                    :options="years"
                    optionLabel="label"
                    optionValue="value"
                    @change="changeYear(selectedYear)"
                />
            </div>

            <div
                v-if="page.props.flash.status"
                class="mb-6 bg-accent/10 rounded-xl px-4 py-3 text-sm text-foreground"
            >
                {{ page.props.flash.status }}
            </div>

            <div v-if="viewingSnapshot" class="mb-6 bg-muted/50 rounded-xl px-4 py-3 text-sm text-muted-foreground">
                Viewing saved snapshot. <Link :href="`/tax-form?year=${year}`" class="text-accent font-medium hover:underline">View live data</Link>
            </div>

            <!-- Form Preview -->
            <div class="bg-card rounded-2xl shadow-sm p-6 md:p-8 print:shadow-none print:rounded-none print:p-0" id="tax-form">
                <!-- Header -->
                <div class="border-b border-border pb-6 mb-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-lg font-bold">Self-Employed Annual Return (S04)</h2>
                            <p class="text-sm text-muted-foreground mt-1">Tax Year {{ year }}</p>
                        </div>
                        <div class="text-right text-sm">
                            <div class="font-medium">{{ taxpayer.name }}</div>
                            <div class="text-muted-foreground">TRN: {{ taxpayer.trn ?? 'Not set' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Section 1: Income -->
                <div class="mb-8">
                    <h3 class="text-sm font-medium text-muted-foreground mb-3">Section 1 — Income</h3>
                    <div class="flex justify-between py-2 border-b border-border/50">
                        <span>Gross Professional / Business Income</span>
                        <span class="tabular-nums font-medium">{{ formatJMD(comp.net_statutory_income + expenses.total) }}</span>
                    </div>
                </div>

                <!-- Section 2: Expenses -->
                <div class="mb-8">
                    <h3 class="text-sm font-medium text-muted-foreground mb-3">Section 2 — Allowable Expenses</h3>
                    <div
                        v-for="(amount, category) in expenses.by_category"
                        :key="category"
                        class="flex justify-between py-1.5 text-sm"
                    >
                        <span class="text-muted-foreground">{{ category }}</span>
                        <span class="tabular-nums">{{ formatJMD(amount) }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-t border-border/50 mt-2 font-medium">
                        <span>Total Expenses</span>
                        <span class="tabular-nums">{{ formatJMD(expenses.total) }}</span>
                    </div>
                </div>

                <!-- Section 3: Tax Computation -->
                <div class="mb-8">
                    <h3 class="text-sm font-medium text-muted-foreground mb-3">Section 3 — Tax Computation</h3>

                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between py-1.5 font-medium">
                            <span>Net Statutory Income</span>
                            <span class="tabular-nums">{{ formatJMD(comp.net_statutory_income) }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 text-muted-foreground">
                            <span>Less: Tax-Free Threshold</span>
                            <span class="tabular-nums">{{ formatJMD(comp.tax_free_threshold) }}</span>
                        </div>

                        <div class="border-t border-border/50 pt-2 mt-2"></div>

                        <div class="flex justify-between py-1.5">
                            <span>Tax on first bracket (25%)</span>
                            <span class="tabular-nums">{{ formatJMD(comp.tax_on_25_bracket) }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 text-muted-foreground text-xs">
                            <span>Taxable amount: {{ formatJMD(comp.taxable_25_bracket) }}</span>
                            <span></span>
                        </div>
                        <div class="flex justify-between py-1.5">
                            <span>Tax on remaining (30%)</span>
                            <span class="tabular-nums">{{ formatJMD(comp.tax_on_30_bracket) }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 text-muted-foreground text-xs">
                            <span>Taxable amount: {{ formatJMD(comp.taxable_30_bracket) }}</span>
                            <span></span>
                        </div>

                        <div class="flex justify-between py-2 border-t border-border/50 mt-2 font-medium">
                            <span>Total Income Tax</span>
                            <span class="tabular-nums">{{ formatJMD(comp.total_income_tax) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Statutory Contributions -->
                <div class="mb-8">
                    <h3 class="text-sm font-medium text-muted-foreground mb-3">Section 4 — Statutory Contributions</h3>
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between py-1.5">
                            <span>NIS Contribution</span>
                            <span class="tabular-nums">{{ formatJMD(comp.nis_contribution) }}</span>
                        </div>
                        <div class="flex justify-between py-1.5">
                            <span>NHT Contribution</span>
                            <span class="tabular-nums">{{ formatJMD(comp.nht_contribution) }}</span>
                        </div>
                        <div class="flex justify-between py-1.5">
                            <span>Education Tax</span>
                            <span class="tabular-nums">{{ formatJMD(comp.education_tax) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Summary -->
                <div class="border-t-2 border-foreground pt-6">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between py-1.5 font-medium">
                            <span>Total Tax Liability</span>
                            <span class="tabular-nums">{{ formatJMD(comp.total_tax_liability) }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 text-muted-foreground">
                            <span>Less: Withholding Tax Credits</span>
                            <span class="tabular-nums">-{{ formatJMD(comp.withholding_credits) }}</span>
                        </div>
                        <div class="flex justify-between py-3 border-t border-border text-lg font-bold">
                            <span>{{ comp.net_tax_payable >= 0 ? 'Net Tax Payable' : 'Refund Due' }}</span>
                            <span class="tabular-nums" :class="comp.net_tax_payable < 0 ? 'text-accent' : ''">
                                {{ formatJMD(Math.abs(comp.net_tax_payable)) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-8 flex flex-wrap items-center gap-4 print:hidden">
                <Button label="Generate Snapshot" @click="generate" />
                <Button label="Print / Save PDF" severity="secondary" outlined @click="printForm" />
            </div>

            <!-- Snapshot History -->
            <div v-if="snapshots.length > 0" class="mt-10 print:hidden">
                <h3 class="text-sm font-medium text-muted-foreground mb-3">Saved Snapshots</h3>
                <div class="space-y-2">
                    <Link
                        v-for="snap in snapshots"
                        :key="snap.id"
                        :href="`/tax-form/snapshot/${snap.id}`"
                        class="flex items-center justify-between py-3 px-4 rounded-xl transition-all duration-200"
                        :class="viewingSnapshot === snap.id ? 'bg-accent/10' : 'bg-card hover:bg-muted/30'"
                    >
                        <div>
                            <span class="text-sm font-medium">{{ snap.form_type }} — {{ snap.tax_year }}</span>
                            <span class="ml-2 text-xs text-muted-foreground">
                                {{ new Date(snap.generated_at).toLocaleDateString('en-JM', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                            </span>
                        </div>
                        <span v-if="viewingSnapshot === snap.id" class="text-xs text-accent font-medium">Viewing</span>
                    </Link>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
