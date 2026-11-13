<script setup>
import AccountShell from '@/Components/AccountShell.vue';
import ProductCard from '@/Components/ProductCard.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const items = ref([]);
onMounted(() => {
    try { items.value = JSON.parse(localStorage.getItem('q_recent') || '[]'); } catch { items.value = []; }
});
function clearAll() {
    try { localStorage.removeItem('q_recent'); } catch { /* ignore */ }
    items.value = [];
}
</script>

<template>
    <Head title="Recently viewed" />
    <AccountShell title="Recently viewed">
        <template v-if="items.length">
            <div class="flex justify-between items-center mb-4 text-sm">
                <span class="text-gray-600">{{ items.length }} items on this device</span>
                <button @click="clearAll" class="underline">Clear all</button>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-x-4 gap-y-8">
                <ProductCard v-for="p in items" :key="p.slug" :product="p" />
            </div>
        </template>
        <div v-else class="border border-gray-200 rounded-2xl p-10 text-center">
            <div class="font-bold">Nothing viewed yet</div>
            <p class="text-sm text-gray-500 mt-1">Items you look at will show up here.</p>
            <Link href="/shop" class="inline-block mt-4 bg-emerald-600 text-white rounded-full px-6 py-2.5 text-sm font-semibold">Start shopping</Link>
        </div>
    </AccountShell>
</template>
