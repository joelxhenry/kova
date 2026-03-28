<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Button from 'primevue/button';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';
import { usePwaInstall } from '@/Composables/usePwaInstall.js';

const page = usePage();
const toast = useToast();
const user = computed(() => page.props.auth.user);
const unreadCount = computed(() => page.props.notifications?.unreadCount ?? 0);
const { canInstall, showIosPrompt, showBanner, install, dismiss } = usePwaInstall();

const navigation = [
    { name: 'Dashboard', href: '/dashboard' },
    { name: 'Clients', href: '/clients' },
    { name: 'Invoices', href: '/invoices' },
    { name: 'Settings', href: '/settings' },
];

const isActive = (href) => {
    return page.url.startsWith(href);
};

const logout = () => {
    router.post('/logout');
};

// Show toast on flash status
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

        <!-- PWA Install Banner -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            leave-active-class="transition-all duration-200 ease-in"
            enter-from-class="opacity-0 -translate-y-full"
            enter-to-class="opacity-100 translate-y-0"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-full"
        >
            <div v-if="showBanner" class="bg-dark-surface text-white">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 md:px-12 lg:px-16 py-3">
                    <!-- Standard install (Chrome, Edge, Firefox) -->
                    <div v-if="canInstall" class="flex items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium">Install Kova</p>
                            <p class="text-xs text-white/60 mt-0.5 hidden sm:block">Add to your home screen for quick access.</p>
                        </div>
                        <Button
                            label="Install"
                            size="small"
                            class="!bg-accent !border-accent !text-white shrink-0"
                            @click="install"
                        />
                        <button @click="dismiss" class="p-1.5 text-white/40 hover:text-white transition-colors" aria-label="Dismiss">
                            <i class="pi pi-times text-sm"></i>
                        </button>
                    </div>

                    <!-- iOS Safari instructions -->
                    <div v-else-if="showIosPrompt" class="flex items-start gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium">Install Kova</p>
                            <p class="text-xs text-white/60 mt-1 leading-relaxed">
                                Tap
                                <svg class="inline-block w-4 h-4 align-text-bottom mx-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                                then <span class="font-medium text-white">"Add to Home Screen"</span>
                            </p>
                        </div>
                        <button @click="dismiss" class="p-1.5 text-white/40 hover:text-white transition-colors shrink-0 mt-0.5" aria-label="Dismiss">
                            <i class="pi pi-times text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Top navbar -->
        <nav class="bg-card shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 md:px-12 lg:px-16">
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
            <div class="flex items-center gap-1 px-4 py-2 overflow-x-auto">
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
        <main class="mx-auto max-w-7xl px-4 sm:px-6 md:px-12 lg:px-16">
            <slot />
        </main>
    </div>
</template>
