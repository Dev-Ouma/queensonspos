<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { kes, img } from '@/money';
defineProps({ items: Array });
</script>

<template>
    <Head title="Wishlist — QUEENSONS" />
    <StorefrontLayout>
        <div class="max-w-5xl mx-auto px-4 py-12">
            <h1 class="font-display text-3xl mb-8">My Wishlist</h1>
            <div v-if="items.length" class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div v-for="w in items" :key="w.id" class="group">
                    <Link :href="`/product/${w.slug}`" class="block aspect-[3/4] bg-navy-50 rounded-lg overflow-hidden">
                        <img v-if="w.image" :src="img(w.image)" class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center font-display text-3xl text-navy-100">Q</div>
                    </Link>
                    <div class="mt-2 text-sm font-medium">{{ w.name }}</div>
                    <div class="text-sm text-navy-700 font-semibold">{{ kes(w.price_cents) }}</div>
                    <button @click="router.post('/account/wishlist/toggle', { product_id: w.id }, { preserveScroll: true })"
                            class="text-xs text-gray-400 hover:text-red-500 underline mt-1">Remove</button>
                </div>
            </div>
            <p v-else class="text-gray-400">Nothing saved yet. <Link href="/shop" class="text-emerald-600 underline">Browse the shop</Link></p>
        </div>
    </StorefrontLayout>
</template>
