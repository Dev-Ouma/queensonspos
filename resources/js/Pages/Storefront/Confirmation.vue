<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { kes } from '@/money';
defineProps({ order: Object });
</script>

<template>
    <Head title="Order Confirmed — QUEENSONS" />
    <StorefrontLayout>
        <div class="max-w-xl mx-auto px-4 py-16 text-center">
            <div class="relative w-20 h-20 mx-auto mb-6">
                <span class="absolute -top-1 -left-3 text-gold animate-fade-up" style="animation-delay:.5s" aria-hidden="true">✦</span>
                <span class="absolute -top-2 right-0 text-gold-light text-xs animate-fade-up" style="animation-delay:.7s" aria-hidden="true">✦</span>
                <span class="absolute bottom-0 -right-4 text-gold text-sm animate-fade-up" style="animation-delay:.9s" aria-hidden="true">✦</span>
                <div class="w-20 h-20 bg-navy-800 rounded-full flex items-center justify-center animate-fade-up">
                    <img src="/logo.svg" alt="" class="w-11 h-11" />
                </div>
            </div>
            <p class="eyebrow text-gold-dark mb-2 animate-fade-up" style="animation-delay:.15s">Order Confirmed</p>
            <h1 class="font-display text-4xl animate-fade-up" style="animation-delay:.25s">Asante sana!</h1>
            <p class="text-gray-500 mt-3 animate-fade-up" style="animation-delay:.35s">Your order has been received and payment confirmed.</p>
            <div class="rule-gold max-w-[120px] mx-auto mt-5"></div>
            <div class="bg-gray-50 rounded-xl p-6 mt-8 text-left">
                <div class="flex justify-between text-sm mb-4">
                    <span class="text-gray-500">Order reference</span>
                    <span class="font-mono font-semibold">{{ order.reference }}</span>
                </div>
                <div v-for="(i, idx) in order.items" :key="idx" class="flex justify-between text-sm py-1">
                    <span>{{ i.qty }}× {{ i.name_snapshot }}</span>
                    <span>{{ kes(i.line_total_cents) }}</span>
                </div>
                <div class="flex justify-between font-bold border-t pt-3 mt-3">
                    <span>Total paid</span><span>{{ kes(order.total_cents) }}</span>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-4">A confirmation has been sent to {{ order.email }}.</p>
            <div class="flex gap-3 justify-center mt-8">
                <Link href="/shop" class="bg-navy-700 text-white rounded-full px-6 py-3 text-sm">Continue shopping</Link>
                <Link href="/account/register" class="border border-gray-300 rounded-full px-6 py-3 text-sm hover:border-navy-700">Create an account</Link>
            </div>
        </div>
    </StorefrontLayout>
</template>
