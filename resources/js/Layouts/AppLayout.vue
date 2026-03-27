<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <nav class="border-b border-border">
            <div class="mx-auto max-w-5xl px-6 md:px-12 lg:px-16">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/" class="text-xl font-bold tracking-tighter uppercase">
                        Kova
                    </Link>
                </div>
            </div>
        </nav>

        <main class="mx-auto max-w-5xl px-6 md:px-12 lg:px-16">
            <slot />
        </main>
    </div>
</template>
