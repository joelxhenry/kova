<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
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
    expense: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
});

const categoryOptions = props.categories.map(c => ({ label: c.name, value: c.id }));
const parseDate = (d) => d ? new Date(d) : new Date();

const form = useForm({
    expense_category_id: props.expense.expense_category_id,
    description: props.expense.description,
    amount: Number(props.expense.amount),
    date_incurred: parseDate(props.expense.date_incurred),
    notes: props.expense.notes ?? '',
});

const formatDate = (d) => d ? d.toISOString().split('T')[0] : null;

const submit = () => {
    form.transform((data) => ({
        ...data,
        date_incurred: formatDate(data.date_incurred),
    })).put(`/expenses/${props.expense.id}`);
};
</script>

<template>
    <Head title="Edit Expense" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20 max-w-xl">
            <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">Edit Expense</h1>

            <form @submit.prevent="submit" class="mt-8 space-y-6">
                <div>
                    <InputLabel value="Category" />
                    <Select v-model="form.expense_category_id" :options="categoryOptions" optionLabel="label" optionValue="value" fluid :invalid="!!form.errors.expense_category_id" />
                    <InputError :message="form.errors.expense_category_id" />
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
                        <DatePicker v-model="form.date_incurred" dateFormat="yy-mm-dd" showIcon fluid :invalid="!!form.errors.date_incurred" />
                        <InputError :message="form.errors.date_incurred" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Notes" />
                    <Textarea v-model="form.notes" rows="2" fluid />
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <Button type="submit" label="Update expense" :loading="form.processing" />
                    <Link href="/expenses" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">Cancel</Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
