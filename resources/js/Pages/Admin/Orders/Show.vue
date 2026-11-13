<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { kes } from '@/money';

const props = defineProps({ order: Object });
const form = useForm({ to: '', note: '' });
function move() {
    if (!form.to) return;
    form.post(`/admin/orders/${props.order.id}/transition`, { preserveScroll: true, onSuccess: () => form.reset() });
}
</script>

<template>
    <Head :title="`Order ${order.reference} — Admin`" />
    <AdminLayout>
        <Link href="/admin/orders" class="text-sm text-gray-500 hover:text-navy-700">← Orders</Link>
        <div class="flex flex-wrap items-center justify-between mt-2 mb-6 gap-3">
            <h1 class="text-xl font-bold text-navy-700 font-mono">{{ order.reference }}</h1>
            <div class="flex items-center gap-2">
                <span class="text-xs bg-navy-50 rounded-full px-3 py-1 capitalize">{{ order.status.replaceAll('_', ' ') }}</span>
                <span :class="['text-xs rounded-full px-3 py-1', order.payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700']">{{ order.payment_status }}</span>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Items -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h2 class="font-semibold text-sm mb-3">Items</h2>
                    <table class="w-full text-sm">
                        <tbody>
                            <tr v-for="(i, idx) in order.items" :key="idx" class="border-b border-gray-50">
                                <td class="py-2">{{ i.name_snapshot }} <span class="text-xs text-gray-400">{{ i.sku_snapshot }}</span></td>
                                <td class="text-center">× {{ i.qty }}</td>
                                <td class="text-right">{{ kes(i.line_total_cents) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="text-sm space-y-1 mt-3 ml-auto max-w-xs">
                        <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>{{ kes(order.subtotal_cents) }}</span></div>
                        <div v-if="order.discount_cents" class="flex justify-between text-emerald-600"><span>Discount</span><span>−{{ kes(order.discount_cents) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Delivery</span><span>{{ kes(order.delivery_cents) }}</span></div>
                        <div class="flex justify-between font-bold border-t pt-1"><span>Total</span><span>{{ kes(order.total_cents) }}</span></div>
                    </div>
                </div>

                <!-- Payments -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h2 class="font-semibold text-sm mb-3">Payments</h2>
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-xs text-gray-400 uppercase"><th>Ref</th><th>Method</th><th>Provider Txn</th><th>Status</th><th class="text-right">Amount</th></tr></thead>
                        <tbody>
                            <tr v-for="p in order.payments" :key="p.reference" class="border-t border-gray-50">
                                <td class="py-2 font-mono text-[11px]">{{ p.reference.slice(-10) }}</td>
                                <td class="uppercase text-xs">{{ p.method }}</td>
                                <td class="font-mono text-[11px]">{{ p.provider_txn_id }}</td>
                                <td><span class="text-xs bg-emerald-50 text-emerald-700 rounded px-2 py-0.5">{{ p.status }}</span></td>
                                <td class="text-right">{{ kes(p.amount_cents) }}</td>
                            </tr>
                            <tr v-if="!order.payments.length"><td colspan="5" class="py-3 text-gray-400">No payments recorded.</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- History -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h2 class="font-semibold text-sm mb-3">Status History</h2>
                    <ol class="relative border-l border-gray-200 ml-2">
                        <li v-for="(h, i) in order.history" :key="i" class="mb-4 ml-5">
                            <span class="absolute -left-[7px] w-3.5 h-3.5 rounded-full bg-navy-700 ring-4 ring-white"></span>
                            <div class="text-sm capitalize">{{ (h.from ? h.from.replaceAll('_',' ') + ' → ' : '') + h.to.replaceAll('_', ' ') }}</div>
                            <div class="text-xs text-gray-400">{{ h.at }} <span v-if="h.note">· {{ h.note }}</span></div>
                        </li>
                    </ol>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Customer -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-sm">
                    <h2 class="font-semibold mb-3">Customer</h2>
                    <div>{{ order.customer }}</div>
                    <div class="text-gray-500">{{ order.email }}</div>
                    <div class="text-gray-500">{{ order.phone }}</div>
                    <div class="mt-3 pt-3 border-t text-xs text-gray-500">
                        <div class="font-semibold text-gray-700 mb-1 capitalize">{{ order.delivery_method }}</div>
                        <template v-if="order.delivery_address">
                            {{ order.delivery_address.line1 }}, {{ order.delivery_address.city }}
                        </template>
                        <template v-else>Store pickup — Flagship</template>
                    </div>
                </div>

                <!-- Transition -->
                <div v-if="order.allowed_transitions.length" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h2 class="font-semibold text-sm mb-3">Move Order Forward</h2>
                    <select v-model="form.to" class="w-full rounded border-gray-200 text-sm mb-2">
                        <option value="">Select next status…</option>
                        <option v-for="t in order.allowed_transitions" :key="t" :value="t">{{ t.replaceAll('_', ' ') }}</option>
                    </select>
                    <input v-model="form.note" placeholder="Note (optional)" class="w-full rounded border-gray-200 text-sm mb-3" />
                    <button @click="move" :disabled="!form.to || form.processing" class="w-full bg-navy-700 text-white rounded-lg py-2 text-sm font-semibold disabled:opacity-40">Update Status</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
