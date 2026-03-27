<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import PrimaryButton from '@/Components/UI/PrimaryButton.vue';

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
                <div class="h-1 w-16 bg-accent mb-6" />
                <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">
                    New password
                </h1>
                <p class="mt-3 text-muted-foreground text-base">
                    Choose a new password for your account.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel value="Email" />
                    <TextInput
                        v-model="form.email"
                        type="email"
                        :error="form.errors.email"
                        autocomplete="username"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel value="Password" />
                    <TextInput
                        v-model="form.password"
                        type="password"
                        :error="form.errors.password"
                        autocomplete="new-password"
                        autofocus
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel value="Confirm Password" />
                    <TextInput
                        v-model="form.password_confirmation"
                        type="password"
                        :error="form.errors.password_confirmation"
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <div class="pt-2">
                    <PrimaryButton :disabled="form.processing">
                        Reset password
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
