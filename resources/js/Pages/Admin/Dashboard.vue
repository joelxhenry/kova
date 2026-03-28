<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    stats: { type: Object, required: true },
    recentSignups: { type: Array, default: () => [] },
});

const formatDate = (d) => {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-JM', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <section class="py-8 md:py-16 lg:py-20">
            <h1 class="text-xl md:text-2xl font-bold tracking-tighter leading-none mb-8">Admin Dashboard</h1>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="bg-card rounded-2xl shadow-sm p-5">
                    <div class="text-xs font-medium text-muted-foreground">Total Users</div>
                    <div class="text-2xl font-bold tabular-nums mt-2">{{ stats.totalUsers }}</div>
                </div>
                <div class="bg-card rounded-2xl shadow-sm p-5">
                    <div class="text-xs font-medium text-muted-foreground">Active</div>
                    <div class="text-2xl font-bold tabular-nums mt-2">{{ stats.activeUsers }}</div>
                </div>
                <div class="bg-card rounded-2xl shadow-sm p-5">
                    <div class="text-xs font-medium text-muted-foreground">Suspended</div>
                    <div class="text-2xl font-bold tabular-nums mt-2" :class="stats.suspendedUsers > 0 ? 'text-accent' : ''">
                        {{ stats.suspendedUsers }}
                    </div>
                </div>
            </div>

            <!-- Recent Signups -->
            <div class="bg-card rounded-2xl shadow-sm p-5 md:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-medium text-muted-foreground">Recent Signups</h2>
                    <Link href="/admin/users" class="text-xs text-accent hover:underline">View all</Link>
                </div>

                <div v-if="recentSignups.length === 0" class="text-sm text-muted-foreground py-4">
                    No users yet.
                </div>

                <div v-else>
                    <Link
                        v-for="user in recentSignups"
                        :key="user.id"
                        :href="`/admin/users/${user.id}`"
                        class="flex items-center justify-between py-3 border-b border-border last:border-0 hover:bg-muted/30 -mx-2 px-2 rounded-lg transition-colors duration-150"
                    >
                        <div>
                            <div class="text-sm font-medium">{{ user.name }}</div>
                            <div class="text-xs text-muted-foreground">{{ user.email }}</div>
                        </div>
                        <span class="text-xs text-muted-foreground tabular-nums">{{ formatDate(user.created_at) }}</span>
                    </Link>
                </div>
            </div>
        </section>
    </AdminLayout>
</template>
