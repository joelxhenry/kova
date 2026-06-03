<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';
import { useRecurrence } from '@/Composables/useRecurrence.js';

const props = defineProps({
    /** @type {{id:number,account_id:number,transfer_account_id:number|null,transaction_category_id:number|null,type:string,amount:string,frequency:string,start_date:string,end_date:string|null,description:string}} */
    recurring: { type: Object, required: true },
    /** @type {Array<{id:number,name:string,type:string}>} */
    accounts: { type: Array, default: () => [] },
    /** @type {Array<{id:number,name:string,kind:string}>} */
    categories: { type: Array, default: () => [] },
});

const { frequencyOptions } = useRecurrence();

const accountOptions = props.accounts.map(a => ({ label: a.name, value: a.id }));

const typeOptions = [
    { label: 'Income', value: 'income' },
    { label: 'Expense', value: 'expense' },
    { label: 'Transfer', value: 'transfer' },
];

const parseDate = (d) => d ? new Date(d) : null;

const form = useForm({
    account_id: props.recurring.account_id,
    transfer_account_id: props.recurring.transfer_account_id,
    transaction_category_id: props.recurring.transaction_category_id,
    type: props.recurring.type,
    amount: Number(props.recurring.amount),
    frequency: props.recurring.frequency,
    start_date: parseDate(props.recurring.start_date) ?? new Date(),
    end_date: parseDate(props.recurring.end_date),
    description: props.recurring.description,
});

const categoryOptions = computed(() =>
    props.categories
        .filter(c => c.kind === form.type || c.kind === 'both')
        .map(c => ({ label: c.name, value: c.id })),
);

watch(() => form.type, () => {
    if (form.type === 'transfer') {
        form.transaction_category_id = null;
    } else {
        form.transfer_account_id = null;
        if (!categoryOptions.value.some(o => o.value === form.transaction_category_id)) {
            form.transaction_category_id = null;
        }
    }
});

const formatDate = (d) => d ? d.toISOString().split('T')[0] : null;

const submit = () => {
    form.transform((data) => ({
        ...data,
        start_date: formatDate(data.start_date),
        end_date: formatDate(data.end_date),
    })).put(`/budget/recurring/${props.recurring.id}`);
};
</script>

<template>
    <Head title="Edit Recurring Rule" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20 max-w-xl">
            <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">Edit Recurring Rule</h1>

            <form @submit.prevent="submit" class="mt-8 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Account" />
                        <Select v-model="form.account_id" :options="accountOptions" optionLabel="label" optionValue="value" fluid :invalid="!!form.errors.account_id" />
                        <InputError :message="form.errors.account_id" />
                    </div>
                    <div>
                        <InputLabel value="Type" />
                        <Select v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" fluid :invalid="!!form.errors.type" />
                        <InputError :message="form.errors.type" />
                    </div>
                </div>

                <div v-if="form.type === 'transfer'">
                    <InputLabel value="Transfer to" />
                    <Select v-model="form.transfer_account_id" :options="accountOptions" optionLabel="label" optionValue="value" placeholder="Destination account" fluid :invalid="!!form.errors.transfer_account_id" />
                    <InputError :message="form.errors.transfer_account_id" />
                </div>

                <div v-else>
                    <InputLabel value="Category" />
                    <Select v-model="form.transaction_category_id" :options="categoryOptions" optionLabel="label" optionValue="value" placeholder="Select a category" showClear fluid :invalid="!!form.errors.transaction_category_id" />
                    <InputError :message="form.errors.transaction_category_id" />
                </div>

                <div>
                    <InputLabel value="Description" />
                    <InputText v-model="form.description" fluid :invalid="!!form.errors.description" />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Amount" />
                        <InputNumber v-model="form.amount" :min="0.01" :minFractionDigits="2" :maxFractionDigits="2" fluid :invalid="!!form.errors.amount" />
                        <InputError :message="form.errors.amount" />
                    </div>
                    <div>
                        <InputLabel value="Frequency" />
                        <Select v-model="form.frequency" :options="frequencyOptions" optionLabel="label" optionValue="value" fluid :invalid="!!form.errors.frequency" />
                        <InputError :message="form.errors.frequency" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Start date" />
                        <DatePicker v-model="form.start_date" dateFormat="yy-mm-dd" showIcon fluid :invalid="!!form.errors.start_date" />
                        <InputError :message="form.errors.start_date" />
                    </div>
                    <div>
                        <InputLabel value="End date (optional)" />
                        <DatePicker v-model="form.end_date" dateFormat="yy-mm-dd" showIcon showButtonBar fluid :invalid="!!form.errors.end_date" />
                        <InputError :message="form.errors.end_date" />
                    </div>
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <Button type="submit" label="Update recurring rule" :loading="form.processing" />
                    <Link href="/budget/recurring" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">Cancel</Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
