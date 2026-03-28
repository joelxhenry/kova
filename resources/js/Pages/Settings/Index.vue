<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';

const props = defineProps({
    business: { type: Object, required: true },
    invoicing: { type: Object, required: true },
    email: { type: Object, required: true },
    invoiceNumberPreview: { type: String, required: true },
});

const activeTab = ref('business');

const tabs = [
    { key: 'business', label: 'Business Profile' },
    { key: 'invoicing', label: 'Invoicing' },
    { key: 'email', label: 'Email Templates' },
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
});

const submitBusiness = () => {
    businessForm.put('/settings/business', { preserveScroll: true });
};

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
</script>

<template>
    <Head title="Settings" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20 max-w-3xl">
            <h1 class="text-xl md:text-2xl font-bold tracking-tight leading-tight mb-8">Settings</h1>

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

                <div><InputLabel value="Subject Line" /><InputText v-model="emailForm.invoice_email_subject" fluid /></div>
                <div><InputLabel value="Greeting" /><InputText v-model="emailForm.invoice_email_greeting" fluid /></div>
                <div><InputLabel value="Body" /><Textarea v-model="emailForm.invoice_email_body" rows="3" fluid /></div>
                <div><InputLabel value="Footer" /><Textarea v-model="emailForm.invoice_email_footer" rows="2" fluid /></div>

                <div class="flex items-start gap-3">
                    <Checkbox v-model="emailForm.invoice_email_include_payment_instructions" :binary="true" />
                    <div>
                        <span class="text-sm font-medium text-foreground">Include payment instructions</span>
                        <p class="text-xs text-muted-foreground mt-0.5">Appends your payment instructions from Business Profile to invoice emails.</p>
                    </div>
                </div>

                <Button type="submit" label="Save email templates" :loading="emailForm.processing" />
            </form>
        </section>
    </AuthenticatedLayout>
</template>
