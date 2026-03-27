<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';

const props = defineProps({
    entry: { type: Object, required: true },
});

const parseDate = (d) => d ? new Date(d) : null;

const form = useForm({
    source: props.entry.source,
    description: props.entry.description ?? '',
    amount: Number(props.entry.amount),
    date_received: parseDate(props.entry.date_received),
    withholding_tax_applied: Number(props.entry.withholding_tax_applied) || null,
});

const formatDate = (d) => d ? d.toISOString().split('T')[0] : null;

const submit = () => {
    form.transform((data) => ({
        ...data,
        date_received: formatDate(data.date_received),
    })).put(`/income/${props.entry.id}`);
};
</script>

<template>
    <Head title="Edit Income" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20 max-w-2xl">
            <div class="h-1 w-16 bg-accent mb-6" />
            <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">Edit Income</h1>

            <form @submit.prevent="submit" class="mt-10 space-y-6">
                <div>
                    <InputLabel value="Source" />
                    <InputText v-model="form.source" autofocus fluid :invalid="!!form.errors.source" />
                    <InputError :message="form.errors.source" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <InputLabel value="Amount (JMD)" />
                        <InputNumber v-model="form.amount" :min="0.01" :minFractionDigits="2" :maxFractionDigits="2" fluid :invalid="!!form.errors.amount" />
                        <InputError :message="form.errors.amount" />
                    </div>
                    <div>
                        <InputLabel value="Date Received" />
                        <DatePicker v-model="form.date_received" dateFormat="yy-mm-dd" showIcon fluid :invalid="!!form.errors.date_received" />
                        <InputError :message="form.errors.date_received" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Withholding Tax Applied (JMD)" />
                    <InputNumber v-model="form.withholding_tax_applied" :min="0" :minFractionDigits="2" :maxFractionDigits="2" fluid :invalid="!!form.errors.withholding_tax_applied" />
                    <InputError :message="form.errors.withholding_tax_applied" />
                </div>

                <div>
                    <InputLabel value="Description" />
                    <Textarea v-model="form.description" rows="3" fluid :invalid="!!form.errors.description" />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <Button type="submit" label="Update entry" :loading="form.processing" text />
                    <Link href="/income" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">Cancel</Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
