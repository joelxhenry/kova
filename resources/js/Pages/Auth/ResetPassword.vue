<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Password" />

    <GuestLayout>
        <div>
            <div class="mb-10">
                <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">
                    New password
                </h1>
                <p class="mt-3 text-muted-foreground text-base">
                    Choose a new password for your account.
                </p>
            </div>

            <div class="bg-card rounded-2xl shadow-sm p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel value="Email" />
                        <InputText v-model="form.email" type="email" autocomplete="username" fluid :invalid="!!form.errors.email" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel value="Password" />
                        <InputText v-model="form.password" type="password" autocomplete="new-password" autofocus fluid :invalid="!!form.errors.password" />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel value="Confirm Password" />
                        <InputText v-model="form.password_confirmation" type="password" autocomplete="new-password" fluid :invalid="!!form.errors.password_confirmation" />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <div class="pt-2">
                        <Button type="submit" label="Reset password" :loading="form.processing" text />
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
