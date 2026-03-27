<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20">
            <div class="h-1 w-16 bg-accent mb-6" />
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold tracking-tighter leading-none">
                Welcome back,<br>
                <span class="text-accent">{{ user.name }}.</span>
            </h1>
            <p class="mt-4 max-w-2xl text-muted-foreground text-base md:text-lg leading-normal">
                Your financial dashboard will appear here as you add income and expenses.
            </p>
        </section>
    </AuthenticatedLayout>
</template>
