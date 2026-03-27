<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    trn: '',
    is_designated_entity: false,
});

const submit = () => {
    form.post('/clients');
};
</script>

<template>
    <Head title="Add Client" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20 max-w-2xl">
            <div class="h-1 w-16 bg-accent mb-6" />
            <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">Add Client</h1>

            <form @submit.prevent="submit" class="mt-10 space-y-6">
                <div>
                    <InputLabel value="Client Name" />
                    <InputText v-model="form.name" autofocus fluid :invalid="!!form.errors.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <InputLabel value="Email" />
                        <InputText v-model="form.email" type="email" fluid :invalid="!!form.errors.email" />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div>
                        <InputLabel value="Phone" />
                        <InputText v-model="form.phone" fluid :invalid="!!form.errors.phone" />
                        <InputError :message="form.errors.phone" />
                    </div>
                </div>

                <div>
                    <InputLabel value="TRN" />
                    <InputText v-model="form.trn" placeholder="123456789" maxlength="9" fluid :invalid="!!form.errors.trn" />
                    <InputError :message="form.errors.trn" />
                </div>

                <div class="flex items-start gap-3">
                    <Checkbox v-model="form.is_designated_entity" :binary="true" />
                    <div>
                        <span class="text-sm font-medium text-foreground">Designated Entity</span>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            Government bodies and large entities that withhold tax at source on invoices over JMD $50,000.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <Button type="submit" label="Save client" :loading="form.processing" text />
                    <Link href="/clients" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">Cancel</Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
