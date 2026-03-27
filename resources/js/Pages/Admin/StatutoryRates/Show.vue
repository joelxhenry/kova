<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';

const props = defineProps({
    rateKey: { type: String, required: true },
    label: { type: String, required: true },
    description: { type: String, default: '' },
    versions: { type: Array, default: () => [] },
    auditLogs: { type: Array, default: () => [] },
});

const isPercentage = props.rateKey.includes('rate') || props.rateKey.includes('levy');

const currentVersion = props.versions[0];

const form = useForm({
    value: null,
    effective_from: '',
});

const formatDate = (d) => d ? d.toISOString().split('T')[0] : null;

const submit = () => {
    form.transform((data) => ({
        ...data,
        effective_from: formatDate(data.effective_from),
    })).post(`/admin/statutory-rates/${props.rateKey}`);
};

const formatDisplayDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-JM', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatAuditDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-JM', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const formatValue = (val) => {
    if (isPercentage) return `${Number(val).toFixed(4)}%`;
    return `J$${Number(val).toLocaleString('en-JM', { minimumFractionDigits: 2 })}`;
};
</script>

<template>
    <Head :title="label" />

    <AdminLayout>
        <section class="py-12 md:py-20 max-w-3xl">
            <Link href="/admin/statutory-rates" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">
                &larr; Statutory Rates
            </Link>

            <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none mt-4">{{ label }}</h1>
            <p class="text-sm text-muted-foreground mt-1">{{ description }}</p>

            <!-- Current Value -->
            <div class="bg-card rounded-2xl shadow-sm p-6 mt-8">
                <div class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-2">Current Value</div>
                <div class="text-2xl font-bold tabular-nums">{{ formatValue(currentVersion.value) }}</div>
                <div class="text-sm text-muted-foreground mt-1">Effective from {{ formatDisplayDate(currentVersion.effective_from) }}</div>
            </div>

            <!-- Add New Version -->
            <div class="bg-card rounded-2xl shadow-sm p-6 mt-6">
                <h2 class="text-sm font-medium text-muted-foreground mb-4">Add New Version</h2>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel :value="isPercentage ? 'New Rate (%)' : 'New Amount (JMD)'" />
                            <InputNumber
                                v-model="form.value"
                                :minFractionDigits="isPercentage ? 2 : 0"
                                :maxFractionDigits="4"
                                :min="0"
                                fluid
                                :invalid="!!form.errors.value"
                            />
                            <InputError :message="form.errors.value" />
                        </div>
                        <div>
                            <InputLabel value="Effective From" />
                            <DatePicker
                                v-model="form.effective_from"
                                dateFormat="yy-mm-dd"
                                showIcon
                                fluid
                                :invalid="!!form.errors.effective_from"
                            />
                            <InputError :message="form.errors.effective_from" />
                        </div>
                    </div>
                    <Button type="submit" label="Add version" :loading="form.processing" size="small" />
                </form>
            </div>

            <!-- Version History -->
            <div class="mt-8">
                <h2 class="text-sm font-medium text-muted-foreground mb-4">Version History</h2>

                <div class="bg-card rounded-2xl shadow-sm overflow-hidden">
                    <div class="grid grid-cols-12 py-3 px-6 text-xs font-medium text-muted-foreground uppercase tracking-wider border-b border-border">
                        <div class="col-span-5">Value</div>
                        <div class="col-span-4">Effective From</div>
                        <div class="col-span-3 text-right">Status</div>
                    </div>

                    <div
                        v-for="(version, index) in versions"
                        :key="version.id"
                        class="grid grid-cols-12 items-center py-3 px-6 border-b border-border last:border-0"
                        :class="index === 0 ? 'bg-accent/5' : ''"
                    >
                        <div class="col-span-5 tabular-nums text-sm font-medium">
                            {{ formatValue(version.value) }}
                        </div>
                        <div class="col-span-4 text-sm text-muted-foreground tabular-nums">
                            {{ formatDisplayDate(version.effective_from) }}
                        </div>
                        <div class="col-span-3 text-right">
                            <span v-if="index === 0" class="text-xs font-medium text-accent">Current</span>
                            <span v-else class="text-xs text-muted-foreground">Historical</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Log -->
            <div v-if="auditLogs.length" class="mt-8">
                <h2 class="text-sm font-medium text-muted-foreground mb-4">Change Log</h2>

                <div class="space-y-3">
                    <div
                        v-for="log in auditLogs"
                        :key="log.id"
                        class="bg-muted/20 rounded-xl p-4 text-sm"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-medium">{{ log.user?.name ?? 'System' }}</span>
                            <span class="text-xs text-muted-foreground tabular-nums">{{ formatAuditDate(log.changed_at) }}</span>
                        </div>
                        <div class="mt-1 text-muted-foreground">
                            <span class="tabular-nums">{{ formatValue(log.old_value) }}</span>
                            &rarr; <span class="tabular-nums font-medium text-foreground">{{ formatValue(log.new_value) }}</span>
                            <span class="ml-2 text-xs">(effective {{ formatDisplayDate(log.new_effective_from) }})</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </AdminLayout>
</template>
