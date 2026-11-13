<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { kes } from '@/money';
defineProps({ order: Object });
</script>

<template>
    <Head :title="`Order ${order.reference} — QUEENSONS`" />
    <StorefrontLayout>
        <div class="max-w-3xl mx-auto px-4 py-12">
            <Link href="/account/orders" class="text-sm text-gray-400 hover:text-navy-700">← Back to orders</Link>
            <div class="flex flex-wrap items-center justify-between mt-3 mb-8 gap-2">
                <h1 class="font-display text-2xl font-mono">{{ order.reference }}</h1>
                <span class="text-xs bg-navy-50 rounded-full px-3 py-1 capitalize">{{ order.status.replaceAll('_', ' ') }}</span>
            </div>

            <!-- Tracking timeline -->
            <div class="mb-10">
                <h2 class="font-semibold mb-4 text-sm">Order Tracking</h2>
                <ol class="relative border-l border-gray-200 ml-2">
                    <li v-for="(h, i) in order.history" :key="i" class="mb-5 ml-5">
                        <span class="absolute -left-[7px] w-3.5 h-3.5 rounded-full bg-emerald-600 ring-4 ring-white"></span>
                        <div class="text-sm capitalize font-medium">{{ h.to.replaceAll('_', ' ') }}</div>
                        <div class="text-xs text-gray-400">{{ h.at }} <span v-if="h.note">· {{ h.note }}</span></div>
                    </li>
                </ol>
            </div>

            <div class="border border-gray-100 rounded-xl p-5">
                <div v-for="(i, idx) in order.items" :key="idx" class="flex justify-between text-sm py-1.5">
                    <span>{{ i.qty }}× {{ i.name_snapshot }} <span class="text-gray-400">({{ i.sku_snapshot }})</span></span>
                    <span>{{ kes(i.line_total_cents) }}</span>
                </div>
                <div class="border-t mt-3 pt-3 space-y-1 text-sm">
                    <div class="flex justify-between"><span>Subtotal</span><span>{{ kes(order.subtotal_cents) }}</span></div>
                    <div v-if="order.discount_cents" class="flex justify-between text-emerald-600"><span>Discount</span><span>−{{ kes(order.discount_cents) }}</span></div>
                    <div class="flex justify-between"><span>Delivery</span><span>{{ kes(order.delivery_cents) }}</span></div>
                    <div class="flex justify-between font-bold"><span>Total</span><span>{{ kes(order.total_cents) }}</span></div>
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
