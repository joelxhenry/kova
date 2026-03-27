<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    clients: { type: Array, default: () => [] },
});

const { formatJMD } = useCurrencyFormatter();

const clientOptions = props.clients.map(c => ({ label: c.name, value: c.id }));

const form = useForm({
    client_id: null,
    issue_date: new Date(),
    due_date: null,
    notes: '',
    items: [{ description: '', quantity: 1, unit_price: null }],
});

const addItem = () => {
    form.items.push({ description: '', quantity: 1, unit_price: null });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const subtotal = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);
    }, 0);
});

const formatDate = (d) => d ? d.toISOString().split('T')[0] : null;

const submit = () => {
    form.transform((data) => ({
        ...data,
        issue_date: formatDate(data.issue_date),
        due_date: formatDate(data.due_date),
    })).post('/invoices');
};
</script>

<template>
    <Head title="New Invoice" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20 max-w-3xl">
            <div class="h-1 w-16 bg-accent mb-6" />
            <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">New Invoice</h1>

            <form @submit.prevent="submit" class="mt-10 space-y-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <InputLabel value="Client" />
                        <Select v-model="form.client_id" :options="clientOptions" optionLabel="label" optionValue="value" placeholder="Select a client" fluid :invalid="!!form.errors.client_id" />
                        <InputError :message="form.errors.client_id" />
                    </div>
                    <div>
                        <InputLabel value="Issue Date" />
                        <DatePicker v-model="form.issue_date" dateFormat="yy-mm-dd" showIcon fluid :invalid="!!form.errors.issue_date" />
                        <InputError :message="form.errors.issue_date" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Due Date" />
                    <DatePicker v-model="form.due_date" dateFormat="yy-mm-dd" showIcon fluid :invalid="!!form.errors.due_date" />
                    <InputError :message="form.errors.due_date" />
                </div>

                <!-- Line Items -->
                <div class="border-t border-border pt-8">
                    <h2 class="text-lg font-semibold tracking-tight mb-4">Line Items</h2>
                    <InputError :message="form.errors.items" />

                    <div class="space-y-4">
                        <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-12 gap-3 items-end">
                            <div class="col-span-6">
                                <InputLabel v-if="index === 0" value="Description" />
                                <InputText v-model="item.description" fluid :invalid="!!form.errors[`items.${index}.description`]" />
                                <InputError :message="form.errors[`items.${index}.description`]" />
                            </div>
                            <div class="col-span-2">
                                <InputLabel v-if="index === 0" value="Qty" />
                                <InputNumber v-model="item.quantity" :min="0.01" :minFractionDigits="0" :maxFractionDigits="2" fluid :invalid="!!form.errors[`items.${index}.quantity`]" />
                            </div>
                            <div class="col-span-3">
                                <InputLabel v-if="index === 0" value="Unit Price" />
                                <InputNumber v-model="item.unit_price" :min="0" :minFractionDigits="2" :maxFractionDigits="2" fluid :invalid="!!form.errors[`items.${index}.unit_price`]" />
                            </div>
                            <div class="col-span-1 pb-1">
                                <Button v-if="form.items.length > 1" icon="pi pi-times" text severity="danger" size="small" @click="removeItem(index)" />
                            </div>
                        </div>
                    </div>

                    <Button type="button" label="+ Add line item" text size="small" class="mt-4" @click="addItem" />

                    <div class="mt-6 text-right text-lg font-mono font-medium">
                        Subtotal: {{ formatJMD(subtotal) }}
                    </div>
                </div>

                <div>
                    <InputLabel value="Notes" />
                    <Textarea v-model="form.notes" rows="3" fluid :invalid="!!form.errors.notes" />
                    <InputError :message="form.errors.notes" />
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <Button type="submit" label="Create invoice" :loading="form.processing" text />
                    <Link href="/invoices" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">Cancel</Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
