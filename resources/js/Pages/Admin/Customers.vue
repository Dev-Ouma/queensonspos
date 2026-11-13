<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { kes } from '@/money';

const props = defineProps({ customers: Object, filters: Object });
const f = reactive({ ...props.filters });
const apply = () => router.get('/admin/customers', f.q ? { q: f.q } : {}, { preserveState: true });
</script>

<template>
    <Head title="Customers — Admin" />
    <AdminLayout>
        <h1 class="text-xl font-bold text-navy-700 mb-6">Customers</h1>
        <input v-model="f.q" @keyup.enter="apply" placeholder="Search name or email…" class="rounded-lg border-gray-200 text-sm mb-4" />

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-400 uppercase border-b"><th class="p-3">Name</th><th>Email</th><th>Phone</th><th>Orders</th><th class="text-right">Total Spent</th><th>Points</th><th>Joined</th></tr></thead>
                <tbody>
                    <tr v-for="c in customers.data" :key="c.id" class="border-b border-gray-50">
                        <td class="p-3 font-medium">{{ c.name }}</td>
                        <td class="text-xs">{{ c.email }}</td>
                        <td class="text-xs">{{ c.phone }}</td>
                        <td>{{ c.orders_count }}</td>
                        <td class="text-right">{{ kes(c.total_spent_cents) }}</td>
                        <td class="text-gold-dark font-semibold">{{ c.loyalty_points }}</td>
                        <td class="text-xs text-gray-400">{{ c.joined }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="customers.links" />
    </AdminLayout>
</template>
