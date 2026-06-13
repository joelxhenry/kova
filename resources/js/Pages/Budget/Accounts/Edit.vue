<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';

const props = defineProps({
    /** @type {{id:number,name:string,type:string,opening_balance:string,current_balance:string,interest_rate:string|null,credit_limit:string|null,is_active:boolean}} */
    account: { type: Object, required: true },
});

const typeOptions = [
    { label: 'Debit (asset — cash, bank, savings)', value: 'debit' },
    { label: 'Credit (liability — credit card, loan)', value: 'credit' },
];

const form = useForm({
    name: props.account.name,
    type: props.account.type,
    opening_balance: Number(props.account.opening_balance),
    interest_rate: props.account.interest_rate !== null ? Number(props.account.interest_rate) : null,
    credit_limit: props.account.credit_limit !== null ? Number(props.account.credit_limit) : null,
    is_active: props.account.is_active,
});

const submit = () => {
    form.put(`/budget/accounts/${props.account.id}`);
};
</script>

<template>
    <Head title="Edit Account" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20 max-w-xl">
            <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">Edit Account</h1>

            <form @submit.prevent="submit" class="mt-8 space-y-6">
                <div>
                    <InputLabel value="Name" />
                    <InputText v-model="form.name" fluid :invalid="!!form.errors.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel value="Type" />
                    <Select v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" fluid :invalid="!!form.errors.type" />
                    <InputError :message="form.errors.type" />
                </div>

                <div>
                    <InputLabel value="Opening balance" />
                    <InputNumber v-model="form.opening_balance" :minFractionDigits="2" :maxFractionDigits="2" fluid :invalid="!!form.errors.opening_balance" />
                    <InputError :message="form.errors.opening_balance" />
                    <p class="mt-1 text-xs text-muted-foreground">Changing this re-derives the current balance, keeping recorded transactions intact.</p>
                </div>

                <div>
                    <InputLabel :value="form.type === 'credit' ? 'Interest rate (APR %)' : 'Interest rate (APR %, optional)'" />
                    <InputNumber v-model="form.interest_rate" suffix=" %" :min="0" :max="100" :minFractionDigits="2" :maxFractionDigits="3" placeholder="e.g. 19.99" fluid :invalid="!!form.errors.interest_rate" />
                    <InputError :message="form.errors.interest_rate" />
                </div>

                <div v-if="form.type === 'credit'">
                    <InputLabel value="Credit limit" />
                    <InputNumber v-model="form.credit_limit" :min="0" :minFractionDigits="2" :maxFractionDigits="2" placeholder="Optional spending limit" fluid :invalid="!!form.errors.credit_limit" />
                    <InputError :message="form.errors.credit_limit" />
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox v-model="form.is_active" :binary="true" inputId="is_active" />
                    <InputLabel value="Active" for="is_active" class="!mb-0" />
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <Button type="submit" label="Update account" :loading="form.processing" />
                    <Link href="/budget/accounts" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">Cancel</Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
