<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from 'primevue/button';

const page = usePage();
const user = computed(() => page.props.auth.user);
const unreadCount = computed(() => page.props.notifications?.unreadCount ?? 0);

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
        <nav class="bg-card shadow-sm">
            <div class="mx-auto max-w-7xl px-6 md:px-12 lg:px-16">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-8">
                        <Link href="/dashboard" class="text-xl font-bold text-foreground">
                            Kova
                        </Link>

                        <div class="hidden md:flex items-center gap-1">
                            <Link
                                v-for="item in navigation"
                                :key="item.name"
                                :href="item.href"
                                class="px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                                :class="isActive(item.href) ? 'text-foreground bg-muted/50' : 'text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                            >
                                {{ item.name }}
                            </Link>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Link href="/notifications" class="relative p-2 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted/30 transition-all duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                            <span
                                v-if="unreadCount > 0"
                                class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-accent text-white text-xs font-bold flex items-center justify-center rounded-full"
                            >
                                {{ unreadCount > 9 ? '9+' : unreadCount }}
                            </span>
                        </Link>
                        <span class="text-sm text-muted-foreground hidden sm:block">
                            {{ user.name }}
                        </span>
                        <Button label="Logout" text size="small" @click="logout" />
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile navigation -->
        <div class="md:hidden bg-card shadow-sm">
            <div class="flex items-center gap-1 px-6 py-2 overflow-x-auto">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    class="px-3 py-1.5 text-xs font-medium whitespace-nowrap rounded-full transition-all duration-200"
                    :class="isActive(item.href) ? 'text-foreground bg-muted' : 'text-muted-foreground'"
                >
                    {{ item.name }}
                </Link>
            </div>
        </div>

        <!-- Page content -->
        <main class="mx-auto max-w-7xl px-6 md:px-12 lg:px-16">
            <slot />
        </main>
    </div>
</template>
