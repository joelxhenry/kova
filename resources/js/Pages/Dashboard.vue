<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Select from 'primevue/select';
import TaxSummaryCard from '@/Components/Domain/TaxSummaryCard.vue';
import QuarterlyEstimatesTimeline from '@/Components/Domain/QuarterlyEstimatesTimeline.vue';
import WithholdingCreditsWidget from '@/Components/Domain/WithholdingCreditsWidget.vue';
import GctThresholdTracker from '@/Components/Domain/GctThresholdTracker.vue';
import { useFiscalYear } from '@/Composables/useFiscalYear.js';

const props = defineProps({
    year: { type: Number, required: true },
    taxBreakdown: { type: Object, required: true },
    quarterlyEstimates: { type: Array, required: true },
    gctStatus: { type: Object, required: true },
});

const page = usePage();
const user = page.props.auth.user;
const { year: selectedYear, years, changeYear } = useFiscalYear(props.year);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <section class="py-8 md:py-16 lg:py-20">
            <div class="flex items-center justify-between mb-6 md:mb-10">
                <div>
                    <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold tracking-tight leading-tight">
                        Welcome back,<br>
                        <span class="text-accent">{{ user.name }}.</span>
                    </h1>
                </div>
                <Select
                    v-model="selectedYear"
                    :options="years"
                    optionLabel="label"
                    optionValue="value"
                    @change="changeYear(selectedYear)"
                />
            </div>

            <div class="space-y-4 md:space-y-6">
                <!-- Tax Summary -->
                <TaxSummaryCard :breakdown="taxBreakdown" />

                <!-- Quarterly Estimates -->
                <QuarterlyEstimatesTimeline :estimates="quarterlyEstimates" />

                <!-- Withholding + GCT row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
                    <WithholdingCreditsWidget :credits="taxBreakdown.withholdingCredits" :year="year" />
                    <GctThresholdTracker :gctStatus="gctStatus" />
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
