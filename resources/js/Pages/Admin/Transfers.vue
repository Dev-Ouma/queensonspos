<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ transfers: Object });
const expanded = ref(null);
const badge = (s) => ({ received: 'bg-emerald-50 text-emerald-700', approved: 'bg-amber-50 text-amber-700', dispatched: 'bg-navy-50 text-navy-700', draft: 'bg-gray-100 text-gray-500' }[s] || 'bg-gray-100');
</script>

<template>
    <Head title="Stock Transfers — Admin" />
    <AdminLayout>
        <h1 class="text-xl font-bold text-navy-700 mb-6">Stock Transfers</h1>

        <div class="space-y-3">
            <div v-for="t in transfers.data" :key="t.id" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-sm">
                        <span class="font-mono font-semibold">{{ t.reference }}</span>
                        <span class="text-gray-400 mx-2">{{ t.from }} → {{ t.to }}</span>
                        <span class="text-xs text-gray-400">{{ t.created }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span :class="['text-xs rounded-full px-2 py-0.5 capitalize', badge(t.status)]">{{ t.status }}</span>
                        <button @click="expanded = expanded === t.id ? null : t.id" class="text-xs text-navy-700 hover:underline">
                            {{ expanded === t.id ? 'Hide' : `${t.items.length} items` }}
                        </button>
                    </div>
                </div>
                <table v-if="expanded === t.id" class="w-full text-sm mt-3 border-t pt-2">
                    <thead><tr class="text-left text-xs text-gray-400 uppercase"><th class="py-2">Item</th><th>SKU</th><th class="text-right">Sent</th><th class="text-right">Received</th></tr></thead>
                    <tbody>
                        <tr v-for="(i, idx) in t.items" :key="idx" class="border-t border-gray-50">
                            <td class="py-1.5">{{ i.product }}</td>
                            <td class="font-mono text-xs">{{ i.sku }}</td>
                            <td class="text-right">{{ i.qty }}</td>
                            <td class="text-right">{{ i.received_qty ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="!transfers.data.length" class="text-gray-400 text-sm">No transfers.</p>
        </div>
        <Pagination :links="transfers.links" />
    </AdminLayout>
</template>
