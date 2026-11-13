<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { kes } from '@/money';

const props = defineProps({ orders: Object, statuses: Array, filters: Object });
const f = reactive({ ...props.filters });
const apply = () => router.get('/admin/orders', Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true });

const badge = (s) => ({
    paid: 'bg-emerald-50 text-emerald-700', delivered: 'bg-emerald-50 text-emerald-700', completed: 'bg-emerald-50 text-emerald-700',
    cancelled: 'bg-red-50 text-red-600', refunded: 'bg-red-50 text-red-600',
    awaiting_payment: 'bg-amber-50 text-amber-700',
}[s] || 'bg-navy-50 text-navy-700');
</script>

<template>
    <Head title="Orders — Admin" />
    <AdminLayout>
        <h1 class="text-xl font-bold text-navy-700 mb-6">Online Orders</h1>

        <div class="flex flex-wrap gap-3 mb-4">
            <input v-model="f.q" @keyup.enter="apply" placeholder="Order reference…" class="rounded-lg border-gray-200 text-sm" />
            <select v-model="f.status" @change="apply" class="rounded-lg border-gray-200 text-sm">
                <option value="">All statuses</option>
                <option v-for="s in statuses" :key="s" :value="s">{{ s.replaceAll('_', ' ') }}</option>
            </select>
            <select v-model="f.payment_status" @change="apply" class="rounded-lg border-gray-200 text-sm">
                <option value="">Any payment</option>
                <option value="paid">Paid</option>
                <option value="unpaid">Unpaid</option>
                <option value="refunded">Refunded</option>
            </select>
            <input v-model="f.from" @change="apply" type="date" class="rounded-lg border-gray-200 text-sm" />
            <input v-model="f.to" @change="apply" type="date" class="rounded-lg border-gray-200 text-sm" />
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-400 uppercase border-b">
                        <th class="p-3">Reference</th><th>Customer</th><th>Status</th><th>Payment</th><th class="text-right">Total</th><th>Placed</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="o in orders.data" :key="o.id" class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="p-3 font-mono text-xs font-semibold">{{ o.reference }}</td>
                        <td>{{ o.customer }}</td>
                        <td><span :class="['text-xs rounded-full px-2 py-0.5 capitalize', badge(o.status)]">{{ o.status.replaceAll('_', ' ') }}</span></td>
                        <td><span :class="['text-xs rounded-full px-2 py-0.5', o.payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700']">{{ o.payment_status }}</span></td>
                        <td class="text-right font-medium">{{ kes(o.total_cents) }}</td>
                        <td class="text-xs text-gray-400 whitespace-nowrap">{{ o.placed_at }}</td>
                        <td class="text-right pr-3"><Link :href="`/admin/orders/${o.id}`" class="text-xs text-navy-700 hover:underline">Open</Link></td>
                    </tr>
                    <tr v-if="!orders.data.length"><td colspan="7" class="p-6 text-center text-gray-400">No orders found.</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="orders.links" />
    </AdminLayout>
</template>
