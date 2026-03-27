<script setup>
import { useForm, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import PrimaryButton from '@/Components/UI/PrimaryButton.vue';

const props = defineProps({
    taxProfile: { type: Object, default: null },
    statutoryRates: { type: Object, default: () => ({}) },
});

const page = usePage();

const businessTypes = [
    { value: 'specified_services', label: 'Specified Services (IT, Engineering, Management, Accounting)' },
    { value: 'construction', label: 'Construction' },
    { value: 'haulage', label: 'Haulage' },
    { value: 'tillage', label: 'Tillage' },
    { value: 'other', label: 'Other' },
];

const form = useForm({
    trn: props.taxProfile?.trn ?? '',
    business_type: props.taxProfile?.business_type ?? '',
    is_gct_registered: props.taxProfile?.is_gct_registered ?? false,
    gct_registration_date: props.taxProfile?.gct_registration_date?.split('T')[0] ?? '',
    fiscal_year_start: props.taxProfile?.fiscal_year_start?.split('T')[0] ?? '',
});

const submit = () => {
    form.put('/tax-profile');
};

const formatRate = (key) => {
    const rate = props.statutoryRates[key];
    return rate ? parseFloat(rate.value).toFixed(2) : '—';
};

const formatCurrency = (key) => {
    const rate = props.statutoryRates[key];
    if (!rate) return '—';
    return new Intl.NumberFormat('en-JM', {
        style: 'currency',
        currency: 'JMD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(parseFloat(rate.value));
};
</script>

<template>
    <Head title="Tax Profile" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20 max-w-2xl">
            <div class="h-1 w-16 bg-accent mb-6" />
            <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">
                Tax Profile
            </h1>
            <p class="mt-3 text-muted-foreground text-base">
                Configure your tax identity. This drives all calculations, withholding logic, and TAJ form generation.
            </p>

            <div
                v-if="page.props.flash.status"
                class="mt-8 border border-accent/30 bg-accent/5 px-4 py-3 text-sm text-foreground"
            >
                {{ page.props.flash.status }}
            </div>

            <form @submit.prevent="submit" class="mt-10 space-y-8">
                <!-- TRN -->
                <div>
                    <InputLabel value="Tax Registration Number (TRN)" />
                    <TextInput
                        v-model="form.trn"
                        type="text"
                        :error="form.errors.trn"
                        placeholder="123456789"
                        maxlength="9"
                    />
                    <InputError :message="form.errors.trn" />
                    <p class="mt-1.5 text-xs text-muted-foreground">9-digit number issued by TAJ</p>
                </div>

                <!-- Business Type -->
                <div>
                    <InputLabel value="Business Type" />
                    <select
                        v-model="form.business_type"
                        class="w-full h-12 md:h-14 bg-input border border-border px-4 text-base text-foreground outline-none transition-colors duration-150 focus:border-accent appearance-none"
                        :class="{ 'border-accent': form.errors.business_type, 'text-muted-foreground': !form.business_type }"
                    >
                        <option value="" disabled>Select your business type</option>
                        <option
                            v-for="type in businessTypes"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.business_type" />
                    <p class="mt-1.5 text-xs text-muted-foreground">
                        Determines withholding tax rate: {{ formatRate('withholding_tax_rate') }}% for specified services, {{ formatRate('contractors_levy_rate') }}% for construction/haulage/tillage
                    </p>
                </div>

                <!-- GCT Registration -->
                <div class="border-t border-border pt-8">
                    <div class="flex items-start gap-3">
                        <input
                            v-model="form.is_gct_registered"
                            type="checkbox"
                            class="mt-1 w-4 h-4 bg-input border border-border text-accent focus:ring-accent focus:ring-offset-background"
                        />
                        <div>
                            <span class="text-sm font-medium text-foreground">GCT Registered</span>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Required when annual turnover exceeds {{ formatCurrency('gct_registration_threshold') }}. Enables {{ formatRate('gct_rate') }}% GCT on invoices.
                            </p>
                        </div>
                    </div>

                    <div v-if="form.is_gct_registered" class="mt-4">
                        <InputLabel value="GCT Registration Date" />
                        <TextInput
                            v-model="form.gct_registration_date"
                            type="date"
                            :error="form.errors.gct_registration_date"
                        />
                        <InputError :message="form.errors.gct_registration_date" />
                    </div>
                </div>

                <!-- Fiscal Year -->
                <div class="border-t border-border pt-8">
                    <InputLabel value="Fiscal Year Start" />
                    <TextInput
                        v-model="form.fiscal_year_start"
                        type="date"
                        :error="form.errors.fiscal_year_start"
                    />
                    <InputError :message="form.errors.fiscal_year_start" />
                    <p class="mt-1.5 text-xs text-muted-foreground">
                        Defaults to January 1 if not set. Most Jamaican self-employed use the calendar year.
                    </p>
                </div>

                <div class="pt-4">
                    <PrimaryButton :disabled="form.processing">
                        Save tax profile
                    </PrimaryButton>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
