<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';

const props = defineProps({
    notifications: { type: Object, required: true },
});

const page = usePage();

const markAsRead = (id) => {
    router.post(`/notifications/${id}/read`);
};

const markAllRead = () => {
    router.post('/notifications/mark-all-read');
};

const formatTime = (date) => {
    return new Date(date).toLocaleDateString('en-JM', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Notifications" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20 max-w-3xl">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tight leading-tight">Notifications</h1>
                </div>
                <Button
                    v-if="notifications.data.some(n => !n.read_at)"
                    label="Mark all read"
                    severity="secondary"
                    outlined
                    size="small"
                    @click="markAllRead"
                />
            </div>

            <div
                v-if="page.props.flash.status"
                class="mb-6 bg-accent/10 rounded-xl px-4 py-3 text-sm text-foreground"
            >
                {{ page.props.flash.status }}
            </div>

            <div v-if="notifications.data.length === 0" class="py-20 text-center text-muted-foreground">
                <p class="text-lg">No notifications yet.</p>
                <p class="mt-2 text-sm">You'll receive reminders for quarterly payments, overdue invoices, and GCT threshold alerts.</p>
            </div>

            <div v-else class="space-y-2">
                <div
                    v-for="notification in notifications.data"
                    :key="notification.id"
                    class="flex items-start gap-4 p-4 rounded-xl transition-all duration-200"
                    :class="notification.read_at ? 'bg-card' : 'bg-accent/5'"
                >
                    <div class="flex-1">
                        <p class="text-sm text-foreground">{{ notification.data.message }}</p>
                        <p class="text-xs text-muted-foreground mt-1">{{ formatTime(notification.created_at) }}</p>
                    </div>
                    <Button
                        v-if="!notification.read_at"
                        label="Read"
                        text
                        size="small"
                        @click="markAsRead(notification.id)"
                    />
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.last_page > 1" class="mt-8 flex gap-2">
                <a
                    v-for="link in notifications.links"
                    :key="link.label"
                    :href="link.url"
                    class="px-3 py-1 text-sm rounded-lg transition-colors duration-200"
                    :class="link.active ? 'bg-accent/10 text-accent' : 'text-muted-foreground hover:text-foreground'"
                    v-html="link.label"
                />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
