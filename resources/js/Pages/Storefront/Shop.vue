<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { reactive, ref, computed } from 'vue';

const props = defineProps({ products: Object, categories: Array, filters: Object, colorOptions: Array, sizeOptions: Array, searchSaved: Boolean });
const page = usePage();

const f = reactive({ sort: '', ...Object.fromEntries(Object.entries(props.filters).filter(([, v]) => v != null)) });
const view = ref('grid');
const filtersOpen = ref(false);
const LETTER = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
const sizes = computed(() => [...props.sizeOptions].sort((a, b) => {
    const rank = (x) => (LETTER.includes(x) ? 100 + LETTER.indexOf(x) : isNaN(+x) ? 999 : +x);
    return rank(a) - rank(b);
}));

const activeCategory = computed(() => props.categories.find(c => c.slug === f.category));
const heading = computed(() => f.q ? f.q : activeCategory.value?.name || 'Women\'s fashion');
const chips = computed(() => ['category', 'color', 'size', 'min_price', 'max_price'].filter(k => f[k]).map(k => ({
    k, label: k === 'category' ? activeCategory.value?.name || f[k] : k === 'min_price' ? `From KES ${f[k]}` : k === 'max_price' ? `To KES ${f[k]}` : f[k],
})));

function apply() {
    router.get('/shop', Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true, preserveScroll: true });
}
function saveSearch() {
    if (!page.props.auth?.customer) return router.visit('/account/login');
    router.post('/account/saved-searches/toggle', Object.fromEntries(Object.entries(props.filters).filter(([, v]) => v)), { preserveScroll: true });
}
function set(k, v) { f[k] = f[k] === v ? '' : v; apply(); }
function clear(k) { f[k] = ''; apply(); }
function reset() { Object.keys(f).forEach(k => { if (k !== 'q') f[k] = ''; }); apply(); }
</script>

<template>
    <Head :title="`${heading} for sale`" />
    <StorefrontLayout>
        <div class="max-w-7xl mx-auto px-4 py-4">
            <!-- Breadcrumb -->
            <nav class="text-xs text-gray-500 flex gap-1.5" aria-label="Breadcrumb">
                <Link href="/" class="hover:underline">QUEENSONS</Link><span>›</span>
                <Link href="/shop" class="hover:underline">Women's fashion</Link>
                <template v-if="activeCategory"><span>›</span><span class="text-navy-900">{{ activeCategory.name }}</span></template>
            </nav>

            <h1 class="text-2xl font-bold mt-3">{{ heading }}</h1>

            <!-- Category pills -->
            <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                <button v-for="c in categories" :key="c.id" @click="set('category', c.slug)"
                        :class="['whitespace-nowrap rounded-full border px-4 py-1.5 text-sm',
                                 f.category === c.slug ? 'bg-navy-900 text-white border-navy-900' : 'border-gray-300 hover:border-navy-900']">
                    {{ c.name }}
                </button>
            </div>

            <div class="mt-6 flex flex-col lg:flex-row gap-8">
                <!-- Filters -->
                <button @click="filtersOpen = true" class="lg:hidden self-start rounded-full border border-gray-300 px-4 py-1.5 text-sm font-semibold">Filter<span v-if="chips.length"> ({{ chips.length }})</span></button>
                <div v-if="filtersOpen" class="fixed inset-0 bg-black/40 z-40 lg:hidden" @click="filtersOpen = false"></div>
                <aside :class="['text-sm bg-white', filtersOpen ? 'fixed inset-y-0 left-0 w-80 max-w-[85%] z-50 overflow-y-auto p-5 shadow-2xl' : 'hidden', 'lg:block lg:static lg:w-60 lg:shrink-0 lg:p-0 lg:shadow-none']">
                    <div class="flex items-center justify-between mb-3">
                        <div class="font-bold text-base">Filter</div>
                        <button @click="filtersOpen = false" class="lg:hidden text-sm underline">Done</button>
                    </div>

                    <details open class="border-t border-gray-200 py-3">
                        <summary class="font-semibold cursor-pointer">Category</summary>
                        <div class="mt-2 flex flex-col gap-1.5">
                            <button @click="clear('category')" :class="['text-left hover:underline', !f.category && 'font-bold']">All categories</button>
                            <button v-for="c in categories" :key="c.id" @click="set('category', c.slug)"
                                    :class="['text-left hover:underline pl-3', f.category === c.slug && 'font-bold']">{{ c.name }}</button>
                        </div>
                    </details>

                    <details open class="border-t border-gray-200 py-3">
                        <summary class="font-semibold cursor-pointer">Size</summary>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button v-for="s in sizes" :key="s" @click="set('size', s)"
                                    :class="['min-w-10 rounded border px-2 py-1 text-xs', f.size === s ? 'bg-navy-900 text-white border-navy-900' : 'border-gray-300 hover:border-navy-900']">{{ s }}</button>
                        </div>
                    </details>

                    <details open class="border-t border-gray-200 py-3">
                        <summary class="font-semibold cursor-pointer">Colour</summary>
                        <div class="mt-2 flex flex-col gap-1.5">
                            <label v-for="c in colorOptions" :key="c" class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" :checked="f.color === c" @change="set('color', c)" class="rounded border-gray-400 text-emerald-600 focus:ring-emerald-600" />
                                {{ c }}
                            </label>
                        </div>
                    </details>

                    <details open class="border-t border-gray-200 py-3">
                        <summary class="font-semibold cursor-pointer">Price</summary>
                        <div class="mt-2 flex items-center gap-2">
                            <input v-model="f.min_price" type="number" placeholder="KES Min" class="w-full rounded border-gray-300 text-xs" />
                            <span>to</span>
                            <input v-model="f.max_price" type="number" placeholder="KES Max" class="w-full rounded border-gray-300 text-xs" />
                            <button @click="apply" class="rounded-full border border-gray-300 w-8 h-8 shrink-0 hover:border-navy-900" aria-label="Apply price">›</button>
                        </div>
                    </details>

                    <details open class="border-t border-b border-gray-200 py-3">
                        <summary class="font-semibold cursor-pointer">Delivery options</summary>
                        <div class="mt-2 text-gray-600 text-xs leading-relaxed">Free delivery over KES 5,000 in Nairobi. Click &amp; collect at our flagship store.</div>
                    </details>
                </aside>

                <!-- Results -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 pb-3">
                        <div class="flex items-center gap-4">
                            <div class="text-sm"><b>{{ products.total }}</b> results<span v-if="f.q"> for <b>{{ f.q }}</b></span></div>
                            <button @click="saveSearch" :aria-pressed="searchSaved"
                                    :class="['rounded-full border px-3 py-1 text-xs font-semibold flex items-center gap-1.5', searchSaved ? 'border-navy-900 bg-navy-900 text-white' : 'border-gray-300 hover:border-navy-900']">
                                <svg class="w-3.5 h-3.5" :fill="searchSaved ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s-7.5-4.6-9.5-9.2C1 8 3.4 4.5 7 4.5c2 0 3.6 1.1 5 2.8 1.4-1.7 3-2.8 5-2.8 3.6 0 6 3.5 4.5 7.3C19.5 16.4 12 21 12 21z"/></svg>
                                {{ searchSaved ? 'Search saved' : 'Save this search' }}
                            </button>
                        </div>
                        <div class="flex items-center gap-3">
                            <select v-model="f.sort" @change="apply" class="rounded-full border-gray-300 text-sm py-1.5">
                                <option value="">Sort: Newly listed</option>
                                <option value="best_selling">Best selling</option>
                                <option value="price_asc">Price + delivery: lowest first</option>
                                <option value="price_desc">Price + delivery: highest first</option>
                            </select>
                            <div class="flex border border-gray-300 rounded-full overflow-hidden" role="group" aria-label="View">
                                <button @click="view = 'list'" :class="['px-3 py-1.5 text-xs', view === 'list' ? 'bg-navy-900 text-white' : '']">List</button>
                                <button @click="view = 'grid'" :class="['px-3 py-1.5 text-xs', view === 'grid' ? 'bg-navy-900 text-white' : '']">Grid</button>
                            </div>
                        </div>
                    </div>

                    <div v-if="chips.length" class="flex flex-wrap gap-2 mt-3">
                        <button v-for="c in chips" :key="c.k" @click="clear(c.k)" class="rounded-full bg-gray-100 hover:bg-gray-200 px-3 py-1 text-xs">{{ c.label }} ✕</button>
                        <button @click="reset" class="text-xs underline">Clear all</button>
                    </div>

                    <div v-if="products.data.length"
                         :class="view === 'grid' ? 'grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-x-4 gap-y-8 mt-5' : 'mt-2'">
                        <ProductCard v-for="p in products.data" :key="p.id" :product="p" :layout="view" />
                    </div>
                    <div v-else class="text-center py-20">
                        <div class="font-bold text-lg">No exact matches found</div>
                        <p class="text-sm text-gray-500 mt-1">Try fewer filters or a different search.</p>
                        <button @click="reset" class="mt-4 rounded-full border border-navy-900 px-5 py-2 text-sm font-semibold">Clear filters</button>
                    </div>
                    <div class="mt-8"><Pagination :links="products.links" /></div>
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
