<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({ products: Object, categories: Array, filters: Object, colorOptions: Array, sizeOptions: Array });

const f = reactive({ sort: '', ...Object.fromEntries(Object.entries(props.filters).filter(([, v]) => v != null)) });

function apply() {
    router.get('/shop', Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true, preserveScroll: true });
}
function reset() {
    Object.keys(f).forEach(k => (f[k] = ''));
    apply();
}
</script>

<template>
    <Head title="Shop — QUEENSONS" />
    <StorefrontLayout>
        <!-- Branded page band -->
        <div class="bg-navy-900 text-white relative overflow-hidden">
            <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-gold/10 blur-3xl"></div>
            <div class="relative max-w-7xl mx-auto px-4 py-12">
                <p class="eyebrow text-gold mb-2">The Collection</p>
                <h1 class="font-display text-4xl">{{ f.q ? `Results for “${f.q}”` : 'Shop All' }}</h1>
                <p class="text-navy-100/80 text-sm mt-2">{{ products.total }} pieces · hand-checked before dispatch</p>
            </div>
            <div class="absolute bottom-0 inset-x-0 rule-gold"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-10 flex flex-col lg:flex-row gap-8">
            <!-- Filters -->
            <aside class="lg:w-60 shrink-0 space-y-6 text-sm">
                <div>
                    <div class="font-semibold mb-2">Category</div>
                    <select v-model="f.category" @change="apply" class="w-full rounded border-gray-200 text-sm">
                        <option value="">All</option>
                        <option v-for="c in categories" :key="c.id" :value="c.slug">{{ c.name }}</option>
                    </select>
                </div>
                <div>
                    <div class="font-semibold mb-2">Colour</div>
                    <select v-model="f.color" @change="apply" class="w-full rounded border-gray-200 text-sm">
                        <option value="">All</option>
                        <option v-for="c in colorOptions" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>
                <div>
                    <div class="font-semibold mb-2">Size</div>
                    <select v-model="f.size" @change="apply" class="w-full rounded border-gray-200 text-sm">
                        <option value="">All</option>
                        <option v-for="s in sizeOptions" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>
                <div>
                    <div class="font-semibold mb-2">Price (KES)</div>
                    <div class="flex gap-2">
                        <input v-model="f.min_price" @change="apply" type="number" placeholder="Min" class="w-full rounded border-gray-200 text-sm" />
                        <input v-model="f.max_price" @change="apply" type="number" placeholder="Max" class="w-full rounded border-gray-200 text-sm" />
                    </div>
                </div>
                <button @click="reset" class="text-xs text-gray-400 hover:text-emerald-600 underline">Clear filters</button>
            </aside>

            <!-- Grid -->
            <div class="flex-1">
                <div class="flex items-center justify-end mb-6">
                    <select v-model="f.sort" @change="apply" class="rounded border-gray-200 text-sm">
                        <option value="">Newest</option>
                        <option value="price_asc">Price: low to high</option>
                        <option value="price_desc">Price: high to low</option>
                    </select>
                </div>
                <div v-if="products.data.length" class="grid grid-cols-2 md:grid-cols-3 gap-6">
                    <div v-for="(p, pi) in products.data" :key="p.id" v-reveal="(pi % 3) * 80">
                        <ProductCard :product="p" />
                    </div>
                </div>
                <div v-else class="text-center py-20 text-gray-400">No products match your filters.</div>
                <Pagination :links="products.links" />
            </div>
        </div>
    </StorefrontLayout>
</template>
