<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    stats: { type: Object, required: true },
    recentSignups: { type: Array, default: () => [] },
    signupChart: { type: Array, default: () => [] },
    year: { type: Number, required: true },
});

const { formatJMD } = useCurrencyFormatter();

const formatDate = (d) => {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-JM', { year: 'numeric', month: 'short', day: 'numeric' });
};

const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const maxSignups = Math.max(...props.signupChart.map(m => m.count), 1);
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <section class="py-12 md:py-20">
            <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none mb-10">Admin Dashboard</h1>

            <!-- Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-10">
                <div class="bg-card rounded-2xl shadow-sm p-6">
                    <div class="text-xs font-medium text-muted-foreground">Total Users</div>
                    <div class="text-3xl font-bold tabular-nums mt-2">{{ stats.totalUsers }}</div>
                </div>
                <div class="bg-card rounded-2xl shadow-sm p-6">
                    <div class="text-xs font-medium text-muted-foreground">Active</div>
                    <div class="text-3xl font-bold tabular-nums mt-2">{{ stats.activeUsers }}</div>
                </div>
                <div class="bg-card rounded-2xl shadow-sm p-6">
                    <div class="text-xs font-medium text-muted-foreground">Suspended</div>
                    <div class="text-3xl font-bold tabular-nums mt-2" :class="stats.suspendedUsers > 0 ? 'text-accent' : ''">
                        {{ stats.suspendedUsers }}
                    </div>
                </div>
                <div class="bg-card rounded-2xl shadow-sm p-6">
                    <div class="text-xs font-medium text-muted-foreground">Invoices</div>
                    <div class="text-3xl font-bold tabular-nums mt-2">{{ stats.invoiceCount }}</div>
                </div>
                <div class="bg-card rounded-2xl shadow-sm p-6">
                    <div class="text-xs font-medium text-muted-foreground">Total Paid</div>
                    <div class="text-xl font-bold tabular-nums mt-2">{{ formatJMD(stats.totalInvoiced) }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Signup Chart -->
                <div class="bg-card rounded-2xl shadow-sm p-6">
                    <h2 class="text-sm font-medium text-muted-foreground mb-6">Signups — {{ year }}</h2>

                    <div class="flex items-end gap-2 h-32">
                        <div
                            v-for="(month, index) in signupChart"
                            :key="index"
                            class="flex-1 flex flex-col items-center gap-1"
                        >
                            <span class="text-xs tabular-nums text-muted-foreground">
                                {{ month.count || '' }}
                            </span>
                            <div
                                class="w-full rounded-t-md transition-all duration-300"
                                :class="month.count > 0 ? 'bg-accent/20' : 'bg-muted/30'"
                                :style="{ height: `${Math.max((month.count / maxSignups) * 100, 4)}%` }"
                            ></div>
                            <span class="text-[10px] text-muted-foreground">{{ monthLabels[index] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Signups -->
                <div class="bg-card rounded-2xl shadow-sm p-6">
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
                            <div class="flex items-center gap-2">
                                <span v-if="user.suspended_at" class="text-[10px] font-medium text-accent bg-accent/10 px-2 py-0.5 rounded-full">
                                    Suspended
                                </span>
                                <span class="text-xs text-muted-foreground tabular-nums">
                                    {{ formatDate(user.created_at) }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </AdminLayout>
</template>
