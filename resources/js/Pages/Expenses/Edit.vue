<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';

const props = defineProps({
    expense: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
});

const categoryOptions = props.categories.map(c => ({ label: c.name, value: c.id }));

const parseDate = (d) => d ? new Date(d) : null;

const form = useForm({
    expense_category_id: props.expense.expense_category_id,
    description: props.expense.description,
    amount: Number(props.expense.amount),
    date_incurred: parseDate(props.expense.date_incurred),
    receipt: null,
    notes: props.expense.notes ?? '',
});

const formatDate = (d) => d ? d.toISOString().split('T')[0] : null;

const onFileChange = (event) => {
    form.receipt = event.target.files[0] ?? null;
};

const submit = () => {
    const data = form.transform((d) => ({
        ...d,
        _method: 'PUT',
        date_incurred: formatDate(d.date_incurred),
    }));
    data.post(`/expenses/${props.expense.id}`, {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Edit Expense" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20 max-w-2xl">
            <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">Edit Expense</h1>

            <form @submit.prevent="submit" class="mt-10 space-y-6">
                <div>
                    <InputLabel value="Category" />
                    <Select v-model="form.expense_category_id" :options="categoryOptions" optionLabel="label" optionValue="value" fluid :invalid="!!form.errors.expense_category_id" />
                    <InputError :message="form.errors.expense_category_id" />
                </div>

                <div>
                    <InputLabel value="Description" />
                    <InputText v-model="form.description" autofocus fluid :invalid="!!form.errors.description" />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <InputLabel value="Amount (JMD)" />
                        <InputNumber v-model="form.amount" :min="0.01" :minFractionDigits="2" :maxFractionDigits="2" fluid :invalid="!!form.errors.amount" />
                        <InputError :message="form.errors.amount" />
                    </div>
                    <div>
                        <InputLabel value="Date Incurred" />
                        <DatePicker v-model="form.date_incurred" dateFormat="yy-mm-dd" showIcon fluid :invalid="!!form.errors.date_incurred" />
                        <InputError :message="form.errors.date_incurred" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Receipt" />
                    <div v-if="expense.receipt_path" class="mb-2 text-xs text-muted-foreground">
                        <span class="text-accent tabular-nums text-xs font-medium">Current receipt attached</span>
                        <span class="ml-1">— upload a new file to replace it.</span>
                    </div>
                    <input
                        type="file"
                        accept=".jpg,.jpeg,.png,.pdf"
                        @change="onFileChange"
                        class="block w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:border file:border-border file:text-sm file:font-semibold file:bg-input file:text-foreground file:rounded-lg hover:file:bg-muted file:transition-colors file:duration-150 file:cursor-pointer"
                    />
                    <InputError :message="form.errors.receipt" />
                    <p class="mt-1.5 text-xs text-muted-foreground">JPG, PNG, or PDF. Max 5MB.</p>
                </div>

                <div>
                    <InputLabel value="Notes" />
                    <Textarea v-model="form.notes" rows="3" fluid :invalid="!!form.errors.notes" />
                    <InputError :message="form.errors.notes" />
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <Button type="submit" label="Update expense" :loading="form.processing" text />
                    <Link href="/expenses" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">Cancel</Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
