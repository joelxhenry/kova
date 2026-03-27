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
    address_line_1: '',
    address_line_2: '',
    city: '',
    state_or_parish: '',
    postal_code: '',
    country: 'Jamaica',
    contacts: [],
});

const addContact = () => {
    form.contacts.push({ first_name: '', last_name: '', email: '', phone: '' });
};

const removeContact = (index) => {
    form.contacts.splice(index, 1);
};

const submit = () => {
    form.post('/clients');
};
</script>

<template>
    <Head title="Add Client" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20 max-w-2xl">
            <h1 class="text-3xl md:text-4xl font-bold tracking-tight leading-tight">Add Client</h1>

            <form @submit.prevent="submit" class="mt-10 space-y-6">
                <div>
                    <InputLabel value="Client Name" />
                    <InputText v-model="form.name" autofocus fluid :invalid="!!form.errors.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                        <p class="text-xs text-muted-foreground mt-0.5">Government bodies and large entities that withhold tax at source.</p>
                    </div>
                </div>

                <!-- Address -->
                <div class="pt-4">
                    <h2 class="text-sm font-medium text-muted-foreground mb-3">Address</h2>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div><InputLabel value="Address Line 1" /><InputText v-model="form.address_line_1" fluid /></div>
                            <div><InputLabel value="Address Line 2" /><InputText v-model="form.address_line_2" fluid /></div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div><InputLabel value="City" /><InputText v-model="form.city" fluid /></div>
                            <div><InputLabel value="State / Parish" /><InputText v-model="form.state_or_parish" fluid /></div>
                            <div><InputLabel value="Postal Code" /><InputText v-model="form.postal_code" fluid /></div>
                            <div><InputLabel value="Country" /><InputText v-model="form.country" fluid /></div>
                        </div>
                    </div>
                </div>

                <!-- Contacts -->
                <div class="pt-4">
                    <h2 class="text-sm font-medium text-muted-foreground mb-3">Contacts</h2>

                    <div v-if="form.contacts.length === 0" class="text-sm text-muted-foreground mb-3">
                        No contacts added yet.
                    </div>

                    <div class="space-y-4">
                        <div v-for="(contact, index) in form.contacts" :key="index" class="bg-muted/20 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-medium text-muted-foreground">Contact {{ index + 1 }}</span>
                                <Button icon="pi pi-times" text severity="danger" size="small" @click="removeContact(index)" type="button" />
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <InputLabel value="First Name" />
                                    <InputText v-model="contact.first_name" fluid :invalid="!!form.errors[`contacts.${index}.first_name`]" />
                                    <InputError :message="form.errors[`contacts.${index}.first_name`]" />
                                </div>
                                <div>
                                    <InputLabel value="Last Name" />
                                    <InputText v-model="contact.last_name" fluid :invalid="!!form.errors[`contacts.${index}.last_name`]" />
                                    <InputError :message="form.errors[`contacts.${index}.last_name`]" />
                                </div>
                                <div>
                                    <InputLabel value="Email" />
                                    <InputText v-model="contact.email" type="email" fluid :invalid="!!form.errors[`contacts.${index}.email`]" />
                                    <InputError :message="form.errors[`contacts.${index}.email`]" />
                                </div>
                                <div>
                                    <InputLabel value="Phone" />
                                    <InputText v-model="contact.phone" fluid :invalid="!!form.errors[`contacts.${index}.phone`]" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <Button type="button" label="+ Add contact" text size="small" class="mt-3" @click="addContact" />
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <Button type="submit" label="Save client" :loading="form.processing" />
                    <Link href="/clients" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-200">Cancel</Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
