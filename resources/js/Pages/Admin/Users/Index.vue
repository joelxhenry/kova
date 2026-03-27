<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? null);

const statusOptions = [
    { label: 'All', value: null },
    { label: 'Active', value: 'active' },
    { label: 'Suspended', value: 'suspended' },
];

const applyFilters = () => {
    router.get('/admin/users', {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true });
};

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-JM', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>

<template>
    <Head title="Users" />

    <AdminLayout>
        <section class="py-12 md:py-20">
            <h1 class="text-3xl md:text-4xl font-bold tracking-tighter leading-none mb-10">Users</h1>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-8">
                <InputText
                    v-model="search"
                    placeholder="Search name or email..."
                    @keydown.enter="applyFilters"
                    class="w-64"
                />
                <Select
                    v-model="status"
                    :options="statusOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="All"
                    @change="applyFilters"
                />
                <Button label="Search" text size="small" @click="applyFilters" />
            </div>

            <div v-if="users.data.length === 0" class="py-20 text-center text-muted-foreground">
                <p class="text-lg">No users found.</p>
            </div>

            <div v-else class="bg-card rounded-2xl shadow-sm overflow-hidden">
                <div class="grid grid-cols-12 py-3 px-6 text-xs font-medium text-muted-foreground uppercase tracking-wider border-b border-border">
                    <div class="col-span-4">User</div>
                    <div class="col-span-3">Email</div>
                    <div class="col-span-2">Joined</div>
                    <div class="col-span-2">Status</div>
                    <div class="col-span-1 text-right">Action</div>
                </div>

                <Link
                    v-for="user in users.data"
                    :key="user.id"
                    :href="`/admin/users/${user.id}`"
                    class="grid grid-cols-12 items-center py-4 px-6 border-b border-border last:border-0 hover:bg-muted/30 transition-colors duration-150"
                >
                    <div class="col-span-4">
                        <div class="text-sm font-medium">{{ user.name }}</div>
                    </div>
                    <div class="col-span-3 text-sm text-muted-foreground">
                        {{ user.email }}
                    </div>
                    <div class="col-span-2 text-sm text-muted-foreground tabular-nums">
                        {{ formatDate(user.created_at) }}
                    </div>
                    <div class="col-span-2">
                        <span v-if="user.suspended_at" class="text-xs font-medium text-accent bg-accent/10 px-2 py-0.5 rounded-full">
                            Suspended
                        </span>
                        <span v-else class="text-xs font-medium text-muted-foreground bg-muted/50 px-2 py-0.5 rounded-full">
                            Active
                        </span>
                    </div>
                    <div class="col-span-1 text-right text-sm text-muted-foreground">
                        &rarr;
                    </div>
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="users.last_page > 1" class="mt-8 flex gap-2">
                <Link
                    v-for="link in users.links"
                    :key="link.label"
                    :href="link.url"
                    class="px-3 py-1 text-sm border border-border transition-colors duration-150"
                    :class="link.active ? 'bg-foreground text-background' : 'text-muted-foreground hover:text-foreground'"
                    v-html="link.label"
                />
            </div>
        </section>
    </AdminLayout>
</template>
