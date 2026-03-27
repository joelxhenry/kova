<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import InputLabel from '@/Components/UI/InputLabel.vue';
import InputError from '@/Components/UI/InputError.vue';
import PrimaryButton from '@/Components/UI/PrimaryButton.vue';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';

const props = defineProps({
    clients: { type: Array, default: () => [] },
});

const { formatJMD } = useCurrencyFormatter();

const form = useForm({
    client_id: '',
    issue_date: new Date().toISOString().split('T')[0],
    due_date: '',
    notes: '',
    items: [{ description: '', quantity: 1, unit_price: '' }],
});

const addItem = () => {
    form.items.push({ description: '', quantity: 1, unit_price: '' });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const subtotal = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);
    }, 0);
});

const submit = () => {
    form.post('/invoices');
};
</script>

<template>
    <Head title="New Invoice" />

    <AuthenticatedLayout>
        <section class="py-12 md:py-20 max-w-3xl">
            <div class="h-1 w-16 bg-accent mb-6" />
            <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none">New Invoice</h1>

            <form @submit.prevent="submit" class="mt-10 space-y-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <InputLabel value="Client" />
                        <select
                            v-model="form.client_id"
                            class="w-full h-12 md:h-14 bg-input border border-border px-4 text-base text-foreground outline-none transition-colors duration-150 focus:border-accent appearance-none"
                            :class="{ 'text-muted-foreground': !form.client_id }"
                        >
                            <option value="" disabled>Select a client</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <InputError :message="form.errors.client_id" />
                    </div>
                    <div>
                        <InputLabel value="Issue Date" />
                        <TextInput v-model="form.issue_date" type="date" :error="form.errors.issue_date" />
                        <InputError :message="form.errors.issue_date" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Due Date" />
                    <TextInput v-model="form.due_date" type="date" :error="form.errors.due_date" />
                    <InputError :message="form.errors.due_date" />
                </div>

                <!-- Line Items -->
                <div class="border-t border-border pt-8">
                    <h2 class="text-lg font-semibold tracking-tight mb-4">Line Items</h2>
                    <InputError :message="form.errors.items" />

                    <div class="space-y-4">
                        <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-12 gap-3 items-end">
                            <div class="col-span-6">
                                <InputLabel v-if="index === 0" value="Description" />
                                <TextInput v-model="item.description" :error="form.errors[`items.${index}.description`]" />
                                <InputError :message="form.errors[`items.${index}.description`]" />
                            </div>
                            <div class="col-span-2">
                                <InputLabel v-if="index === 0" value="Qty" />
                                <TextInput v-model="item.quantity" type="number" step="0.01" min="0.01" :error="form.errors[`items.${index}.quantity`]" />
                            </div>
                            <div class="col-span-3">
                                <InputLabel v-if="index === 0" value="Unit Price" />
                                <TextInput v-model="item.unit_price" type="number" step="0.01" min="0" :error="form.errors[`items.${index}.unit_price`]" />
                            </div>
                            <div class="col-span-1 pb-1">
                                <button
                                    v-if="form.items.length > 1"
                                    type="button"
                                    @click="removeItem(index)"
                                    class="text-muted-foreground hover:text-accent transition-colors duration-150 text-lg"
                                >
                                    &times;
                                </button>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="addItem"
                        class="mt-4 text-sm text-muted-foreground hover:text-foreground transition-colors duration-150 uppercase tracking-wider"
                    >
                        + Add line item
                    </button>

                    <div class="mt-6 text-right text-lg font-mono font-medium">
                        Subtotal: {{ formatJMD(subtotal) }}
                    </div>
                </div>

                <div>
                    <InputLabel value="Notes" />
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        class="w-full bg-input border border-border px-4 py-3 text-base text-foreground placeholder:text-muted-foreground outline-none transition-colors duration-150 focus:border-accent resize-none"
                    ></textarea>
                    <InputError :message="form.errors.notes" />
                </div>

                <div class="flex items-center gap-6 pt-4">
                    <PrimaryButton :disabled="form.processing">Create invoice</PrimaryButton>
                    <Link href="/invoices" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">Cancel</Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
