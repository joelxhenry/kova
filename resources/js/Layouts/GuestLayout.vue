<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-background text-foreground flex flex-col">
        <nav class="bg-card shadow-sm">
            <div class="mx-auto max-w-5xl px-6 md:px-12 lg:px-16">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/login" class="text-xl font-bold text-foreground">
                        Kova
                    </Link>
                    <div class="flex items-center gap-6">
                        <slot name="nav" />
                    </div>
                </div>
            </div>
        </nav>

        <main class="flex-1 flex items-center justify-center px-6 py-16 md:py-24">
            <div class="w-full max-w-md">
                <slot />
            </div>
        </main>
    </div>
</template>
