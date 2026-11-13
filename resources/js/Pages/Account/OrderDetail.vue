<script setup>
import AccountShell from '@/Components/AccountShell.vue';
import { Head, Link } from '@inertiajs/vue3';
import { kes } from '@/money';
defineProps({ order: Object });
</script>

<template>
    <Head :title="`Order ${order.reference}`" />
    <AccountShell>
        <Link href="/account/orders" class="text-sm underline">← Back to purchases</Link>
        <div class="flex flex-wrap items-center justify-between gap-2 mt-4">
            <h2 class="text-xl font-bold">Order <span class="font-mono">{{ order.reference }}</span></h2>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold capitalize">{{ order.status.replaceAll('_', ' ') }}</span>
        </div>

        <div class="mt-5 grid lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 border border-gray-200 rounded-2xl divide-y divide-gray-200">
                <div v-for="(i, idx) in order.items" :key="idx" class="p-4 flex justify-between gap-4 text-sm">
                    <div>{{ i.name_snapshot }}<div class="text-xs text-gray-500">SKU {{ i.sku_snapshot }} · Qty {{ i.qty }}</div></div>
                    <div class="font-bold">{{ kes(i.line_total_cents) }}</div>
                </div>
                <div class="p-4 space-y-1.5 text-sm">
                    <div class="flex justify-between"><span>Subtotal</span><span>{{ kes(order.subtotal_cents) }}</span></div>
                    <div v-if="order.discount_cents" class="flex justify-between text-green-700"><span>Discount</span><span>−{{ kes(order.discount_cents) }}</span></div>
                    <div class="flex justify-between"><span>Delivery</span><span>{{ order.delivery_cents ? kes(order.delivery_cents) : 'Free' }}</span></div>
                    <div class="flex justify-between font-bold text-base pt-2"><span>Order total</span><span>{{ kes(order.total_cents) }}</span></div>
                </div>
            </div>
            <div class="border border-gray-200 rounded-2xl p-5">
                <h3 class="font-bold">Tracking</h3>
                <ol class="relative border-l-2 border-gray-200 ml-2 mt-4">
                    <li v-for="(h, i) in order.history" :key="i" class="mb-5 ml-5 last:mb-0">
                        <span class="absolute -left-[9px] w-4 h-4 rounded-full ring-4 ring-white" :class="i === order.history.length - 1 ? 'bg-green-600' : 'bg-gray-400'"></span>
                        <div class="text-sm font-semibold capitalize">{{ h.to.replaceAll('_', ' ') }}</div>
                        <div class="text-xs text-gray-500">{{ h.at }}<span v-if="h.note"> · {{ h.note }}</span></div>
                    </li>
                </ol>
                <p v-if="!order.history.length" class="text-sm text-gray-500 mt-2">Tracking updates will show here.</p>
            </div>
        </div>
    </AccountShell>
</template>
