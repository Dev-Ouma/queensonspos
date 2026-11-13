<script setup>
import AccountShell from '@/Components/AccountShell.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link } from '@inertiajs/vue3';
import { kes } from '@/money';
defineProps({ orders: Object });
</script>

<template>
    <Head title="Purchases" />
    <AccountShell title="Purchases">
        <div v-if="orders.data.length" class="space-y-4">
            <div v-for="o in orders.data" :key="o.reference" class="border border-gray-200 rounded-2xl">
                <div class="bg-gray-50 rounded-t-2xl px-5 py-3 flex flex-wrap gap-x-8 gap-y-1 text-xs text-gray-600">
                    <span>Order date<br /><b class="text-navy-900 text-sm">{{ o.placed_at }}</b></span>
                    <span>Order total<br /><b class="text-navy-900 text-sm">{{ kes(o.total_cents) }}</b></span>
                    <span>Order number<br /><b class="text-navy-900 text-sm font-mono">{{ o.reference }}</b></span>
                </div>
                <div class="p-5 flex flex-wrap items-center justify-between gap-4">
                    <div class="text-sm">
                        <div class="font-bold capitalize text-green-700">{{ o.status.replaceAll('_', ' ') }}</div>
                        <div v-for="(i, k) in o.items" :key="k" class="mt-1">{{ i.name_snapshot }} <span class="text-gray-500">× {{ i.qty }}</span></div>
                    </div>
                    <Link :href="`/account/orders/${o.reference}`" class="border-2 border-navy-900 rounded-full px-5 py-2 text-sm font-semibold">View order details</Link>
                </div>
            </div>
        </div>
        <p v-else class="text-sm text-gray-500">You haven't bought anything yet. <Link href="/shop" class="underline">Start shopping</Link></p>
        <div class="mt-6"><Pagination :links="orders.links" /></div>
    </AccountShell>
</template>
