<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';

const props = defineProps({
    taxProfile: { type: Object, default: null },
    statutoryRates: { type: Object, default: () => ({}) },
});

const businessTypes = [
    { value: 'specified_services', label: 'Specified Services (IT, Engineering, Management, Accounting)' },
    { value: 'construction', label: 'Construction' },
    { value: 'haulage', label: 'Haulage' },
    { value: 'tillage', label: 'Tillage' },
    { value: 'other', label: 'Other' },
];

const parseDate = (d) => d ? new Date(d) : null;

const form = useForm({
    trn: props.taxProfile?.trn ?? '',
    business_type: props.taxProfile?.business_type ?? null,
    is_gct_registered: props.taxProfile?.is_gct_registered ?? false,
    gct_registration_date: parseDate(props.taxProfile?.gct_registration_date),
    fiscal_year_start: parseDate(props.taxProfile?.fiscal_year_start),
});

const formatDate = (d) => d ? d.toISOString().split('T')[0] : null;

const submit = () => {
    form.transform((data) => ({
        ...data,
        gct_registration_date: formatDate(data.gct_registration_date),
        fiscal_year_start: formatDate(data.fiscal_year_start),
    })).put('/tax-profile');
};

const formatRate = (key) => {
    const rate = props.statutoryRates[key];
    return rate ? parseFloat(rate.value).toFixed(2) : '—';
};

const formatCurrency = (key) => {
    const rate = props.statutoryRates[key];
    if (!rate) return '—';
    return '$' + new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(parseFloat(rate.value));
};
</script>

<template>
    <Head title="Tax Profile" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20 max-w-2xl">
            <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">
                Tax Profile
            </h1>
            <p class="mt-3 text-muted-foreground text-base">
                Configure your tax identity. This drives all calculations, withholding logic, and TAJ form generation.
            </p>

            <form @submit.prevent="submit" class="mt-10 space-y-8">
                <!-- TRN -->
                <div>
                    <InputLabel value="Tax Registration Number (TRN)" />
                    <InputText v-model="form.trn" placeholder="123456789" maxlength="9" fluid :invalid="!!form.errors.trn" />
                    <InputError :message="form.errors.trn" />
                    <p class="mt-1.5 text-xs text-muted-foreground">9-digit number issued by TAJ</p>
                </div>

                <!-- Business Type -->
                <div>
                    <InputLabel value="Business Type" />
                    <Select
                        v-model="form.business_type"
                        :options="businessTypes"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select your business type"
                        fluid
                        :invalid="!!form.errors.business_type"
                    />
                    <InputError :message="form.errors.business_type" />
                    <p class="mt-1.5 text-xs text-muted-foreground">
                        Determines withholding tax rate: {{ formatRate('withholding_tax_rate') }}% for specified services, {{ formatRate('contractors_levy_rate') }}% for construction/haulage/tillage
                    </p>
                </div>

                <!-- GCT Registration -->
                <div class="pt-6">
                    <div class="flex items-start gap-3">
                        <Checkbox v-model="form.is_gct_registered" :binary="true" />
                        <div>
                            <span class="text-sm font-medium text-foreground">GCT Registered</span>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Required when annual turnover exceeds {{ formatCurrency('gct_registration_threshold') }}. Enables {{ formatRate('gct_rate') }}% GCT on invoices.
                            </p>
                        </div>
                    </div>

                    <div v-if="form.is_gct_registered" class="mt-4">
                        <InputLabel value="GCT Registration Date" />
                        <DatePicker v-model="form.gct_registration_date" dateFormat="yy-mm-dd" showIcon fluid :invalid="!!form.errors.gct_registration_date" />
                        <InputError :message="form.errors.gct_registration_date" />
                    </div>
                </div>

                <!-- Fiscal Year -->
                <div class="pt-6">
                    <InputLabel value="Fiscal Year Start" />
                    <DatePicker v-model="form.fiscal_year_start" dateFormat="yy-mm-dd" showIcon fluid :invalid="!!form.errors.fiscal_year_start" />
                    <InputError :message="form.errors.fiscal_year_start" />
                    <p class="mt-1.5 text-xs text-muted-foreground">
                        Defaults to January 1 if not set. Most Jamaican self-employed use the calendar year.
                    </p>
                </div>

                <div class="pt-4">
                    <Button type="submit" label="Save tax profile" :loading="form.processing" text />
                </div>
            </form>

            <!-- Statutory Rates (read-only) -->
            <div class="mt-16 pt-6">
                <h2 class="text-lg font-semibold tracking-tight mb-1">Current Statutory Rates</h2>
                <p class="text-xs text-muted-foreground mb-6">
                    These rates are set by the system administrator and apply to all calculations.
                </p>

                <div class="space-y-3">
                    <div
                        v-for="(rate, key) in statutoryRates"
                        :key="key"
                        class="flex items-center justify-between py-2 border-b border-border last:border-b-0"
                    >
                        <div>
                            <span class="text-sm text-foreground">{{ rate.label }}</span>
                            <span v-if="rate.effective_from" class="ml-2 text-xs text-muted-foreground tabular-nums">
                                from {{ rate.effective_from.split('T')[0] }}
                            </span>
                        </div>
                        <span class="text-sm tabular-nums font-medium text-foreground">
                            {{ parseFloat(rate.value).toLocaleString('en-JM') }}
                        </span>
                    </div>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
