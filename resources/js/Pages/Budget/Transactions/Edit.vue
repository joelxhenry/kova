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
    /** @type {{id:number,account_id:number,type:string,transaction_category_id:number|null,amount:string,date:string,description:string,notes:string|null}} */
    transaction: { type: Object, required: true },
    /** @type {Array<{id:number,name:string,type:string}>} */
    accounts: { type: Array, default: () => [] },
    /** @type {Array<{id:number,name:string,kind:string}>} */
    categories: { type: Array, default: () => [] },
});

const accountOptions = props.accounts.map(a => ({ label: a.name, value: a.id }));

const typeOptions = [
    { label: 'Income', value: 'income' },
    { label: 'Expense', value: 'expense' },
];

const parseDate = (d) => d ? new Date(d) : new Date();

const form = useForm({
    account_id: props.transaction.account_id,
    type: props.transaction.type,
    transaction_category_id: props.transaction.transaction_category_id,
    amount: Number(props.transaction.amount),
    date: parseDate(props.transaction.date),
    description: props.transaction.description,
    notes: props.transaction.notes ?? '',
});

const categoryOptions = computed(() =>
    props.categories
        .filter(c => c.kind === form.type || c.kind === 'both')
        .map(c => ({ label: c.name, value: c.id })),
);

watch(() => form.type, () => {
    if (!categoryOptions.value.some(o => o.value === form.transaction_category_id)) {
        form.transaction_category_id = null;
    }
});

const formatDate = (d) => d ? d.toISOString().split('T')[0] : null;

const submit = () => {
    form.transform((data) => ({
        ...data,
        date: formatDate(data.date),
    })).put(`/budget/transactions/${props.transaction.id}`);
};
</script>

<template>
    <Head title="Edit Transaction" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20 max-w-xl">
            <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">Edit Transaction</h1>

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

                <div>
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
                        <InputLabel value="Date" />
                        <DatePicker v-model="form.date" dateFormat="yy-mm-dd" showIcon fluid :invalid="!!form.errors.date" />
                        <InputError :message="form.errors.date" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Notes" />
                    <Textarea v-model="form.notes" rows="2" fluid />
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <Button type="submit" label="Update transaction" :loading="form.processing" />
                    <Link href="/budget/transactions" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">Cancel</Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
