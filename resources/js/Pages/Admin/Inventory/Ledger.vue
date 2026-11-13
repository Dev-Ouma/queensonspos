<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({ transactions: Object, types: Array, filters: Object });
const f = reactive({ ...props.filters });
const apply = () => router.get('/admin/inventory/ledger', Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true });
</script>

<template>
    <Head title="Stock Ledger — Admin" />
    <AdminLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-bold text-navy-700">Stock Ledger <span class="text-xs font-normal text-gray-400">(immutable)</span></h1>
            <Link href="/admin/inventory" class="text-sm text-gray-500 hover:text-navy-700">← Inventory</Link>
        </div>

        <select v-model="f.type" @change="apply" class="rounded-lg border-gray-200 text-sm mb-4">
            <option value="">All movement types</option>
            <option v-for="t in types" :key="t" :value="t">{{ t.replaceAll('_', ' ') }}</option>
        </select>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-400 uppercase border-b">
                        <th class="p-3">Reference</th><th>Product</th><th>Warehouse</th><th class="text-right">Qty</th><th>Type</th><th>By</th><th>Reason</th><th>When</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="t in transactions.data" :key="t.reference" class="border-b border-gray-50">
                        <td class="p-3 font-mono text-[11px] text-gray-500">{{ t.reference.slice(-10) }}</td>
                        <td>{{ t.product }} <span class="text-xs text-gray-400">{{ t.sku }}</span></td>
                        <td class="text-xs">{{ t.warehouse }}</td>
                        <td :class="['text-right font-bold', t.qty_delta > 0 ? 'text-emerald-700' : 'text-red-600']">{{ t.qty_delta > 0 ? '+' : '' }}{{ t.qty_delta }}</td>
                        <td><span class="text-xs bg-navy-50 rounded px-2 py-0.5">{{ t.type.replaceAll('_', ' ') }}</span></td>
                        <td class="text-xs">{{ t.user }}</td>
                        <td class="text-xs text-gray-400">{{ t.reason }}</td>
                        <td class="text-xs text-gray-400 whitespace-nowrap">{{ t.at }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="transactions.links" />
    </AdminLayout>
</template>
