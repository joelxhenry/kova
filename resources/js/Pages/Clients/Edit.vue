<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';

const props = defineProps({
    client: { type: Object, required: true },
});

const form = useForm({
    name: props.client.name,
    email: props.client.email ?? '',
    phone: props.client.phone ?? '',
    trn: props.client.trn ?? '',
    is_designated_entity: props.client.is_designated_entity,
});

const submit = () => {
    form.put(`/clients/${props.client.id}`);
};
</script>

<template>
    <Head title="Edit Client" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20 max-w-2xl">
            <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">Edit Client</h1>

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
                            Government bodies and large entities that withhold tax at source.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <Button type="submit" label="Update client" :loading="form.processing" text />
                    <Link href="/clients" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">Cancel</Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
