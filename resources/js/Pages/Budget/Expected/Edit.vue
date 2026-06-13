<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';

const props = defineProps({
    /** @type {{id:number,account_id:number|null,transfer_account_id:number|null,type:string,transaction_category_id:number|null,amount:string,expected_date:string,description:string,notes:string|null}} */
    expected: { type: Object, required: true },
    /** @type {Array<{id:number,name:string,type:string}>} */
    accounts: { type: Array, default: () => [] },
    /** @type {Array<{id:number,name:string,kind:string}>} */
    categories: { type: Array, default: () => [] },
});

const accountOptions = props.accounts.map(a => ({ label: a.name, value: a.id }));
const debitOptions = props.accounts.filter(a => a.type === 'debit').map(a => ({ label: a.name, value: a.id }));
const creditOptions = props.accounts.filter(a => a.type === 'credit').map(a => ({ label: a.name, value: a.id }));

const typeOptions = [
    { label: 'Income', value: 'income' },
    { label: 'Expense', value: 'expense' },
    { label: 'Payment', value: 'transfer' },
];

const parseDate = (d) => d ? new Date(d) : new Date();

const form = useForm({
    account_id: props.expected.account_id,
    transfer_account_id: props.expected.transfer_account_id,
    type: props.expected.type,
    transaction_category_id: props.expected.transaction_category_id,
    amount: Number(props.expected.amount),
    expected_date: parseDate(props.expected.expected_date),
    description: props.expected.description,
    notes: props.expected.notes ?? '',
});

const isPayment = computed(() => form.type === 'transfer');

const categoryOptions = computed(() =>
    props.categories
        .filter(c => c.kind === form.type || c.kind === 'both')
        .map(c => ({ label: c.name, value: c.id })),
);

watch(() => form.type, () => {
    if (isPayment.value) {
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
        expected_date: formatDate(data.expected_date),
    })).put(`/budget/expected/${props.expected.id}`);
};
</script>

<template>
    <Head title="Edit Expected Item" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20 max-w-xl">
            <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">Edit Expected Item</h1>

            <form @submit.prevent="submit" class="mt-8 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel :value="isPayment ? 'Pay from (cash account)' : 'Account (optional)'" />
                        <Select v-model="form.account_id" :options="isPayment ? debitOptions : accountOptions" optionLabel="label" optionValue="value" :placeholder="isPayment ? 'Cash account' : 'Decide later'" :showClear="!isPayment" fluid :invalid="!!form.errors.account_id" />
                        <InputError :message="form.errors.account_id" />
                    </div>
                    <div>
                        <InputLabel value="Type" />
                        <Select v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" fluid :invalid="!!form.errors.type" />
                        <InputError :message="form.errors.type" />
                    </div>
                </div>

                <div v-if="isPayment">
                    <InputLabel value="Pay to (credit account)" />
                    <Select v-model="form.transfer_account_id" :options="creditOptions" optionLabel="label" optionValue="value" placeholder="Credit account" fluid :invalid="!!form.errors.transfer_account_id" />
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
                        <InputLabel value="Expected date" />
                        <DatePicker v-model="form.expected_date" dateFormat="yy-mm-dd" showIcon fluid :invalid="!!form.errors.expected_date" />
                        <InputError :message="form.errors.expected_date" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Notes" />
                    <Textarea v-model="form.notes" rows="2" fluid />
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <Button type="submit" label="Update expected item" :loading="form.processing" />
                    <Link href="/budget/expected" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">Cancel</Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
