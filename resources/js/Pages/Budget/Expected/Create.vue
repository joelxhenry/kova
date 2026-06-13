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

const form = useForm({
    account_id: null,
    transfer_account_id: null,
    type: 'income',
    transaction_category_id: null,
    amount: null,
    expected_date: new Date(),
    description: '',
    notes: '',
});

const isPayment = computed(() => form.type === 'transfer');

// Categories are filtered to the selected type's kind (a `both` category fits either).
const categoryOptions = computed(() =>
    props.categories
        .filter(c => c.kind === form.type || c.kind === 'both')
        .map(c => ({ label: c.name, value: c.id })),
);

// Switching to/from a payment clears the fields that no longer apply.
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
    })).post('/budget/expected');
};
</script>

<template>
    <Head title="Add Expected Item" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20 max-w-xl">
            <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">Add Expected Item</h1>
            <p class="mt-2 text-sm text-muted-foreground">Anticipate a one-off cash flow. It feeds your projection while pending and never touches a balance until realized.</p>

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
                    <Button type="submit" label="Save expected item" :loading="form.processing" />
                    <Link href="/budget/expected" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">Cancel</Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
