<script setup>
import AccountShell from '@/Components/AccountShell.vue';
import { Head, Link } from '@inertiajs/vue3';
import { kes } from '@/money';

defineProps({ customer: Object, recentOrders: Array, wishlistCount: Number });
</script>

<template>
    <Head title="My QUEENSONS" />
    <AccountShell title="Summary">
                <div class="grid sm:grid-cols-3 gap-4">
            <Link href="/account/orders" class="border border-gray-200 rounded-2xl p-5 hover:border-navy-900">
                <div class="text-sm text-gray-500">Purchases</div>
                <div class="text-2xl font-bold mt-1">{{ recentOrders.length }}<span class="text-sm font-normal text-gray-500"> recent</span></div>
            </Link>
            <Link href="/account/wishlist" class="border border-gray-200 rounded-2xl p-5 hover:border-navy-900">
                <div class="text-sm text-gray-500">Watchlist</div>
                <div class="text-2xl font-bold mt-1">{{ wishlistCount }}<span class="text-sm font-normal text-gray-500"> items</span></div>
            </Link>
            <div class="border border-gray-200 rounded-2xl p-5">
                <div class="text-sm text-gray-500">Loyalty points</div>
                <div class="text-2xl font-bold mt-1 text-gold-dark">{{ customer.loyalty_points }}</div>
            </div>
        </div>

        <div class="mt-10 flex items-end justify-between">
            <h2 class="text-xl font-bold">Recent purchases</h2>
            <Link href="/account/orders" class="text-sm underline">See all</Link>
        </div>
        <div v-if="recentOrders.length" class="mt-3 border border-gray-200 rounded-2xl divide-y divide-gray-200">
            <Link v-for="o in recentOrders" :key="o.reference" :href="`/account/orders/${o.reference}`"
                  class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm hover:bg-gray-50">
                <div>
                    <div class="font-bold">Order {{ o.reference }}</div>
                    <div class="text-xs text-gray-500">Placed {{ o.placed_at }}</div>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold capitalize">{{ o.status.replaceAll('_', ' ') }}</span>
                <span class="font-bold">{{ kes(o.total_cents) }}</span>
            </Link>
        </div>
        <p v-else class="mt-3 text-sm text-gray-500">No purchases yet. <Link href="/shop" class="underline">Start shopping</Link></p>

        <div class="mt-10 border border-gray-200 rounded-2xl p-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="font-bold">Account settings</div>
                <div class="text-sm text-gray-500">Personal info, addresses, and sign in and security.</div>
            </div>
            <Link href="/account/settings" class="border-2 border-navy-900 rounded-full px-5 py-2 text-sm font-semibold">Manage account</Link>
        </div>
    </AccountShell>
</template>
