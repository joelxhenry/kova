<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from 'primevue/button';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';
import { watch } from 'vue';

const page = usePage();
const toast = useToast();
const user = computed(() => page.props.auth.user);

const navigation = [
    { name: 'Dashboard', href: '/admin' },
{ name: 'Users', href: '/admin/users' },
];

const isActive = (href) => {
    if (href === '/admin') return page.url === '/admin';
    return page.url.startsWith(href);
};

const logout = () => {
    router.post('/logout');
};

watch(() => page.props.flash.status, (message) => {
    if (message) {
        toast.add({ severity: 'success', summary: message, life: 4000 });
    }
}, { immediate: true });
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <Toast position="top-right" />
        <ConfirmDialog />

        <!-- Top navbar -->
        <nav class="bg-dark-surface text-white shadow-sm">
            <div class="mx-auto max-w-7xl px-6 md:px-12 lg:px-16">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-8">
                        <Link href="/admin" class="text-xl font-bold">
                            Kova <span class="text-accent text-sm font-medium ml-1">Admin</span>
                        </Link>

                        <div class="hidden md:flex items-center gap-1">
                            <Link
                                v-for="item in navigation"
                                :key="item.name"
                                :href="item.href"
                                class="px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                                :class="isActive(item.href) ? 'text-white bg-white/10' : 'text-white/60 hover:text-white hover:bg-white/5'"
                            >
                                {{ item.name }}
                            </Link>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Link href="/dashboard" class="text-sm text-white/60 hover:text-white transition-colors">
                            User App &rarr;
                        </Link>
                        <span class="text-sm text-white/60 hidden sm:block">
                            {{ user.name }}
                        </span>
                        <Button label="Logout" text size="small" class="!text-white/60 hover:!text-white" @click="logout" />
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile navigation -->
        <div class="md:hidden bg-dark-surface shadow-sm">
            <div class="flex items-center gap-1 px-6 py-2 overflow-x-auto">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    class="px-3 py-1.5 text-xs font-medium whitespace-nowrap rounded-full transition-all duration-200"
                    :class="isActive(item.href) ? 'text-white bg-white/10' : 'text-white/60'"
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
