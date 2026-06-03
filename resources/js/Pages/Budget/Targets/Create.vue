<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Button from 'primevue/button';

const props = defineProps({
    /** @type {Array<{id:number,name:string,kind:string}>} */
    categories: { type: Array, default: () => [] },
});

const typeOptions = [
    { label: 'Income', value: 'income' },
    { label: 'Expense', value: 'expense' },
];

const periodOptions = [
    { label: 'Monthly', value: 'monthly' },
];

const form = useForm({
    transaction_category_id: null,
    type: 'expense',
    period: 'monthly',
    amount: null,
});

// Categories are filtered to the selected type's kind (a `both` category fits either).
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

const submit = () => {
    form.post('/budget/targets');
};
</script>

<template>
    <Head title="Set Budget Target" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20 max-w-xl">
            <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">Set Budget Target</h1>
            <p class="mt-2 text-sm text-muted-foreground">Plan a monthly amount for a category. Your actual spend is compared against it live.</p>

            <form @submit.prevent="submit" class="mt-8 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Type" />
                        <Select v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" fluid :invalid="!!form.errors.type" />
                        <InputError :message="form.errors.type" />
                    </div>
                    <div>
                        <InputLabel value="Period" />
                        <Select v-model="form.period" :options="periodOptions" optionLabel="label" optionValue="value" fluid :invalid="!!form.errors.period" />
                        <InputError :message="form.errors.period" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Category" />
                    <Select v-model="form.transaction_category_id" :options="categoryOptions" optionLabel="label" optionValue="value" placeholder="Select a category" fluid :invalid="!!form.errors.transaction_category_id" />
                    <InputError :message="form.errors.transaction_category_id" />
                </div>

                <div>
                    <InputLabel value="Planned amount" />
                    <InputNumber v-model="form.amount" :min="0.01" :minFractionDigits="2" :maxFractionDigits="2" fluid :invalid="!!form.errors.amount" />
                    <InputError :message="form.errors.amount" />
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <Button type="submit" label="Save target" :loading="form.processing" />
                    <Link href="/budget/targets" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">Cancel</Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
