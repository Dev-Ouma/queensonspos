<script setup>
import AccountShell from '@/Components/AccountShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { kes, img, fallbackImage } from '@/money';
defineProps({ items: Array });
</script>

<template>
    <Head title="Watchlist" />
    <AccountShell :title="`Watchlist (${items.length})`">
        <div v-if="items.length" class="border border-gray-200 rounded-2xl divide-y divide-gray-200">
            <div v-for="w in items" :key="w.id" class="p-4 flex gap-4 items-center">
                <Link :href="`/product/${w.slug}`" class="w-24 h-24 rounded-xl overflow-hidden bg-gray-100 shrink-0">
                    <img :src="img(w.image) || fallbackImage(w.id)" :alt="w.name" class="w-full h-full object-cover" />
                </Link>
                <div class="flex-1 min-w-0">
                    <Link :href="`/product/${w.slug}`" class="hover:underline">{{ w.name }}</Link>
                    <div class="text-lg font-bold mt-1">{{ kes(w.price_cents) }}</div>
                    <div class="text-xs text-gray-500">Brand new · Buy it now</div>
                </div>
                <div class="flex flex-col gap-2 shrink-0">
                    <Link :href="`/product/${w.slug}`" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full px-5 py-2 text-sm font-semibold text-center">Buy it now</Link>
                    <button @click="router.post('/account/wishlist/toggle', { product_id: w.id }, { preserveScroll: true })"
                            class="border-2 border-gray-300 hover:border-navy-900 rounded-full px-5 py-2 text-sm font-semibold">Remove</button>
                </div>
            </div>
        </div>
        <div v-else class="border border-gray-200 rounded-2xl p-10 text-center">
            <div class="font-bold">Your watchlist is empty</div>
            <p class="text-sm text-gray-500 mt-1">Tap the heart on any item to keep track of it here.</p>
            <Link href="/shop" class="inline-block mt-4 bg-emerald-600 text-white rounded-full px-6 py-2.5 text-sm font-semibold">Start shopping</Link>
        </div>
    </AccountShell>
</template>
