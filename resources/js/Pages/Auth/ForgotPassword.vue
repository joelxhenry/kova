<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <Head title="Forgot Password" />

    <GuestLayout>
        <template #nav>
            <Link href="/login" class="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors duration-200">
                Login
            </Link>
        </template>

        <div>
            <div class="mb-10">
                <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">
                    Reset password
                </h1>
                <p class="mt-3 text-muted-foreground text-base">
                    Enter your email and we'll send you a reset link.
                </p>
            </div>

            <div class="bg-card rounded-2xl shadow-sm p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel value="Email" />
                        <InputText v-model="form.email" type="email" autocomplete="username" autofocus fluid :invalid="!!form.errors.email" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="pt-2">
                        <Button type="submit" label="Send reset link" :loading="form.processing" text />
                    </div>
                </form>
            </div>

            <p class="mt-8 text-sm text-muted-foreground pt-8">
                Remember your password?
                <Link href="/login" class="text-foreground hover:text-accent transition-colors duration-150">
                    Sign in
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>
