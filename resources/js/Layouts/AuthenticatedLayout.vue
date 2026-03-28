<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Button from 'primevue/button';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Popover from 'primevue/popover';
import { useToast } from 'primevue/usetoast';
import { usePwaInstall } from '@/Composables/usePwaInstall.js';

const page = usePage();
const toast = useToast();
const user = computed(() => page.props.auth.user);
const unreadCount = computed(() => page.props.notifications?.unreadCount ?? 0);
const recentNotifications = computed(() => page.props.notifications?.recent ?? []);
const { canInstall, showIosPrompt, showBanner, install, dismiss } = usePwaInstall();

const subscription = computed(() => page.props.subscription);
const showTrialBanner = computed(() => {
    return subscription.value?.onTrial && subscription.value?.trialDaysLeft !== null && subscription.value?.trialDaysLeft >= 0;
});

const navigation = [
    { name: 'Clients', href: '/clients' },
    { name: 'Invoices', href: '/invoices' },
    { name: 'Settings', href: '/settings' },
];

const isActive = (href) => {
    if (href === '/dashboard') return page.url === '/dashboard';
    return page.url.startsWith(href);
};

const logout = () => {
    router.post('/logout');
};

// Popovers
const notifPopover = ref(null);
const userPopover = ref(null);

const toggleNotifications = (event) => {
    notifPopover.value.toggle(event);
};

const toggleUserMenu = (event) => {
    userPopover.value.toggle(event);
};

const markAsRead = (id) => {
    router.post(`/notifications/${id}/read`, {}, { preserveScroll: true });
};

const markAllRead = () => {
    router.post('/notifications/mark-all-read', {}, { preserveScroll: true });
};

const initials = computed(() => {
    const name = user.value?.name ?? '';
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
});

// Show toast on flash status
watch(() => page.props.flash.status, (message) => {
    if (message) {
        toast.add({ severity: 'success', summary: message, life: 4000 });
    }
}, { immediate: true });
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <Toast position="top-center" />
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
                    <div v-if="canInstall" class="flex items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium">Install Kova</p>
                            <p class="text-xs text-white/60 mt-0.5 hidden sm:block">Add to your home screen for quick access.</p>
                        </div>
                        <Button label="Install" size="small" class="!bg-accent !border-accent !text-white shrink-0" @click="install" />
                        <button @click="dismiss" class="p-1.5 text-white/40 hover:text-white transition-colors" aria-label="Dismiss">
                            <i class="pi pi-times text-sm"></i>
                        </button>
                    </div>
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

        <!-- Trial Banner -->
        <div v-if="showTrialBanner" class="bg-accent/10 border-b border-accent/20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 md:px-12 lg:px-16 py-2 flex items-center justify-between gap-3">
                <p class="text-sm text-foreground">
                    <span class="font-medium">{{ subscription.trialDaysLeft }}</span>
                    {{ subscription.trialDaysLeft === 1 ? 'day' : 'days' }} left on your free trial
                </p>
                <Link href="/billing/pricing" class="text-sm text-accent font-medium hover:underline shrink-0">
                    Subscribe now
                </Link>
            </div>
        </div>

        <!-- Top navbar -->
        <nav class="bg-card shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 md:px-12 lg:px-16">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-6 md:gap-8">
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

                    <div class="flex items-center gap-2">
                        <!-- Notifications -->
                        <button
                            @click="toggleNotifications"
                            class="relative p-2 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted/30 transition-all duration-200"
                        >
                            <i class="pi pi-bell text-lg"></i>
                            <span
                                v-if="unreadCount > 0"
                                class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-accent text-white text-[10px] font-bold flex items-center justify-center rounded-full"
                            >
                                {{ unreadCount > 9 ? '9+' : unreadCount }}
                            </span>
                        </button>

                        <!-- Avatar / User Menu -->
                        <button
                            @click="toggleUserMenu"
                            class="w-9 h-9 rounded-full bg-dark-surface text-white text-xs font-bold flex items-center justify-center hover:ring-2 hover:ring-accent/30 transition-all duration-200"
                        >
                            {{ initials }}
                        </button>
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

        <!-- Notifications Popover -->
        <Popover ref="notifPopover" class="w-80 max-w-[calc(100vw-2rem)]">
            <div class="p-2">
                <div class="flex items-center justify-between px-2 pb-2 mb-1 border-b border-border">
                    <span class="text-sm font-medium">Notifications</span>
                    <button v-if="unreadCount > 0" @click="markAllRead" class="text-xs text-accent hover:underline">
                        Mark all read
                    </button>
                </div>

                <div v-if="recentNotifications.length === 0" class="px-2 py-6 text-center text-sm text-muted-foreground">
                    No notifications.
                </div>

                <div v-else class="max-h-72 overflow-y-auto">
                    <div
                        v-for="notif in recentNotifications"
                        :key="notif.id"
                        class="px-2 py-2.5 rounded-lg hover:bg-muted/30 transition-colors cursor-pointer"
                        :class="!notif.read_at ? 'bg-accent/5' : ''"
                        @click="!notif.read_at && markAsRead(notif.id)"
                    >
                        <div class="flex items-start gap-2">
                            <div
                                v-if="!notif.read_at"
                                class="w-2 h-2 rounded-full bg-accent shrink-0 mt-1.5"
                            ></div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm leading-snug">{{ notif.message }}</p>
                                <p class="text-[11px] text-muted-foreground mt-0.5">{{ notif.created_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-2 mt-1 border-t border-border">
                    <Link href="/notifications" class="block text-center text-xs text-accent font-medium py-1 hover:underline">
                        View all notifications
                    </Link>
                </div>
            </div>
        </Popover>

        <!-- User Menu Popover -->
        <Popover ref="userPopover" class="w-56">
            <div class="p-1">
                <div class="px-3 py-2 mb-1 border-b border-border">
                    <div class="text-sm font-medium truncate">{{ user.name }}</div>
                    <div class="text-xs text-muted-foreground truncate">{{ user.email }}</div>
                </div>

                <Link
                    href="/settings"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg hover:bg-muted/30 transition-colors"
                >
                    <i class="pi pi-user text-muted-foreground"></i>
                    Profile & Settings
                </Link>
                <Link
                    href="/dashboard"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg hover:bg-muted/30 transition-colors"
                >
                    <i class="pi pi-home text-muted-foreground"></i>
                    Dashboard
                </Link>
                <Link
                    href="/billing"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg hover:bg-muted/30 transition-colors"
                >
                    <i class="pi pi-credit-card text-muted-foreground"></i>
                    Billing
                </Link>

                <div class="border-t border-border mt-1 pt-1">
                    <button
                        @click="logout"
                        class="flex items-center gap-2.5 w-full px-3 py-2 text-sm rounded-lg text-accent hover:bg-accent/10 transition-colors"
                    >
                        <i class="pi pi-sign-out"></i>
                        Logout
                    </button>
                </div>
            </div>
        </Popover>
    </div>
</template>
