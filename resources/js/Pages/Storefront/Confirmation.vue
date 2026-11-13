<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { kes } from '@/money';
defineProps({ order: Object });
</script>

<template>
    <Head title="Order confirmed" />
    <StorefrontLayout>
        <div class="max-w-3xl mx-auto px-4 py-10">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-green-600 text-white flex items-center justify-center shrink-0" aria-hidden="true">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold">Thanks, your order is confirmed!</h1>
                    <p class="text-gray-600 mt-1">We sent a confirmation to <b>{{ order.email }}</b>. Asante sana for shopping with QUEENSONS.</p>
                </div>
            </div>

            <div class="border border-gray-200 rounded-2xl mt-8">
                <div class="px-5 py-3 border-b border-gray-200 flex flex-wrap justify-between gap-2 text-sm">
                    <span>Order number <b class="font-mono">{{ order.reference }}</b></span>
                    <span class="text-green-700 font-semibold capitalize">{{ order.status }}</span>
                </div>
                <div class="divide-y divide-gray-200">
                    <div v-for="(i, idx) in order.items" :key="idx" class="px-5 py-3 flex justify-between text-sm">
                        <span>{{ i.name_snapshot }} <span class="text-gray-500">× {{ i.qty }}</span></span>
                        <span class="font-semibold">{{ kes(i.line_total_cents) }}</span>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-gray-200 flex justify-between font-bold text-lg">
                    <span>Total</span><span>{{ kes(order.total_cents) }}</span>
                </div>
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                <Link href="/account/orders" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full px-7 py-3 text-sm font-semibold">View purchases</Link>
                <Link href="/shop" class="border-2 border-navy-900 rounded-full px-7 py-3 text-sm font-semibold">Continue shopping</Link>
            </div>
        </div>
    </StorefrontLayout>
</template>
