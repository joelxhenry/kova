<script setup>
import { useForm, Link, Head, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

const page = usePage();

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
            <Link href="/login" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150 uppercase tracking-wider">
                Login
            </Link>
        </template>

        <div>
            <div class="mb-10">
                <div class="h-1 w-16 bg-accent mb-6" />
                <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">
                    Reset password
                </h1>
                <p class="mt-3 text-muted-foreground text-base">
                    Enter your email and we'll send you a reset link.
                </p>
            </div>

            <div
                v-if="page.props.flash.status"
                class="mb-6 border border-accent/30 bg-accent/5 px-4 py-3 text-sm text-foreground"
            >
                {{ page.props.flash.status }}
            </div>

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

            <p class="mt-8 text-sm text-muted-foreground border-t border-border pt-6">
                Remember your password?
                <Link href="/login" class="text-foreground hover:text-accent transition-colors duration-150">
                    Sign in
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>
