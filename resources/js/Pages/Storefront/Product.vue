<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { kes, img, fallbackImage } from '@/money';

const props = defineProps({ product: Object, variants: Array, related: Array });
const page = usePage();
const customer = computed(() => page.props.auth?.customer);
const freeAt = computed(() => page.props.storefront?.free_delivery_threshold_cents ?? 500000);

/* Gallery */
const photos = computed(() => props.product.images.length
    ? props.product.images.map(i => ({ src: img(i.path), alt: i.alt || props.product.name }))
    : [{ src: fallbackImage(props.product.id, 1000), alt: props.product.name }]);
const active = ref(0);
const zoomOrigin = ref('50% 50%');
function onZoom(e) {
    const r = e.currentTarget.getBoundingClientRect();
    zoomOrigin.value = `${((e.clientX - r.left) / r.width) * 100}% ${((e.clientY - r.top) / r.height) * 100}%`;
}

/* Variants */
const colors = computed(() => [...new Set(props.variants.map(v => v.color).filter(Boolean))]);
const selectedColor = ref(colors.value[0] || null);
const sizes = computed(() => props.variants.filter(v => !selectedColor.value || v.color === selectedColor.value));
const selectedVariantId = ref(sizes.value.length === 1 && sizes.value[0].available > 0 ? sizes.value[0].id : null);
const selected = computed(() => props.variants.find(v => v.id === selectedVariantId.value));
const cheapest = computed(() => props.variants.reduce((a, v) => (!a || v.price_cents < a.price_cents ? v : a), null));
const priceVariant = computed(() => selected.value || cheapest.value);
const displayPrice = computed(() => priceVariant.value?.price_cents ?? 0);
const compareAt = computed(() => priceVariant.value?.compare_at_cents);
const discount = computed(() => (compareAt.value ? Math.round((1 - displayPrice.value / compareAt.value) * 100) : 0));
const totalAvailable = computed(() => props.variants.reduce((a, v) => a + v.available, 0));
const available = computed(() => (selected.value ? selected.value.available : totalAvailable.value));

const form = useForm({ variant_id: null, qty: 1 });
const sizeError = ref(false);

function add(thenCheckout = false) {
    if (!selectedVariantId.value) { sizeError.value = true; return; }
    form.variant_id = selectedVariantId.value;
    form.post('/cart/add', {
        preserveScroll: true,
        onSuccess: () => { if (thenCheckout) router.visit('/checkout'); },
    });
}

function watch() {
    if (!customer.value) return router.visit('/account/login');
    router.post('/account/wishlist/toggle', { product_id: props.product.id }, { preserveScroll: true });
}

/* Delivery estimate */
const eta = computed(() => {
    const d = new Date();
    const add = (n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x.toLocaleDateString('en-KE', { weekday: 'short', day: 'numeric', month: 'short' }); };
    return { nairobi: add(1), country: `${add(2)} – ${add(4)}` };
});

/* Reviews */
const avgRating = computed(() => {
    const r = props.product.reviews;
    return r.length ? r.reduce((a, b) => a + b.rating, 0) / r.length : 0;
});
const ratingBars = computed(() => [5, 4, 3, 2, 1].map(n => ({
    n, count: props.product.reviews.filter(r => r.rating === n).length,
})));

/* Tabs */
const tab = ref('specifics');
const specifics = computed(() => [
    ['Condition', 'New with tags'],
    ['Brand', props.product.brand || 'QUEENSONS'],
    ['Department', 'Women'],
    ['Category', props.product.categories.map(c => c.name).join(', ') || '—'],
    ['Colours', colors.value.join(', ') || '—'],
    ['Sizes', [...new Set(props.variants.map(v => v.size).filter(Boolean))].join(', ') || '—'],
    ['SKU', priceVariant.value?.sku || '—'],
    ['Care', props.product.care_instructions || 'See label'],
]);

/* Size guide */
const sizeGuide = ref(false);
const sizeRows = [
    ['S', '84–88', '64–68', '90–94'],
    ['M', '88–92', '68–72', '94–98'],
    ['L', '92–98', '72–78', '98–104'],
    ['XL', '98–106', '78–86', '104–112'],
];

const shareUrl = computed(() => {
    const text = `Check out ${props.product.name} at QUEENSONS — ${typeof window !== 'undefined' ? window.location.href : ''}`;
    return `https://wa.me/?text=${encodeURIComponent(text)}`;
});

/* Recently viewed (localStorage, read by Home and here) */
const recent = ref([]);
onBeforeUnmount(() => document.body.classList.remove('has-buybar'));
onMounted(() => {
    document.body.classList.add('has-buybar');
    try {
        const key = 'q_recent';
        const list = JSON.parse(localStorage.getItem(key) || '[]');
        recent.value = list.filter(r => r.slug !== props.product.slug).slice(0, 12);
        const entry = {
            id: props.product.id,
            slug: props.product.slug,
            name: props.product.name,
            price_cents: cheapest.value?.price_cents,
            compare_at_cents: cheapest.value?.compare_at_cents,
            image: props.product.images[0]?.path || null,
            in_stock: totalAvailable.value > 0,
        };
        localStorage.setItem(key, JSON.stringify([entry, ...list.filter(r => r.slug !== entry.slug)].slice(0, 12)));
    } catch { /* storage unavailable */ }
});
</script>

<template>
    <Head :title="product.name">
        <meta name="description" :content="(product.description || '').slice(0, 160)" />
        <meta property="og:title" :content="`${product.name} — QUEENSONS`" />
        <meta property="og:description" :content="(product.description || '').slice(0, 160)" />
        <meta property="og:type" content="product" />
        <meta property="og:image" :content="photos[0].src" />
    </Head>
    <StorefrontLayout>
        <div class="max-w-7xl mx-auto px-4 py-4 pb-24 lg:pb-4">
            <!-- Breadcrumb -->
            <nav class="text-xs text-gray-500 flex flex-wrap gap-1.5" aria-label="Breadcrumb">
                <Link href="/" class="hover:underline">QUEENSONS</Link><span>›</span>
                <Link href="/shop" class="hover:underline">Women's fashion</Link>
                <template v-if="product.categories[0]"><span>›</span>
                    <Link :href="`/shop?category=${product.categories[0].slug}`" class="hover:underline">{{ product.categories[0].name }}</Link>
                </template>
            </nav>

            <div class="mt-4 grid lg:grid-cols-12 gap-8">
                <!-- Gallery -->
                <div class="lg:col-span-7 flex flex-col-reverse md:flex-row gap-3">
                    <div class="flex md:flex-col gap-2 overflow-x-auto md:w-20 shrink-0">
                        <button v-for="(ph, i) in photos" :key="i" @click="active = i" @mouseenter="active = i"
                                :class="['w-16 h-16 md:w-20 md:h-20 rounded-lg overflow-hidden border-2 shrink-0',
                                         active === i ? 'border-navy-900' : 'border-transparent']">
                            <img :src="ph.src" :alt="ph.alt" class="w-full h-full object-cover" />
                        </button>
                    </div>
                    <div class="flex-1 relative bg-gray-100 rounded-2xl overflow-hidden aspect-square cursor-zoom-in group"
                         @mousemove="onZoom" @mouseleave="zoomOrigin = '50% 50%'">
                        <img :src="photos[active].src" :alt="photos[active].alt"
                             class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-150"
                             :style="{ transformOrigin: zoomOrigin }" />
                        <button @click="watch" class="absolute top-3 right-3 bg-white rounded-full shadow px-3 py-1.5 text-xs font-semibold flex items-center gap-1.5"
                                :aria-label="product.watched ? 'Remove from watchlist' : 'Add to watchlist'">
                            <svg class="w-4 h-4" :fill="product.watched ? 'currentColor' : 'none'" :class="product.watched && 'text-gold-dark'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s-7.5-4.6-9.5-9.2C1 8 3.4 4.5 7 4.5c2 0 3.6 1.1 5 2.8 1.4-1.7 3-2.8 5-2.8 3.6 0 6 3.5 4.5 7.3C19.5 16.4 12 21 12 21z"/></svg>
                            {{ product.watchers }}
                        </button>
                        <span v-if="product.sold >= 5" class="absolute top-3 left-3 bg-red-600 text-white text-xs font-bold rounded-full px-3 py-1">Popular · {{ product.sold }} sold</span>
                    </div>
                </div>

                <!-- Buy box -->
                <div class="lg:col-span-5">
                    <h1 class="text-xl md:text-2xl font-bold leading-snug">{{ product.name }}</h1>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-sm">
                        <span v-if="product.brand" class="text-gray-600">Brand: <b class="text-navy-900">{{ product.brand }}</b></span>
                        <a v-if="product.reviews.length" href="#reviews" class="flex items-center gap-1 hover:underline">
                            <span class="text-gold-dark">{{ '★'.repeat(Math.round(avgRating)) }}{{ '☆'.repeat(5 - Math.round(avgRating)) }}</span>
                            <span class="text-gray-600">{{ product.reviews.length }} reviews</span>
                        </a>
                        <span v-if="product.sold" class="font-bold">{{ product.sold }} sold</span>
                    </div>

                    <div class="border-t border-gray-200 mt-4 pt-4">
                        <div class="text-3xl font-bold">{{ kes(displayPrice) }}</div>
                        <div v-if="discount" class="text-sm text-gray-500 mt-0.5">
                            Was <span class="line-through">{{ kes(compareAt) }}</span>
                            <span class="text-red-600 font-semibold ml-1">Save {{ discount }}%</span>
                        </div>
                        <div class="text-sm text-gray-600 mt-1">Condition: <b class="text-navy-900">New with tags</b></div>
                    </div>

                    <!-- Colour -->
                    <div v-if="colors.length" class="mt-5">
                        <div class="text-sm mb-2">Colour: <b>{{ selectedColor }}</b></div>
                        <div class="flex gap-2 flex-wrap">
                            <button v-for="c in colors" :key="c" @click="selectedColor = c; selectedVariantId = null"
                                    :class="['px-4 py-2 text-sm rounded-full border',
                                             selectedColor === c ? 'border-navy-900 ring-1 ring-navy-900 font-semibold' : 'border-gray-300 hover:border-navy-900']">
                                {{ c }}
                            </button>
                        </div>
                    </div>

                    <!-- Size -->
                    <div class="mt-5">
                        <div class="flex items-center justify-between mb-2 text-sm">
                            <div>Size: <b>{{ selected?.size || 'Select' }}</b></div>
                            <button type="button" @click="sizeGuide = true" class="underline">Size guide</button>
                        </div>
                        <div class="flex gap-2 flex-wrap">
                            <button v-for="v in sizes" :key="v.id" @click="selectedVariantId = v.id; sizeError = false" :disabled="v.available < 1"
                                    :class="['min-w-[3rem] px-3 py-2 text-sm rounded-lg border',
                                             v.available < 1 ? 'border-gray-200 text-gray-300 line-through cursor-not-allowed'
                                                 : selectedVariantId === v.id ? 'border-navy-900 ring-1 ring-navy-900 font-semibold' : 'border-gray-300 hover:border-navy-900']">
                                {{ v.size }}
                            </button>
                        </div>
                        <div v-if="sizeError" class="text-sm text-red-600 mt-2">Please select a size.</div>
                    </div>

                    <!-- Quantity -->
                    <div class="mt-5 flex items-center gap-3 text-sm">
                        <label for="qty">Quantity:</label>
                        <select id="qty" v-model="form.qty" class="rounded-lg border-gray-300 text-sm py-1.5">
                            <option v-for="n in Math.max(1, Math.min(10, available))" :key="n" :value="n">{{ n }}</option>
                        </select>
                        <span :class="available <= 5 && available > 0 ? 'text-red-600 font-semibold' : 'text-gray-500'">
                            {{ available > 0 ? (available <= 5 ? `Only ${available} left` : 'More than 5 available') : 'Out of stock' }}
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex flex-col gap-3">
                        <button @click="add(true)" :disabled="form.processing || available < 1"
                                class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 text-white rounded-full py-3 font-semibold">Buy It Now</button>
                        <button @click="add(false)" :disabled="form.processing || available < 1"
                                class="w-full border-2 border-emerald-600 text-emerald-700 hover:bg-emerald-50 disabled:opacity-50 rounded-full py-3 font-semibold">Add to cart</button>
                        <button @click="watch" class="w-full border-2 border-gray-300 hover:border-navy-900 rounded-full py-3 font-semibold flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" :fill="product.watched ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s-7.5-4.6-9.5-9.2C1 8 3.4 4.5 7 4.5c2 0 3.6 1.1 5 2.8 1.4-1.7 3-2.8 5-2.8 3.6 0 6 3.5 4.5 7.3C19.5 16.4 12 21 12 21z"/></svg>
                            {{ product.watched ? 'Watching' : 'Add to Watchlist' }}
                        </button>
                    </div>
                    <div v-if="page.props.errors.cart" class="text-red-600 text-sm mt-2">{{ page.props.errors.cart }}</div>

                    <div v-if="product.watchers" class="mt-4 text-sm flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2">
                        <span class="text-gold-dark">♥</span> <b>{{ product.watchers }}</b> {{ product.watchers === 1 ? 'person is' : 'people are' }} watching this.
                    </div>

                    <!-- Shipping, returns, payments -->
                    <dl class="mt-5 border-t border-gray-200 pt-4 text-sm grid grid-cols-[96px_1fr] gap-y-3">
                        <dt class="text-gray-500">Delivery</dt>
                        <dd>
                            <b :class="displayPrice >= freeAt && 'text-green-700'">{{ displayPrice >= freeAt ? 'Free delivery' : 'KES 300' }}</b> in Nairobi.
                            <div class="text-gray-500">Get it by <b class="text-navy-900">{{ eta.nairobi }}</b> in Nairobi, {{ eta.country }} countrywide.</div>
                        </dd>
                        <dt class="text-gray-500">Collect</dt>
                        <dd>Free click &amp; collect from our flagship store.</dd>
                        <dt class="text-gray-500">Returns</dt>
                        <dd>7 days returns on unworn items with tags.</dd>
                        <dt class="text-gray-500">Payments</dt>
                        <dd class="flex flex-wrap gap-1.5">
                            <span class="border border-gray-300 rounded px-2 py-0.5 text-xs font-semibold text-green-700">M-PESA</span>
                            <span class="border border-gray-300 rounded px-2 py-0.5 text-xs font-semibold">VISA</span>
                            <span class="border border-gray-300 rounded px-2 py-0.5 text-xs font-semibold">Mastercard</span>
                        </dd>
                    </dl>

                    <a :href="shareUrl" target="_blank" rel="noopener" class="inline-flex items-center gap-2 mt-4 text-sm underline">Share on WhatsApp</a>
                </div>
            </div>

            <!-- About this item -->
            <section class="mt-12 border border-gray-200 rounded-2xl">
                <div class="flex border-b border-gray-200 text-sm font-semibold">
                    <button v-for="t in [['specifics', 'Item specifics'], ['description', 'Description'], ['delivery', 'Delivery & returns']]" :key="t[0]"
                            @click="tab = t[0]"
                            :class="['px-5 py-3 -mb-px border-b-2', tab === t[0] ? 'border-navy-900' : 'border-transparent text-gray-500 hover:text-navy-900']">{{ t[1] }}</button>
                </div>
                <div class="p-5 text-sm">
                    <dl v-if="tab === 'specifics'" class="grid md:grid-cols-2 gap-x-10 gap-y-3">
                        <div v-for="r in specifics" :key="r[0]" class="grid grid-cols-[120px_1fr] gap-3">
                            <dt class="text-gray-500">{{ r[0] }}</dt><dd>{{ r[1] }}</dd>
                        </div>
                    </dl>
                    <p v-else-if="tab === 'description'" class="leading-relaxed text-gray-700 max-w-3xl whitespace-pre-line">{{ product.description || 'No description yet.' }}</p>
                    <div v-else class="text-gray-700 space-y-2 max-w-3xl">
                        <p>Nairobi delivery within 24 hours. Countrywide delivery in 2 to 4 days through our courier partners.</p>
                        <p>Free delivery on orders above {{ kes(freeAt) }}. Free click &amp; collect from our flagship store.</p>
                        <p>Return unworn items with tags within 7 days for an exchange or refund.</p>
                    </div>
                </div>
            </section>

            <!-- Related -->
            <section v-if="related.length" class="mt-12">
                <h2 class="text-xl md:text-2xl font-bold">Similar items</h2>
                <div class="mt-4 flex gap-4 overflow-x-auto snap-x pb-2">
                    <div v-for="p in related" :key="p.id" class="snap-start shrink-0 w-[46%] sm:w-[30%] md:w-[22%] lg:w-[15.5%]">
                        <ProductCard :product="p" layout="rail" />
                    </div>
                </div>
            </section>

            <!-- Reviews -->
            <section id="reviews" class="mt-12 grid md:grid-cols-3 gap-8">
                <div>
                    <h2 class="text-xl md:text-2xl font-bold">Ratings and reviews</h2>
                    <template v-if="product.reviews.length">
                        <div class="flex items-end gap-2 mt-3">
                            <span class="text-4xl font-bold">{{ avgRating.toFixed(1) }}</span>
                            <span class="text-gold-dark text-lg mb-1">{{ '★'.repeat(Math.round(avgRating)) }}</span>
                        </div>
                        <div class="text-sm text-gray-500">{{ product.reviews.length }} product ratings</div>
                        <div class="mt-4 space-y-1.5">
                            <div v-for="b in ratingBars" :key="b.n" class="flex items-center gap-2 text-xs">
                                <span class="w-3">{{ b.n }}</span><span class="text-gold-dark">★</span>
                                <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-navy-900" :style="{ width: `${(b.count / product.reviews.length) * 100}%` }"></div>
                                </div>
                                <span class="w-6 text-right text-gray-500">{{ b.count }}</span>
                            </div>
                        </div>
                    </template>
                    <p v-else class="text-sm text-gray-500 mt-2">No reviews yet. Bought this item? Review it from your purchases.</p>
                </div>
                <div class="md:col-span-2 space-y-5">
                    <article v-for="(r, i) in product.reviews.slice(0, 6)" :key="i" class="border-b border-gray-200 pb-5">
                        <div class="text-gold-dark">{{ '★'.repeat(r.rating) }}{{ '☆'.repeat(5 - r.rating) }}</div>
                        <p class="text-sm text-gray-800 mt-1">{{ r.body }}</p>
                        <div class="text-xs text-gray-500 mt-1">{{ r.customer }} · {{ r.date }} · <span class="text-green-700 font-semibold">Verified purchase</span></div>
                    </article>
                </div>
            </section>

            <!-- Recently viewed -->
            <section v-if="recent.length" class="mt-12">
                <h2 class="text-xl md:text-2xl font-bold">Your recently viewed items</h2>
                <div class="mt-4 flex gap-4 overflow-x-auto snap-x pb-2">
                    <div v-for="p in recent" :key="p.slug" class="snap-start shrink-0 w-[46%] sm:w-[30%] md:w-[22%] lg:w-[15.5%]">
                        <ProductCard :product="p" layout="rail" />
                    </div>
                </div>
            </section>
        </div>

        <!-- Sticky mobile buy bar -->
        <div class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 p-3 flex items-center gap-3 z-30">
            <div class="font-bold">{{ kes(displayPrice) }}</div>
            <button @click="add(true)" :disabled="form.processing || available < 1" class="flex-1 bg-emerald-600 disabled:bg-gray-300 text-white rounded-full py-2.5 text-sm font-semibold">Buy It Now</button>
        </div>

        <!-- Size guide modal -->
        <div v-if="sizeGuide" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="sizeGuide = false" role="dialog" aria-modal="true" aria-label="Size guide">
            <div class="bg-white rounded-2xl p-6 w-full max-w-md">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold">Size guide</h3>
                    <button @click="sizeGuide = false" class="text-gray-500 hover:text-navy-900" aria-label="Close">✕</button>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 border-b border-gray-200">
                            <th class="py-2">Size</th><th>Bust (cm)</th><th>Waist (cm)</th><th>Hip (cm)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in sizeRows" :key="r[0]" class="border-b border-gray-100">
                            <td class="py-2.5 font-bold">{{ r[0] }}</td><td>{{ r[1] }}</td><td>{{ r[2] }}</td><td>{{ r[3] }}</td>
                        </tr>
                    </tbody>
                </table>
                <p class="text-xs text-gray-500 mt-4">Between sizes? Size up for tailored pieces, or chat with us on WhatsApp.</p>
            </div>
        </div>
    </StorefrontLayout>
</template>
