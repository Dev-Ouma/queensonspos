<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link } from '@inertiajs/vue3';
import { kes } from '@/money';
defineProps({ orders: Object });
</script>

<template>
    <Head title="My Orders — QUEENSONS" />
    <StorefrontLayout>
        <div class="max-w-4xl mx-auto px-4 py-12">
            <h1 class="font-display text-3xl mb-8">My Orders</h1>
            <div v-if="orders.data.length" class="space-y-4">
                <Link v-for="o in orders.data" :key="o.reference" :href="`/account/orders/${o.reference}`"
                      class="block border border-gray-100 rounded-xl p-5 hover:border-navy-700 transition">
                    <div class="flex flex-wrap justify-between gap-2 text-sm">
                        <div>
                            <span class="font-mono font-semibold">{{ o.reference }}</span>
                            <span class="text-gray-400 ml-3">{{ o.placed_at }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs bg-navy-50 rounded-full px-3 py-1 capitalize">{{ o.status.replaceAll('_', ' ') }}</span>
                            <span class="font-semibold">{{ kes(o.total_cents) }}</span>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">
                        {{ o.items.map(i => `${i.qty}× ${i.name_snapshot}`).join(' · ') }}
                    </div>
                </Link>
            </div>
            <p v-else class="text-gray-400">No orders yet.</p>
            <Pagination :links="orders.links" />
        </div>
    </StorefrontLayout>
</template>
