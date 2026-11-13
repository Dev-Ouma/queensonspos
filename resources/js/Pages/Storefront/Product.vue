<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { kes, img } from '@/money';

const props = defineProps({ product: Object, variants: Array, related: Array });
const page = usePage();

const colors = computed(() => [...new Set(props.variants.map(v => v.color).filter(Boolean))]);
const selectedColor = ref(colors.value[0] || null);
const sizes = computed(() => props.variants.filter(v => !selectedColor.value || v.color === selectedColor.value));
const selectedVariantId = ref(null);

const selected = computed(() => props.variants.find(v => v.id === selectedVariantId.value));
const displayPrice = computed(() => selected.value?.price_cents ?? Math.min(...props.variants.map(v => v.price_cents)));

const form = useForm({ variant_id: null, qty: 1 });

/* Size guide modal */
const sizeGuide = ref(false);
const sizeRows = [
    ['S', '84–88', '64–68', '90–94'],
    ['M', '88–92', '68–72', '94–98'],
    ['L', '92–98', '72–78', '98–104'],
    ['XL', '98–106', '78–86', '104–112'],
];

/* WhatsApp share */
const shareUrl = computed(() => {
    const text = `Check out ${props.product.name} at QUEENSONS — ${typeof window !== 'undefined' ? window.location.href : ''}`;
    return `https://wa.me/?text=${encodeURIComponent(text)}`;
});

/* Recently viewed (localStorage) */
const recent = ref([]);
onMounted(() => {
    try {
        const key = 'q_recent';
        const list = JSON.parse(localStorage.getItem(key) || '[]');
        recent.value = list.filter((r) => r.slug !== props.product.slug).slice(0, 4);
        const entry = {
            slug: props.product.slug,
            name: props.product.name,
            price_cents: displayPrice.value,
            image: props.product.images[0]?.path || null,
            in_stock: true,
        };
        localStorage.setItem(key, JSON.stringify([entry, ...list.filter((r) => r.slug !== entry.slug)].slice(0, 8)));
    } catch { /* storage unavailable — skip */ }
});

const zoomOrigin = ref('50% 50%');
function onZoom(e) {
    const r = e.currentTarget.getBoundingClientRect();
    zoomOrigin.value = `${((e.clientX - r.left) / r.width) * 100}% ${((e.clientY - r.top) / r.height) * 100}%`;
}

function addToCart() {
    if (!selectedVariantId.value) return;
    form.variant_id = selectedVariantId.value;
    form.post('/cart/add', { preserveScroll: true });
}

const avgRating = computed(() => {
    const r = props.product.reviews;
    return r.length ? (r.reduce((a, b) => a + b.rating, 0) / r.length).toFixed(1) : null;
});
</script>

<template>
    <Head :title="`${product.name} — QUEENSONS`">
        <meta name="description" :content="(product.description || '').slice(0, 160)" />
        <meta property="og:title" :content="`${product.name} — QUEENSONS`" />
        <meta property="og:description" :content="(product.description || '').slice(0, 160)" />
        <meta property="og:type" content="product" />
        <meta v-if="product.images.length" property="og:image" :content="img(product.images[0].path)" />
    </Head>
    <StorefrontLayout>
        <div class="max-w-7xl mx-auto px-4 py-10 grid md:grid-cols-2 gap-10">
            <!-- Gallery with hover zoom -->
            <div class="animate-fade-in">
                <div class="aspect-[3/4] bg-gradient-to-br from-navy-50 to-gray-100 rounded-xl overflow-hidden group cursor-zoom-in"
                     @mousemove="onZoom" @mouseleave="zoomOrigin = '50% 50%'">
                    <img v-if="product.images.length" :src="img(product.images[0].path)" :alt="product.images[0].alt || product.name"
                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-[1.7]"
                         :style="{ transformOrigin: zoomOrigin }" />
                    <div v-else class="w-full h-full flex items-center justify-center font-display text-6xl text-navy-100">Q</div>
                </div>
                <p class="text-[10px] text-gray-300 text-center mt-2 tracking-[0.2em] uppercase">Hover to zoom</p>
            </div>

            <!-- Details -->
            <div class="animate-fade-up">
                <div class="text-xs tracking-widest text-gold-dark uppercase">{{ product.brand }}</div>
                <h1 class="font-display text-3xl mt-1">{{ product.name }}</h1>
                <div class="flex items-center gap-3 mt-2">
                    <div class="text-2xl font-bold text-navy-700">{{ kes(displayPrice) }}</div>
                    <div v-if="avgRating" class="text-sm text-gold-dark">★ {{ avgRating }} ({{ product.reviews.length }})</div>
                </div>

                <p class="text-gray-600 text-sm mt-4 leading-relaxed">{{ product.description }}</p>

                <!-- Colour -->
                <div v-if="colors.length" class="mt-6">
                    <div class="text-sm font-semibold mb-2">Colour: <span class="font-normal">{{ selectedColor }}</span></div>
                    <div class="flex gap-2 flex-wrap">
                        <button v-for="c in colors" :key="c" @click="selectedColor = c; selectedVariantId = null"
                                :class="['px-4 py-2 text-sm rounded-full border transition',
                                         selectedColor === c ? 'border-navy-700 bg-navy-700 text-white' : 'border-gray-200 hover:border-navy-700']">
                            {{ c }}
                        </button>
                    </div>
                </div>

                <!-- Size -->
                <div class="mt-5">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-semibold">Size</div>
                        <button type="button" @click="sizeGuide = true" class="text-xs text-gold-dark hover:underline">Size guide</button>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <button v-for="v in sizes" :key="v.id" @click="selectedVariantId = v.id" :disabled="v.available < 1"
                                :class="['min-w-[3rem] px-3 py-2 text-sm rounded border transition',
                                         v.available < 1 ? 'border-gray-100 text-gray-300 line-through cursor-not-allowed'
                                             : selectedVariantId === v.id ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-gray-200 hover:border-emerald-600']">
                            {{ v.size }}
                        </button>
                    </div>
                    <div v-if="selected && selected.available <= 5" class="text-xs text-red-500 mt-2">Only {{ selected.available }} left in stock</div>
                </div>

                <!-- Qty + add -->
                <div class="flex gap-3 mt-6">
                    <select v-model="form.qty" class="rounded border-gray-200 w-20">
                        <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                    </select>
                    <button @click="addToCart" :disabled="!selectedVariantId || form.processing"
                            class="flex-1 bg-navy-700 hover:bg-navy-600 disabled:bg-gray-300 text-white rounded-full py-3 text-sm font-semibold transition">
                        {{ selectedVariantId ? 'Add to Cart' : 'Select size' }}
                    </button>
                </div>
                <div v-if="page.props.errors.cart" class="text-red-500 text-sm mt-2">{{ page.props.errors.cart }}</div>

                <!-- Share -->
                <a :href="shareUrl" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 mt-4 text-xs text-gray-400 hover:text-emerald-600 transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm0 18.2a8.2 8.2 0 01-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1112 20.2z"/></svg>
                    Share on WhatsApp
                </a>

                <!-- Trust strip -->
                <div class="grid grid-cols-3 gap-3 mt-7 border border-gray-100 rounded-xl p-4 text-center">
                    <div>
                        <svg class="w-5 h-5 mx-auto text-gold-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.6a1 1 0 01.9.6l1.9 4.3a1 1 0 01.1.4V16a1 1 0 01-1 1h-1.5M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <div class="text-[11px] text-gray-500 mt-1.5">24h Nairobi<br />delivery</div>
                    </div>
                    <div class="border-x border-gray-100">
                        <svg class="w-5 h-5 mx-auto text-gold-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M16 15v-1a4 4 0 00-8 0v1m-2 0h12l-1 7H7l-1-7zM12 3l1.5 3L17 7l-2.5 2 .5 3.5L12 11l-3 1.5L9.5 9 7 7l3.5-1z"/></svg>
                        <div class="text-[11px] text-gray-500 mt-1.5">Hand-checked<br />quality</div>
                    </div>
                    <div>
                        <svg class="w-5 h-5 mx-auto text-gold-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M16 15H8m8 0l-3-3m3 3l-3 3M3 12a9 9 0 1118 0 9 9 0 01-18 0z"/></svg>
                        <div class="text-[11px] text-gray-500 mt-1.5">7-day easy<br />returns</div>
                    </div>
                </div>

                <!-- Care -->
                <details class="mt-8 border-t pt-4 text-sm">
                    <summary class="font-semibold cursor-pointer">Care instructions</summary>
                    <p class="text-gray-500 mt-2">{{ product.care_instructions }}</p>
                </details>
                <details class="mt-2 border-t pt-4 text-sm">
                    <summary class="font-semibold cursor-pointer">Delivery & returns</summary>
                    <p class="text-gray-500 mt-2">Nairobi delivery within 24 hours, countrywide 2–4 days. 7-day returns on unworn items.</p>
                </details>

                <!-- Reviews -->
                <div v-if="product.reviews.length" class="mt-8 border-t pt-6">
                    <h3 class="font-semibold mb-3">Customer Reviews</h3>
                    <div v-for="(r, i) in product.reviews" :key="i" class="mb-4 text-sm">
                        <div class="text-gold-dark">{{ '★'.repeat(r.rating) }}{{ '☆'.repeat(5 - r.rating) }}</div>
                        <p class="text-gray-600 mt-1">{{ r.body }}</p>
                        <div class="text-xs text-gray-400 mt-1">{{ r.customer }} · {{ r.date }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related -->
        <section v-if="related.length" class="max-w-7xl mx-auto px-4 mt-10" v-reveal>
            <h2 class="font-display text-2xl mb-6">You may also like</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <ProductCard v-for="p in related" :key="p.id" :product="p" />
            </div>
        </section>

        <!-- Recently viewed -->
        <section v-if="recent.length" class="max-w-7xl mx-auto px-4 mt-14" v-reveal>
            <div class="flex items-center gap-3 mb-6">
                <h2 class="font-display text-2xl">Recently Viewed</h2>
                <div class="rule-gold flex-1"></div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <ProductCard v-for="p in recent" :key="p.slug" :product="p" />
            </div>
        </section>

        <!-- Size guide modal -->
        <div v-if="sizeGuide" class="fixed inset-0 bg-navy-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="sizeGuide = false">
            <div class="bg-white rounded-2xl p-7 w-full max-w-md animate-fade-up">
                <div class="flex items-center justify-between mb-1">
                    <p class="eyebrow text-gold-dark">Fit Notes</p>
                    <button @click="sizeGuide = false" class="text-gray-300 hover:text-gray-500" aria-label="Close">✕</button>
                </div>
                <h3 class="font-display text-2xl mb-4">Size Guide</h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 uppercase tracking-wider border-b border-gray-100">
                            <th class="py-2">Size</th><th>Bust (cm)</th><th>Waist (cm)</th><th>Hip (cm)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in sizeRows" :key="r[0]" class="border-b border-gray-50">
                            <td class="py-2.5 font-semibold text-navy-700">{{ r[0] }}</td>
                            <td>{{ r[1] }}</td><td>{{ r[2] }}</td><td>{{ r[3] }}</td>
                        </tr>
                    </tbody>
                </table>
                <p class="text-xs text-gray-400 mt-4">Between sizes? We recommend sizing up for tailored pieces. WhatsApp us for a personal fit consult.</p>
            </div>
        </div>
    </StorefrontLayout>
</template>
