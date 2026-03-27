<script setup>
import { useForm, Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';
import Select from 'primevue/select';
import Button from 'primevue/button';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    credits: { type: Array, default: () => [] },
    year: { type: Number, required: true },
    summary: { type: Object, required: true },
});

const page = usePage();
const { formatJMD } = useCurrencyFormatter();

const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 5 }, (_, i) => {
    const y = currentYear - 2 + i;
    return { label: String(y), value: y };
});

const selectedYear = ref(props.year);

const changeYear = () => {
    router.get('/withholding-credits', { year: selectedYear.value }, { preserveState: true });
};

const showForm = ref(false);

const form = useForm({
    amount: null,
    tax_year: props.year,
    date_withheld: new Date(),
    description: '',
});

const formatDate = (d) => d ? d.toISOString().split('T')[0] : null;

const submit = () => {
    form.transform((data) => ({
        ...data,
        date_withheld: formatDate(data.date_withheld),
    })).post('/withholding-credits', {
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};

const deleteCredit = (credit) => {
    if (confirm('Delete this withholding credit entry?')) {
        router.delete(`/withholding-credits/${credit.id}`);
    }
};
</script>

<template>
    <Head title="Withholding Credits" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20 max-w-3xl">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">Withholding Credits</h1>
                </div>
                <Select v-model="selectedYear" :options="yearOptions" optionLabel="label" optionValue="value" @change="changeYear" />
            </div>

            <div
                v-if="page.props.flash.status"
                class="mb-6 bg-accent/10 rounded-xl px-4 py-3 text-sm text-foreground"
            >
                {{ page.props.flash.status }}
            </div>

            <!-- Summary -->
            <div class="bg-card rounded-2xl shadow-sm p-6 mb-8">
                <h2 class="text-xs font-medium text-muted-foreground mb-4">{{ year }} Credit Summary</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div>
                        <div class="text-xs font-medium text-muted-foreground">Invoice WHT</div>
                        <div class="tabular-nums text-base font-medium mt-0.5">{{ formatJMD(summary.invoiceCredits) }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-muted-foreground">Manual</div>
                        <div class="tabular-nums text-base font-medium mt-0.5">{{ formatJMD(summary.manualCredits) }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-muted-foreground">Total</div>
                        <div class="tabular-nums text-lg font-bold text-accent mt-0.5">{{ formatJMD(summary.totalCredits) }}</div>
                    </div>
                </div>
            </div>

            <!-- Add Manual Credit -->
            <div class="mb-8">
                <Button v-if="!showForm" label="+ Add manual credit" text size="small" @click="showForm = true" />

                <div v-if="showForm" class="bg-card rounded-2xl shadow-sm p-6 mt-4">
                    <h3 class="text-sm font-semibold mb-4">Manual Withholding Credit</h3>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Amount (JMD)" />
                                <InputNumber v-model="form.amount" :min="0.01" :minFractionDigits="2" :maxFractionDigits="2" fluid :invalid="!!form.errors.amount" />
                                <InputError :message="form.errors.amount" />
                            </div>
                            <div>
                                <InputLabel value="Date Withheld" />
                                <DatePicker v-model="form.date_withheld" dateFormat="yy-mm-dd" showIcon fluid :invalid="!!form.errors.date_withheld" />
                                <InputError :message="form.errors.date_withheld" />
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Description" />
                            <InputText v-model="form.description" placeholder="e.g., WHT from client payment" fluid :invalid="!!form.errors.description" />
                            <InputError :message="form.errors.description" />
                        </div>
                        <div class="flex items-center gap-4">
                            <Button type="submit" label="Add credit" :loading="form.processing" text />
                            <Button type="button" label="Cancel" text severity="secondary" @click="showForm = false" />
                        </div>
                    </form>
                </div>
            </div>

            <!-- Credits Ledger -->
            <div v-if="credits.length === 0" class="py-12 text-center text-muted-foreground">
                <p class="text-base">No manual withholding credits for {{ year }}.</p>
                <p class="mt-1 text-sm">Invoice and income withholding are tracked automatically.</p>
            </div>

            <div v-else class="border-t border-border">
                <div class="text-xs font-medium text-muted-foreground py-3 border-b border-border">
                    Manual Credits Ledger
                </div>
                <div
                    v-for="credit in credits"
                    :key="credit.id"
                    class="flex items-center justify-between py-3 border-b border-border"
                >
                    <div>
                        <span class="text-sm font-medium text-foreground">{{ credit.description }}</span>
                        <div class="text-xs text-muted-foreground mt-0.5">
                            {{ credit.date_withheld?.split('T')[0] }}
                            <span class="ml-2 tabular-nums text-xs font-medium" :class="credit.source_type === 'invoice' ? 'text-accent' : ''">
                                {{ credit.source_type }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="tabular-nums text-sm font-medium">{{ formatJMD(credit.amount) }}</span>
                        <Button v-if="credit.source_type === 'manual'" label="Delete" text severity="danger" size="small" @click="deleteCredit(credit)" />
                    </div>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
