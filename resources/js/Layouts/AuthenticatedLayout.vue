<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from 'primevue/button';

const page = usePage();
const user = computed(() => page.props.auth.user);

const navigation = [
    { name: 'Dashboard', href: '/dashboard' },
    { name: 'Clients', href: '/clients' },
    { name: 'Invoices', href: '/invoices' },
    { name: 'Income', href: '/income' },
    { name: 'Expenses', href: '/expenses' },
    { name: 'Tax Profile', href: '/tax-profile' },
];

const isActive = (href) => {
    return page.url.startsWith(href);
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <!-- Top navbar -->
        <nav class="border-b border-border">
            <div class="mx-auto max-w-7xl px-6 md:px-12 lg:px-16">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-10">
                        <Link href="/dashboard" class="text-xl font-bold tracking-tighter uppercase">
                            Kova
                        </Link>

                        <div class="hidden md:flex items-center gap-1">
                            <Link
                                v-for="item in navigation"
                                :key="item.name"
                                :href="item.href"
                                class="relative px-4 py-2 text-sm uppercase tracking-wider transition-colors duration-150"
                                :class="isActive(item.href) ? 'text-foreground' : 'text-muted-foreground hover:text-foreground'"
                            >
                                {{ item.name }}
                                <span
                                    v-if="isActive(item.href)"
                                    class="absolute bottom-0 left-4 right-4 h-0.5 bg-accent"
                                />
                            </Link>
                        </div>
                    </div>

                    <div class="flex items-center gap-6">
                        <span class="text-sm text-muted-foreground hidden sm:block">
                            {{ user.name }}
                        </span>
                        <Button label="Logout" text size="small" @click="logout" />
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile navigation -->
        <div class="md:hidden border-b border-border">
            <div class="flex items-center gap-1 px-6 py-2 overflow-x-auto">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    class="relative px-3 py-2 text-xs uppercase tracking-wider whitespace-nowrap transition-colors duration-150"
                    :class="isActive(item.href) ? 'text-foreground' : 'text-muted-foreground'"
                >
                    {{ item.name }}
                    <span
                        v-if="isActive(item.href)"
                        class="absolute bottom-0 left-3 right-3 h-0.5 bg-accent"
                    />
                </Link>
            </div>
        </div>

        <!-- Page content -->
        <main class="mx-auto max-w-7xl px-6 md:px-12 lg:px-16">
            <slot />
        </main>
    </div>
</template>
