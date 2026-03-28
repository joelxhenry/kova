<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from 'primevue/button';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    user: { type: Object, required: true },
    stats: { type: Object, required: true },
});

const confirmDialog = useConfirm();

const suspendUser = () => {
    confirmDialog.require({
        message: `Suspend "${props.user.name}"? They will be logged out and unable to access the application.`,
        header: 'Suspend User',
        acceptClass: 'p-button-danger',
        accept: () => router.post(`/admin/users/${props.user.id}/suspend`),
    });
};

const reactivateUser = () => {
    router.post(`/admin/users/${props.user.id}/reactivate`);
};

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-JM', { year: 'numeric', month: 'long', day: 'numeric' });
};

const formatDateTime = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-JM', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head :title="user.name" />

    <AdminLayout>
        <section class="py-8 md:py-16 lg:py-20 max-w-4xl">
            <Link href="/admin/users" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">
                &larr; Users
            </Link>

            <div class="flex items-start justify-between mt-4 mb-8">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight leading-tight">{{ user.name }}</h1>
                    <p class="text-muted-foreground mt-1">{{ user.email }}</p>
                    <div class="mt-2">
                        <span v-if="user.suspended_at" class="text-xs font-medium text-accent bg-accent/10 px-3 py-1 rounded-full">
                            Suspended {{ formatDateTime(user.suspended_at) }}
                        </span>
                        <span v-else class="text-xs font-medium text-muted-foreground bg-muted/50 px-3 py-1 rounded-full">
                            Active
                        </span>
                    </div>
                </div>
                <div>
                    <Button
                        v-if="!user.suspended_at"
                        label="Suspend"
                        severity="danger"
                        text
                        size="small"
                        @click="suspendUser"
                    />
                    <Button
                        v-else
                        label="Reactivate"
                        text
                        size="small"
                        @click="reactivateUser"
                    />
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-4 mb-8">
                <div class="bg-card rounded-2xl shadow-sm p-5">
                    <div class="text-xs font-medium text-muted-foreground">Clients</div>
                    <div class="text-2xl font-bold tabular-nums mt-1">{{ stats.clientCount }}</div>
                </div>
                <div class="bg-card rounded-2xl shadow-sm p-5">
                    <div class="text-xs font-medium text-muted-foreground">Invoices</div>
                    <div class="text-2xl font-bold tabular-nums mt-1">{{ stats.invoiceCount }}</div>
                </div>
            </div>

            <!-- Account Details -->
            <div class="bg-card rounded-2xl shadow-sm p-5 md:p-6">
                <h2 class="text-sm font-medium text-muted-foreground mb-4">Account Details</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Joined</span>
                        <span>{{ formatDate(user.created_at) }}</span>
                    </div>
                    <div v-if="user.business_name" class="flex justify-between">
                        <span class="text-muted-foreground">Business Name</span>
                        <span>{{ user.business_name }}</span>
                    </div>
                </div>
            </div>
        </section>
    </AdminLayout>
</template>
