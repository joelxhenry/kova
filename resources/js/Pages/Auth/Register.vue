<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import PrimaryButton from '@/Components/UI/PrimaryButton.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />

    <GuestLayout>
        <template #nav>
            <Link href="/login" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150 uppercase tracking-wider">
                Login
            </Link>
        </template>

        <div>
            <div class="mb-10">
                <div class="h-1 w-16 bg-accent mb-6" />
                <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">
                    Create account
                </h1>
                <p class="mt-3 text-muted-foreground text-base">
                    Start managing your taxes and income.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel value="Name" />
                    <TextInput
                        v-model="form.name"
                        type="text"
                        :error="form.errors.name"
                        autocomplete="name"
                        autofocus
                    />
                    <InputError :message="form.errors.name" />
                </div>

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
                        Create account
                    </PrimaryButton>
                </div>
            </form>

            <p class="mt-8 text-sm text-muted-foreground border-t border-border pt-6">
                Already have an account?
                <Link href="/login" class="text-foreground hover:text-accent transition-colors duration-150">
                    Sign in
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>
