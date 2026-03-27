<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    stats: { type: Object, required: true },
    recentSignups: { type: Array, default: () => [] },
});

const { formatJMD } = useCurrencyFormatter();

const formatDate = (d) => {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-JM', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <section class="py-12 md:py-20">
            <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none mb-10">Admin Dashboard</h1>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-10">
                <div class="bg-card rounded-2xl shadow-sm p-6">
                    <div class="text-xs font-medium text-muted-foreground">Total Users</div>
                    <div class="text-3xl font-bold tabular-nums mt-2">{{ stats.totalUsers }}</div>
                </div>
            </div>

            <!-- Recent Signups -->
            <div class="bg-card rounded-2xl shadow-sm p-6">
                <h2 class="text-sm font-medium text-muted-foreground mb-4">Recent Signups</h2>

                <div v-if="recentSignups.length === 0" class="text-sm text-muted-foreground py-4">
                    No users yet.
                </div>

                <div v-else>
                    <div
                        v-for="user in recentSignups"
                        :key="user.id"
                        class="flex items-center justify-between py-3 border-b border-border last:border-0"
                    >
                        <div>
                            <div class="text-sm font-medium">{{ user.name }}</div>
                            <div class="text-xs text-muted-foreground">{{ user.email }}</div>
                        </div>
                        <div class="text-xs text-muted-foreground tabular-nums">
                            {{ formatDate(user.created_at) }}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </AdminLayout>
</template>
