<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({ balances: Object, warehouses: Array, filters: Object });
const f = reactive({ ...props.filters });
const apply = () => router.get('/admin/inventory', Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true });

const adjusting = ref(null);
const adjForm = useForm({ variant_id: null, warehouse_id: null, qty_delta: 0, reason: '' });

function openAdjust(b) {
    adjusting.value = b;
    adjForm.variant_id = b.variant_id;
    adjForm.warehouse_id = b.warehouse_id;
    adjForm.qty_delta = 0;
    adjForm.reason = '';
}
function submitAdjust() {
    adjForm.post('/admin/inventory/adjust', { preserveScroll: true, onSuccess: () => (adjusting.value = null) });
}
</script>

<template>
    <Head title="Inventory — Admin" />
    <AdminLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-bold text-navy-700">Inventory</h1>
            <Link href="/admin/inventory/ledger" class="text-sm text-emerald-600 hover:underline">View stock ledger →</Link>
        </div>

        <div class="flex flex-wrap gap-3 mb-4">
            <input v-model="f.q" @keyup.enter="apply" placeholder="Search product or SKU…" class="rounded-lg border-gray-200 text-sm" />
            <select v-model="f.warehouse" @change="apply" class="rounded-lg border-gray-200 text-sm">
                <option value="">All warehouses</option>
                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
            </select>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="f.low_only" @change="apply" true-value="1" false-value="" class="rounded text-emerald-600" /> Low stock only
            </label>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-400 uppercase border-b">
                        <th class="p-3">Product</th><th>SKU</th><th>Variant</th><th>Warehouse</th>
                        <th class="text-right">On Hand</th><th class="text-right">Reserved</th><th class="text-right">Damaged</th><th class="text-right">Available</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="b in balances.data" :key="b.id" class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="p-3">{{ b.product }}</td>
                        <td class="font-mono text-xs">{{ b.sku }}</td>
                        <td class="text-xs text-gray-500">{{ b.color }} / {{ b.size }}</td>
                        <td class="text-xs">{{ b.warehouse }}</td>
                        <td class="text-right">{{ b.on_hand }}</td>
                        <td class="text-right text-amber-600">{{ b.reserved }}</td>
                        <td class="text-right text-red-500">{{ b.damaged }}</td>
                        <td :class="['text-right font-bold', b.available <= 0 ? 'text-red-600' : b.available <= 5 ? 'text-amber-600' : 'text-emerald-700']">{{ b.available }}</td>
                        <td class="text-right pr-3">
                            <button @click="openAdjust(b)" class="text-xs text-navy-700 hover:underline">Adjust</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="balances.links" />

        <!-- Adjust modal -->
        <div v-if="adjusting" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="adjusting = null">
            <div class="bg-white rounded-xl p-6 w-full max-w-md">
                <h2 class="font-semibold mb-1">Adjust Stock</h2>
                <p class="text-xs text-gray-400 mb-4">{{ adjusting.product }} · {{ adjusting.sku }} · {{ adjusting.warehouse }}</p>
                <form @submit.prevent="submitAdjust" class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-500">Quantity change (negative removes)</label>
                        <input v-model.number="adjForm.qty_delta" type="number" required class="w-full rounded border-gray-200 text-sm mt-1" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Reason *</label>
                        <input v-model="adjForm.reason" required placeholder="e.g. Stock count correction" class="w-full rounded border-gray-200 text-sm mt-1" />
                    </div>
                    <div class="flex gap-2 justify-end">
                        <button type="button" @click="adjusting = null" class="text-sm text-gray-500 px-4 py-2">Cancel</button>
                        <button :disabled="adjForm.processing || !adjForm.qty_delta" class="bg-navy-700 text-white rounded-lg px-4 py-2 text-sm">Record Adjustment</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
