<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login" />

    <GuestLayout>
        <template #nav>
            <Link href="/register" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150 uppercase tracking-wider">
                Register
            </Link>
        </template>

        <div>
            <div class="mb-10">
                <div class="h-1 w-16 bg-accent mb-6" />
                <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">
                    Sign in
                </h1>
                <p class="mt-3 text-muted-foreground text-base">
                    Enter your credentials to access your account.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel value="Email" />
                    <InputText
                        v-model="form.email"
                        type="email"
                        autocomplete="username"
                        autofocus
                        fluid
                        :invalid="!!form.errors.email"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel value="Password" />
                    <InputText
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        fluid
                        :invalid="!!form.errors.password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <Checkbox v-model="form.remember" :binary="true" />
                        <span class="text-sm text-muted-foreground">Remember me</span>
                    </label>

                    <Link
                        href="/forgot-password"
                        class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150"
                    >
                        Forgot password?
                    </Link>
                </div>

                <div class="pt-2">
                    <Button type="submit" label="Sign in" :loading="form.processing" text />
                </div>
            </form>

            <p class="mt-8 text-sm text-muted-foreground border-t border-border pt-6">
                Don't have an account?
                <Link href="/register" class="text-foreground hover:text-accent transition-colors duration-150">
                    Create one
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>
