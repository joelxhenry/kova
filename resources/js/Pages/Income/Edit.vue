<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import PrimaryButton from '@/Components/UI/PrimaryButton.vue';

const props = defineProps({
    entry: { type: Object, required: true },
});

const form = useForm({
    source: props.entry.source,
    description: props.entry.description ?? '',
    amount: props.entry.amount,
    date_received: props.entry.date_received?.split('T')[0] ?? '',
    withholding_tax_applied: props.entry.withholding_tax_applied ?? '',
});

const submit = () => {
    form.put(`/income/${props.entry.id}`);
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
                    <TextInput v-model="form.source" :error="form.errors.source" autofocus />
                    <InputError :message="form.errors.source" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <InputLabel value="Amount (JMD)" />
                        <TextInput v-model="form.amount" type="number" step="0.01" min="0.01" :error="form.errors.amount" />
                        <InputError :message="form.errors.amount" />
                    </div>
                    <div>
                        <InputLabel value="Date Received" />
                        <TextInput v-model="form.date_received" type="date" :error="form.errors.date_received" />
                        <InputError :message="form.errors.date_received" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Withholding Tax Applied (JMD)" />
                    <TextInput v-model="form.withholding_tax_applied" type="number" step="0.01" min="0" :error="form.errors.withholding_tax_applied" />
                    <InputError :message="form.errors.withholding_tax_applied" />
                </div>

                <div>
                    <InputLabel value="Description" />
                    <textarea v-model="form.description" rows="3" class="w-full bg-input border border-border px-4 py-3 text-base text-foreground placeholder:text-muted-foreground outline-none transition-colors duration-150 focus:border-accent resize-none"></textarea>
                    <InputError :message="form.errors.description" />
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <PrimaryButton :disabled="form.processing">Update entry</PrimaryButton>
                    <Link href="/income" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">Cancel</Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
