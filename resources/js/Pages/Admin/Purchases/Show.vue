<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { kes } from '@/money';

const props = defineProps({ po: Object });

const form = useForm({
    supplier_delivery_ref: '',
    items: props.po.items.map(i => ({ purchase_order_item_id: i.id, qty_received: Math.max(0, i.qty_ordered - i.qty_received), qty_damaged: 0 })),
});
function receive() {
    form.post(`/admin/purchases/${props.po.id}/receive`, { preserveScroll: true });
}
const outstanding = props.po.items.some(i => i.qty_received < i.qty_ordered);
</script>

<template>
    <Head :title="`${po.reference} — Admin`" />
    <AdminLayout>
        <Link href="/admin/purchases" class="text-sm text-gray-500 hover:text-navy-700">← Purchase Orders</Link>
        <div class="flex items-center justify-between mt-2 mb-6">
            <h1 class="text-xl font-bold text-navy-700 font-mono">{{ po.reference }}</h1>
            <span class="text-xs bg-navy-50 rounded-full px-3 py-1 capitalize">{{ po.status.replaceAll('_', ' ') }}</span>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-6 text-sm">
            <div class="grid grid-cols-3 gap-4">
                <div><span class="text-xs text-gray-400 block">Supplier</span>{{ po.supplier }}</div>
                <div><span class="text-xs text-gray-400 block">Warehouse</span>{{ po.warehouse }}</div>
                <div><span class="text-xs text-gray-400 block">Total</span>{{ kes(po.total_cents) }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h2 class="font-semibold text-sm mb-3">{{ outstanding ? 'Receive Goods (GRN)' : 'Items — fully received' }}</h2>
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-400 uppercase"><th class="py-2">Item</th><th>Ordered</th><th>Received</th><th>Unit Cost</th><th v-if="outstanding">Receive now</th><th v-if="outstanding">Damaged</th></tr></thead>
                <tbody>
                    <tr v-for="(i, idx) in po.items" :key="i.id" class="border-t border-gray-50">
                        <td class="py-2">{{ i.product }} <span class="text-xs text-gray-400">{{ i.sku }}</span></td>
                        <td>{{ i.qty_ordered }}</td>
                        <td :class="i.qty_received >= i.qty_ordered ? 'text-emerald-700 font-semibold' : ''">{{ i.qty_received }}</td>
                        <td>{{ kes(i.unit_cost_cents) }}</td>
                        <td v-if="outstanding"><input v-model.number="form.items[idx].qty_received" type="number" min="0" :max="i.qty_ordered - i.qty_received" class="rounded border-gray-200 text-xs w-20" /></td>
                        <td v-if="outstanding"><input v-model.number="form.items[idx].qty_damaged" type="number" min="0" class="rounded border-gray-200 text-xs w-20" /></td>
                    </tr>
                </tbody>
            </table>
            <div v-if="outstanding" class="flex items-center gap-3 mt-4">
                <input v-model="form.supplier_delivery_ref" placeholder="Supplier delivery ref" class="rounded border-gray-200 text-sm" />
                <button @click="receive" :disabled="form.processing" class="bg-emerald-600 text-white rounded-lg px-5 py-2 text-sm font-semibold">Receive & Update Stock</button>
            </div>
        </div>
    </AdminLayout>
</template>
