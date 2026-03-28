<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

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
            <Link href="/login" class="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors duration-200">
                Login
            </Link>
        </template>

        <div>
            <div class="mb-10">
                <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none">
                    Create account
                </h1>
                <p class="mt-3 text-muted-foreground text-base">
                    Start managing your taxes and income.
                </p>
            </div>

            <div class="bg-card rounded-2xl shadow-sm p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel value="Name" />
                        <InputText v-model="form.name" autocomplete="name" autofocus fluid :invalid="!!form.errors.name" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel value="Email" />
                        <InputText v-model="form.email" type="email" autocomplete="username" fluid :invalid="!!form.errors.email" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel value="Password" />
                        <InputText v-model="form.password" type="password" autocomplete="new-password" fluid :invalid="!!form.errors.password" />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel value="Confirm Password" />
                        <InputText v-model="form.password_confirmation" type="password" autocomplete="new-password" fluid :invalid="!!form.errors.password_confirmation" />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <div class="pt-2">
                        <Button type="submit" label="Create account" :loading="form.processing" text />
                    </div>
                </form>
            </div>

            <p class="mt-8 text-sm text-muted-foreground pt-8">
                Already have an account?
                <Link href="/login" class="text-foreground hover:text-accent transition-colors duration-150">
                    Sign in
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>
