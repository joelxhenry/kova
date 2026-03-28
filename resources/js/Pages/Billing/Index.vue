<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    subscription: { type: Object, default: null },
    transactions: { type: Array, default: () => [] },
    prices: { type: Object, required: true },
    onTrial: { type: Boolean, default: false },
    trialEndsAt: { type: String, default: null },
});

const confirmDialog = useConfirm();

const statusLabels = {
    active: 'Active',
    past_due: 'Past Due',
    canceled: 'Cancelled',
    paused: 'Paused',
    trialing: 'Trial',
};

const statusColors = {
    active: 'text-accent bg-accent/10',
    past_due: 'text-accent bg-accent/20',
    canceled: 'text-muted-foreground bg-muted/50',
    paused: 'text-muted-foreground bg-muted/50',
    trialing: 'text-foreground bg-foreground/10',
};

const currentPlanLabel = () => {
    if (!props.subscription) return null;
    if (props.subscription.currentPriceId === props.prices.yearly) return 'Kova Pro Yearly';
    return 'Kova Pro Monthly';
};

const cancelSubscription = () => {
    confirmDialog.require({
        message: 'Cancel your subscription? You will retain access until the end of your current billing period.',
        header: 'Cancel Subscription',
        acceptClass: 'p-button-danger',
        accept: () => router.post('/billing/cancel'),
    });
};

const resumeSubscription = () => {
    router.post('/billing/resume');
};

const swapPlan = (priceId) => {
    router.post('/billing/swap', { price_id: priceId });
};

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-JM', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>

<template>
    <Head title="Billing" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20 max-w-2xl">
            <h1 class="text-2xl md:text-3xl font-bold tracking-tight leading-tight mb-8">Billing</h1>

            <!-- Trial Banner -->
            <div v-if="onTrial && !subscription" class="bg-accent/10 rounded-2xl p-5 mb-6">
                <div class="text-sm font-medium">You're on a free trial</div>
                <p class="text-xs text-muted-foreground mt-1">
                    Your trial ends {{ formatDate(trialEndsAt) }}.
                    <Link href="/billing/pricing" class="text-accent font-medium hover:underline ml-1">Subscribe now</Link>
                </p>
            </div>

            <!-- No subscription -->
            <div v-if="!subscription && !onTrial" class="bg-card rounded-2xl shadow-sm p-6 mb-6">
                <div class="text-sm font-medium mb-2">No active subscription</div>
                <p class="text-xs text-muted-foreground mb-4">Subscribe to unlock all features.</p>
                <Link href="/billing/pricing">
                    <Button label="View plans" size="small" />
                </Link>
            </div>

            <!-- Active subscription -->
            <div v-if="subscription" class="bg-card rounded-2xl shadow-sm p-5 md:p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <div class="text-sm font-medium">{{ currentPlanLabel() }}</div>
                        <span class="inline-block mt-1 text-[11px] font-medium px-2.5 py-0.5 rounded-full" :class="statusColors[subscription.status] || ''">
                            {{ statusLabels[subscription.status] || subscription.status }}
                        </span>
                    </div>
                </div>

                <div class="space-y-2 text-sm">
                    <div v-if="subscription.nextBilledAt" class="flex justify-between">
                        <span class="text-muted-foreground">Next billing</span>
                        <span class="tabular-nums">{{ formatDate(subscription.nextBilledAt) }}</span>
                    </div>
                    <div v-if="subscription.onGracePeriod" class="flex justify-between">
                        <span class="text-muted-foreground">Access until</span>
                        <span class="tabular-nums">{{ formatDate(subscription.endsAt) }}</span>
                    </div>
                </div>

                <div class="mt-5 pt-5 border-t border-border flex flex-wrap gap-2">
                    <!-- Swap plan -->
                    <Button
                        v-if="subscription.currentPriceId === prices.monthly && prices.yearly"
                        label="Switch to Yearly"
                        outlined
                        severity="secondary"
                        size="small"
                        @click="swapPlan(prices.yearly)"
                    />
                    <Button
                        v-if="subscription.currentPriceId === prices.yearly && prices.monthly"
                        label="Switch to Monthly"
                        outlined
                        severity="secondary"
                        size="small"
                        @click="swapPlan(prices.monthly)"
                    />

                    <!-- Cancel / Resume -->
                    <Button
                        v-if="subscription.onGracePeriod"
                        label="Resume"
                        size="small"
                        @click="resumeSubscription"
                    />
                    <Button
                        v-else-if="subscription.status === 'active'"
                        label="Cancel"
                        text
                        severity="danger"
                        size="small"
                        @click="cancelSubscription"
                    />
                </div>
            </div>

            <!-- Transaction History -->
            <div v-if="transactions.length > 0" class="bg-card rounded-2xl shadow-sm p-5 md:p-6">
                <h2 class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-4">Transaction History</h2>

                <div
                    v-for="txn in transactions"
                    :key="txn.id"
                    class="flex items-center justify-between py-3 border-b border-border last:border-0"
                >
                    <div>
                        <div class="text-sm tabular-nums">{{ formatDate(txn.billed_at) }}</div>
                        <div class="text-xs text-muted-foreground">{{ txn.status }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium tabular-nums">{{ txn.total }}</span>
                        <a v-if="txn.receipt_url" :href="txn.receipt_url" target="_blank" class="text-xs text-accent hover:underline">
                            Receipt
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
