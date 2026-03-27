<script setup>
import { useForm, Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';
import Drawer from 'primevue/drawer';

const props = defineProps({
    business: { type: Object, required: true },
    invoicing: { type: Object, required: true },
    email: { type: Object, required: true },
    invoiceNumberPreview: { type: String, required: true },
    taxProfile: { type: Object, default: null },
    statutoryRates: { type: Object, default: () => ({}) },
});

const activeTab = ref('business');
const showRatesDrawer = ref(false);

const tabs = [
    { key: 'business', label: 'Business Profile' },
    { key: 'invoicing', label: 'Invoicing' },
    { key: 'email', label: 'Email Templates' },
    { key: 'tax', label: 'Tax Profile' },
];

// --- Business form ---
const businessForm = useForm({
    business_name: props.business.business_name ?? '',
    business_address_line_1: props.business.business_address_line_1 ?? '',
    business_address_line_2: props.business.business_address_line_2 ?? '',
    business_city: props.business.business_city ?? '',
    business_state_or_parish: props.business.business_state_or_parish ?? '',
    business_postal_code: props.business.business_postal_code ?? '',
    business_country: props.business.business_country ?? 'Jamaica',
    business_phone: props.business.business_phone ?? '',
    business_email: props.business.business_email ?? '',
    payment_terms: props.business.payment_terms ?? '',
    payment_instructions: props.business.payment_instructions ?? '',
    logo: null,
});

const onLogoChange = (e) => { businessForm.logo = e.target.files[0] ?? null; };

const submitBusiness = () => {
    businessForm.transform((data) => {
        const fd = { ...data };
        if (!fd.logo) delete fd.logo;
        return fd;
    }).post('/settings/business', { forceFormData: true, method: 'put', preserveScroll: true });
};

const removeLogo = () => { router.delete('/settings/logo', { preserveScroll: true }); };

// --- Invoice form ---
const invoiceForm = useForm({
    invoice_prefix: props.invoicing.invoice_prefix,
    invoice_separator: props.invoicing.invoice_separator,
    invoice_next_number: props.invoicing.invoice_next_number,
    invoice_padding: props.invoicing.invoice_padding,
});

const invoicePreview = computed(() => {
    const num = String(invoiceForm.invoice_next_number ?? 1).padStart(invoiceForm.invoice_padding ?? 4, '0');
    return `${invoiceForm.invoice_prefix ?? 'INV'}${invoiceForm.invoice_separator ?? '-'}${num}`;
});

const submitInvoice = () => { invoiceForm.put('/settings/invoicing', { preserveScroll: true }); };

// --- Email form ---
const emailForm = useForm({
    invoice_email_subject: props.email.invoice_email_subject,
    invoice_email_greeting: props.email.invoice_email_greeting,
    invoice_email_body: props.email.invoice_email_body,
    invoice_email_footer: props.email.invoice_email_footer,
    invoice_email_include_payment_instructions: props.email.invoice_email_include_payment_instructions,
});

const submitEmail = () => { emailForm.put('/settings/email', { preserveScroll: true }); };

// --- Tax Profile form ---
const businessTypes = [
    { value: 'specified_services', label: 'Specified Services (IT, Engineering, Management, Accounting)' },
    { value: 'construction', label: 'Construction' },
    { value: 'haulage', label: 'Haulage' },
    { value: 'tillage', label: 'Tillage' },
    { value: 'other', label: 'Other' },
];

const parseDate = (d) => d ? new Date(d) : null;
const formatDate = (d) => d ? d.toISOString().split('T')[0] : null;

const taxForm = useForm({
    trn: props.taxProfile?.trn ?? '',
    business_type: props.taxProfile?.business_type ?? null,
    is_gct_registered: props.taxProfile?.is_gct_registered ?? false,
    gct_registration_date: parseDate(props.taxProfile?.gct_registration_date),
    fiscal_year_start: parseDate(props.taxProfile?.fiscal_year_start),
});

const submitTax = () => {
    taxForm.transform((data) => ({
        ...data,
        gct_registration_date: formatDate(data.gct_registration_date),
        fiscal_year_start: formatDate(data.fiscal_year_start),
    })).put('/settings/tax-profile', { preserveScroll: true });
};

const formatRate = (key) => {
    const rate = props.statutoryRates[key];
    return rate ? parseFloat(rate.value).toFixed(2) : '—';
};

const formatCurrency = (key) => {
    const rate = props.statutoryRates[key];
    if (!rate) return '—';
    return new Intl.NumberFormat('en-JM', { style: 'currency', currency: 'JMD', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(parseFloat(rate.value));
};
</script>

<template>
    <Head title="Settings" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20">
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-3xl md:text-4xl font-bold tracking-tight leading-tight">Settings</h1>
            </div>

            <div class="flex justify-between gap-8 items-start">
                <!-- Main content -->
                <div class="min-w-0 max-w-3xl">
                    <!-- Tabs -->
                    <div class="flex flex-wrap gap-1 mb-8">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                            :class="activeTab === tab.key ? 'text-foreground bg-muted/50' : 'text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- Business Profile -->
                    <form v-show="activeTab === 'business'" @submit.prevent="submitBusiness" class="space-y-6">
                        <div>
                            <InputLabel value="Business Name" />
                            <InputText v-model="businessForm.business_name" fluid placeholder="Your business or trading name" :invalid="!!businessForm.errors.business_name" />
                            <InputError :message="businessForm.errors.business_name" />
                        </div>

                        <div>
                            <InputLabel value="Logo" />
                            <div v-if="business.business_logo_path" class="mb-3 flex items-center gap-4">
                                <img :src="`/storage/${business.business_logo_path}`" alt="Logo" class="h-12 rounded-lg" />
                                <Button label="Remove" text severity="danger" size="small" @click="removeLogo" type="button" />
                            </div>
                            <input type="file" accept=".jpg,.jpeg,.png,.svg" @change="onLogoChange" class="block w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:border file:border-border file:text-sm file:font-semibold file:bg-input file:text-foreground file:rounded-lg hover:file:bg-muted file:transition-colors file:duration-150 file:cursor-pointer" />
                            <InputError :message="businessForm.errors.logo" />
                            <p class="mt-1.5 text-xs text-muted-foreground">JPG, PNG, or SVG. Max 2MB.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div><InputLabel value="Address Line 1" /><InputText v-model="businessForm.business_address_line_1" fluid /></div>
                            <div><InputLabel value="Address Line 2" /><InputText v-model="businessForm.business_address_line_2" fluid /></div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div><InputLabel value="City" /><InputText v-model="businessForm.business_city" fluid /></div>
                            <div><InputLabel value="State / Parish" /><InputText v-model="businessForm.business_state_or_parish" fluid /></div>
                            <div><InputLabel value="Postal Code" /><InputText v-model="businessForm.business_postal_code" fluid /></div>
                            <div><InputLabel value="Country" /><InputText v-model="businessForm.business_country" fluid /></div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div><InputLabel value="Phone" /><InputText v-model="businessForm.business_phone" fluid /></div>
                            <div><InputLabel value="Email" /><InputText v-model="businessForm.business_email" type="email" fluid :invalid="!!businessForm.errors.business_email" /><InputError :message="businessForm.errors.business_email" /></div>
                        </div>

                        <div><InputLabel value="Payment Terms" /><Textarea v-model="businessForm.payment_terms" rows="2" fluid placeholder="e.g., Payment due within 14 days of invoice date." /></div>
                        <div><InputLabel value="Payment Instructions" /><Textarea v-model="businessForm.payment_instructions" rows="3" fluid placeholder="e.g., Bank name, account number, routing info..." /></div>

                        <Button type="submit" label="Save business profile" :loading="businessForm.processing" />
                    </form>

                    <!-- Invoice Settings -->
                    <form v-show="activeTab === 'invoicing'" @submit.prevent="submitInvoice" class="space-y-6">
                        <div class="bg-card rounded-2xl shadow-sm p-6 mb-6">
                            <div class="text-sm text-muted-foreground mb-1">Next invoice number preview</div>
                            <div class="tabular-nums text-2xl font-bold">{{ invoicePreview }}</div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div><InputLabel value="Prefix" /><InputText v-model="invoiceForm.invoice_prefix" fluid :invalid="!!invoiceForm.errors.invoice_prefix" /><InputError :message="invoiceForm.errors.invoice_prefix" /></div>
                            <div><InputLabel value="Separator" /><InputText v-model="invoiceForm.invoice_separator" fluid :invalid="!!invoiceForm.errors.invoice_separator" /><InputError :message="invoiceForm.errors.invoice_separator" /></div>
                            <div><InputLabel value="Next Number" /><InputNumber v-model="invoiceForm.invoice_next_number" :min="1" fluid :invalid="!!invoiceForm.errors.invoice_next_number" /><InputError :message="invoiceForm.errors.invoice_next_number" /></div>
                            <div><InputLabel value="Padding" /><InputNumber v-model="invoiceForm.invoice_padding" :min="1" :max="10" fluid :invalid="!!invoiceForm.errors.invoice_padding" /><InputError :message="invoiceForm.errors.invoice_padding" /></div>
                        </div>

                        <Button type="submit" label="Save invoice settings" :loading="invoiceForm.processing" />
                    </form>

                    <!-- Email Templates -->
                    <form v-show="activeTab === 'email'" @submit.prevent="submitEmail" class="space-y-6">
                        <div class="bg-muted/30 rounded-xl p-4 text-xs text-muted-foreground mb-4">
                            Available variables: <code class="font-mono">{invoice_number}</code>, <code class="font-mono">{business_name}</code>, <code class="font-mono">{client_name}</code>, <code class="font-mono">{total}</code>
                        </div>

                        <div><InputLabel value="Subject Line" /><InputText v-model="emailForm.invoice_email_subject" fluid :invalid="!!emailForm.errors.invoice_email_subject" /><InputError :message="emailForm.errors.invoice_email_subject" /></div>
                        <div><InputLabel value="Greeting" /><InputText v-model="emailForm.invoice_email_greeting" fluid :invalid="!!emailForm.errors.invoice_email_greeting" /><InputError :message="emailForm.errors.invoice_email_greeting" /></div>
                        <div><InputLabel value="Body" /><Textarea v-model="emailForm.invoice_email_body" rows="3" fluid :invalid="!!emailForm.errors.invoice_email_body" /><InputError :message="emailForm.errors.invoice_email_body" /></div>
                        <div><InputLabel value="Footer" /><Textarea v-model="emailForm.invoice_email_footer" rows="2" fluid :invalid="!!emailForm.errors.invoice_email_footer" /><InputError :message="emailForm.errors.invoice_email_footer" /></div>

                        <div class="flex items-start gap-3">
                            <Checkbox v-model="emailForm.invoice_email_include_payment_instructions" :binary="true" />
                            <div>
                                <span class="text-sm font-medium text-foreground">Include payment instructions</span>
                                <p class="text-xs text-muted-foreground mt-0.5">Appends your payment instructions from Business Profile to invoice emails.</p>
                            </div>
                        </div>

                        <Button type="submit" label="Save email templates" :loading="emailForm.processing" />
                    </form>

                    <!-- Tax Profile -->
                    <form v-show="activeTab === 'tax'" @submit.prevent="submitTax" class="space-y-6">
                        <p class="text-muted-foreground text-sm mb-6">
                            Configure your tax identity. This drives all calculations, withholding logic, and TAJ form generation.
                        </p>

                        <!-- Mobile: show rates button -->
                        <div class="lg:hidden">
                            <Button label="View statutory rates" severity="secondary" outlined size="small" @click="showRatesDrawer = true" type="button" />
                        </div>

                        <div>
                            <InputLabel value="Tax Registration Number (TRN)" />
                            <InputText v-model="taxForm.trn" placeholder="123456789" maxlength="9" fluid :invalid="!!taxForm.errors.trn" />
                            <InputError :message="taxForm.errors.trn" />
                            <p class="mt-1.5 text-xs text-muted-foreground">9-digit number issued by TAJ</p>
                        </div>

                        <div>
                            <InputLabel value="Business Type" />
                            <Select v-model="taxForm.business_type" :options="businessTypes" optionLabel="label" optionValue="value" placeholder="Select your business type" fluid :invalid="!!taxForm.errors.business_type" />
                            <InputError :message="taxForm.errors.business_type" />
                            <p class="mt-1.5 text-xs text-muted-foreground">
                                Determines withholding tax rate: {{ formatRate('withholding_tax_rate') }}% for specified services, {{ formatRate('contractors_levy_rate') }}% for construction/haulage/tillage
                            </p>
                        </div>

                        <div class="pt-4">
                            <div class="flex items-start gap-3">
                                <Checkbox v-model="taxForm.is_gct_registered" :binary="true" />
                                <div>
                                    <span class="text-sm font-medium text-foreground">GCT Registered</span>
                                    <p class="text-xs text-muted-foreground mt-0.5">
                                        Required when annual turnover exceeds {{ formatCurrency('gct_registration_threshold') }}. Enables {{ formatRate('gct_rate') }}% GCT on invoices.
                                    </p>
                                </div>
                            </div>
                            <div v-if="taxForm.is_gct_registered" class="mt-4">
                                <InputLabel value="GCT Registration Date" />
                                <DatePicker v-model="taxForm.gct_registration_date" dateFormat="yy-mm-dd" showIcon fluid :invalid="!!taxForm.errors.gct_registration_date" />
                                <InputError :message="taxForm.errors.gct_registration_date" />
                            </div>
                        </div>

                        <div class="pt-4">
                            <InputLabel value="Fiscal Year Start" />
                            <DatePicker v-model="taxForm.fiscal_year_start" dateFormat="yy-mm-dd" showIcon fluid :invalid="!!taxForm.errors.fiscal_year_start" />
                            <InputError :message="taxForm.errors.fiscal_year_start" />
                            <p class="mt-1.5 text-xs text-muted-foreground">Defaults to January 1 if not set.</p>
                        </div>

                        <Button type="submit" label="Save tax profile" :loading="taxForm.processing" />
                    </form>
                </div>

                <!-- Statutory Rates Sticky Aside (desktop only) -->
                <div v-if="activeTab === 'tax'" class="hidden lg:block w-72 shrink-0">
                    <div class="sticky top-24 bg-muted/30 rounded-2xl p-5">
                        <h3 class="text-sm font-medium text-muted-foreground mb-4">Current Statutory Rates</h3>
                        <p class="text-xs text-muted-foreground mb-4">Set by the system administrator.</p>
                        <div class="space-y-2.5">
                            <div
                                v-for="(rate, key) in statutoryRates"
                                :key="key"
                                class="flex items-center justify-between"
                            >
                                <span class="text-xs text-muted-foreground">{{ rate.label }}</span>
                                <span class="text-xs tabular-nums font-medium text-foreground">
                                    {{ parseFloat(rate.value).toLocaleString('en-JM') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Rates Drawer -->
            <Drawer v-model:visible="showRatesDrawer" header="Current Statutory Rates" position="bottom" class="!h-auto">
                <p class="text-xs text-muted-foreground mb-4">Set by the system administrator and apply to all calculations.</p>
                <div class="space-y-3">
                    <div
                        v-for="(rate, key) in statutoryRates"
                        :key="key"
                        class="flex items-center justify-between py-1.5"
                    >
                        <span class="text-sm text-foreground">{{ rate.label }}</span>
                        <span class="text-sm tabular-nums font-medium text-foreground">
                            {{ parseFloat(rate.value).toLocaleString('en-JM') }}
                        </span>
                    </div>
                </div>
            </Drawer>
        </section>
    </AuthenticatedLayout>
</template>
