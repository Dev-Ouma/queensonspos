<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { kes } from '@/money';

defineProps({ orders: Object, suppliers: Array, warehouses: Array });

const creating = ref(false);
const form = useForm({ supplier_id: null, warehouse_id: null, items: [] });
const search = ref('');
const results = ref([]);

async function doSearch() {
    if (search.value.length < 2) return;
    const r = await fetch(`/admin/inventory/variant-search?q=${encodeURIComponent(search.value)}`, { headers: { Accept: 'application/json' } });
    results.value = await r.json();
}
function addItem(v) {
    if (!form.items.find(i => i.variant_id === v.id)) {
        form.items.push({ variant_id: v.id, label: v.label, qty: 10, unit_cost: 0 });
    }
    results.value = [];
    search.value = '';
}
function save() {
    form.post('/admin/purchases', { preserveScroll: true, onSuccess: () => { creating.value = false; form.reset(); } });
}
</script>

<template>
    <Head title="Purchase Orders — Admin" />
    <AdminLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-bold text-navy-700">Purchase Orders</h1>
            <button @click="creating = true" class="bg-emerald-600 text-white rounded-lg px-4 py-2 text-sm font-semibold">+ New PO</button>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-400 uppercase border-b"><th class="p-3">Reference</th><th>Supplier</th><th>Warehouse</th><th>Status</th><th class="text-right">Total</th><th>Created</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="po in orders.data" :key="po.id" class="border-b border-gray-50">
                        <td class="p-3 font-mono text-xs font-semibold">{{ po.reference }}</td>
                        <td>{{ po.supplier }}</td>
                        <td class="text-xs">{{ po.warehouse }}</td>
                        <td><span class="text-xs bg-navy-50 rounded-full px-2 py-0.5 capitalize">{{ po.status.replaceAll('_', ' ') }}</span></td>
                        <td class="text-right">{{ kes(po.total_cents) }}</td>
                        <td class="text-xs text-gray-400">{{ po.created }}</td>
                        <td class="text-right pr-3"><Link :href="`/admin/purchases/${po.id}`" class="text-xs text-navy-700 hover:underline">Open</Link></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="orders.links" />

        <!-- Create modal -->
        <div v-if="creating" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="creating = false">
            <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <h2 class="font-semibold mb-4">New Purchase Order</h2>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <select v-model="form.supplier_id" class="rounded border-gray-200 text-sm">
                        <option :value="null">Select supplier…</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <select v-model="form.warehouse_id" class="rounded border-gray-200 text-sm">
                        <option :value="null">Select warehouse…</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                </div>

                <div class="relative mb-4">
                    <input v-model="search" @input="doSearch" placeholder="Search variants to add…" class="w-full rounded border-gray-200 text-sm" />
                    <div v-if="results.length" class="absolute z-10 bg-white border rounded-lg shadow-lg w-full mt-1 max-h-48 overflow-y-auto">
                        <button v-for="r in results" :key="r.id" @click="addItem(r)" type="button" class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-50">
                            {{ r.label }}
                        </button>
                    </div>
                </div>

                <table v-if="form.items.length" class="w-full text-sm mb-4">
                    <thead><tr class="text-left text-xs text-gray-400"><th>Item</th><th class="w-20">Qty</th><th class="w-28">Unit cost KES</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="(i, idx) in form.items" :key="i.variant_id" class="border-t border-gray-50">
                            <td class="py-1 text-xs">{{ i.label }}</td>
                            <td><input v-model.number="i.qty" type="number" min="1" class="rounded border-gray-200 text-xs w-16" /></td>
                            <td><input v-model.number="i.unit_cost" type="number" step="0.01" class="rounded border-gray-200 text-xs w-24" /></td>
                            <td><button type="button" @click="form.items.splice(idx, 1)" class="text-red-400 text-xs">✕</button></td>
                        </tr>
                    </tbody>
                </table>

                <div class="flex justify-end gap-2">
                    <button @click="creating = false" class="text-gray-500 px-4 py-2 text-sm">Cancel</button>
                    <button @click="save" :disabled="!form.supplier_id || !form.warehouse_id || !form.items.length || form.processing"
                            class="bg-navy-700 text-white rounded-lg px-4 py-2 text-sm font-semibold disabled:opacity-40">Create & Approve</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
