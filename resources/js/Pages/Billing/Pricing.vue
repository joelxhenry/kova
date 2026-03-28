<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from 'primevue/button';

const props = defineProps({
    prices: { type: Object, required: true },
    isSubscribed: { type: Boolean, default: false },
    onTrial: { type: Boolean, default: false },
});

const page = usePage();
const interval = ref('monthly');

const features = [
    'Unlimited clients & invoices',
    'Professional PDF invoices',
    'Email invoices to clients',
    'Tax calculation engine',
    'Quarterly payment estimates',
    'GCT threshold tracking',
    'Withholding tax ledger',
    'TAJ S04 form generation',
];

const checkout = (priceId) => {
    if (!priceId || !window.Paddle) return;
    window.Paddle.Checkout.open({
        items: [{ priceId, quantity: 1 }],
        customer: {
            email: page.props.auth.user.email,
        },
        customData: {
            user_id: String(page.props.auth.user.id),
        },
    });
};
</script>

<template>
    <Head title="Pricing" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20 max-w-2xl mx-auto text-center">
            <h1 class="text-3xl md:text-4xl font-bold tracking-tight leading-tight">
                Simple pricing
            </h1>
            <p class="mt-2 text-muted-foreground">
                Everything you need to manage your contracting income and taxes.
            </p>

            <!-- Interval Toggle -->
            <div class="flex items-center justify-center gap-2 mt-8">
                <button
                    @click="interval = 'monthly'"
                    class="px-4 py-2 text-sm font-medium rounded-full transition-all duration-200"
                    :class="interval === 'monthly' ? 'bg-foreground text-background' : 'text-muted-foreground hover:text-foreground'"
                >
                    Monthly
                </button>
                <button
                    @click="interval = 'yearly'"
                    class="px-4 py-2 text-sm font-medium rounded-full transition-all duration-200"
                    :class="interval === 'yearly' ? 'bg-foreground text-background' : 'text-muted-foreground hover:text-foreground'"
                >
                    Yearly
                    <span class="ml-1 text-xs text-accent font-medium">Save 20%</span>
                </button>
            </div>

            <!-- Plan Card -->
            <div class="mt-8 bg-card rounded-2xl shadow-sm border border-border p-6 md:p-8 text-left">
                <div class="text-sm font-medium text-muted-foreground">Kova Pro</div>
                <div class="mt-2 text-3xl font-bold tracking-tight">
                    <template v-if="interval === 'monthly'">
                        $9<span class="text-lg text-muted-foreground font-normal">/mo</span>
                    </template>
                    <template v-else>
                        $86<span class="text-lg text-muted-foreground font-normal">/yr</span>
                    </template>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">14-day free trial included</p>

                <ul class="mt-6 space-y-3">
                    <li v-for="feature in features" :key="feature" class="flex items-start gap-2 text-sm">
                        <i class="pi pi-check text-accent mt-0.5 shrink-0"></i>
                        <span>{{ feature }}</span>
                    </li>
                </ul>

                <div class="mt-8">
                    <Button
                        v-if="isSubscribed"
                        label="Already subscribed"
                        disabled
                        class="w-full"
                        size="large"
                    />
                    <Button
                        v-else
                        :label="onTrial ? 'Subscribe now' : 'Start free trial'"
                        class="w-full"
                        size="large"
                        @click="checkout(interval === 'monthly' ? prices.monthly : prices.yearly)"
                        :disabled="!prices.monthly"
                    />
                    <p v-if="!prices.monthly" class="mt-2 text-xs text-muted-foreground text-center">
                        Paddle price IDs not configured. Set PADDLE_PRICE_MONTHLY and PADDLE_PRICE_YEARLY in .env.
                    </p>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
