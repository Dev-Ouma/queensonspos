<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import { Link, Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({ banners: Array, featured: Array, newArrivals: Array, categories: Array });

/* Category bubbles */
const bubbles = [
    { label: 'Dresses', img: 'photo-1539533018447-63fcce2678e3', href: '/shop?category=dresses' },
    { label: 'Tops', img: 'photo-1485968579580-b6d095142e6e', href: '/shop?category=tops-blouses' },
    { label: 'Office Wear', img: 'photo-1496747611176-843222e1e57c', href: '/shop?category=office-wear' },
    { label: 'Shoes', img: 'photo-1549298916-b41d501d3772', href: '/shop?category=shoes' },
    { label: 'Handbags', img: 'photo-1548036328-c9fa89d128fa', href: '/shop?category=handbags' },
    { label: 'Accessories', img: 'photo-1515562141207-7a88fb7ce338', href: '/shop?category=accessories' },
    { label: 'New Arrivals', img: 'photo-1583496661160-fb5886a0aaaa', href: '/shop?collection=new-arrivals' },
];

/* Recently viewed (stored by the product page) */
const recent = ref([]);

const deals = computed(() => [...props.featured, ...props.newArrivals]
    .filter((p, i, a) => p.compare_at_cents && a.findIndex(x => x.id === p.id) === i));

const rails = computed(() => [
    { title: 'Your recently viewed items', sub: 'Pick up where you left off', href: '/shop', items: recent.value },
    { title: "Today's deals", sub: 'Marked-down styles, while stock lasts', href: '/shop?sort=price_asc', items: deals.value },
    { title: 'Trending in women\'s fashion', sub: 'Our most-loved pieces right now', href: '/shop?collection=best-sellers', items: props.featured },
    { title: 'New arrivals', sub: 'Just landed this week', href: '/shop?collection=new-arrivals', items: props.newArrivals },
]);



const trust = [
    { title: 'Pay with M-Pesa', sub: 'Or Visa and Mastercard' },
    { title: 'Free delivery', sub: 'On orders above KES 5,000' },
    { title: 'Easy returns', sub: '7-day returns on unworn items' },
    { title: 'Quality checked', sub: 'Every item inspected before dispatch' },
];

const u = (id, w) => `https://images.unsplash.com/${id}?q=80&w=${w}&auto=format&fit=crop`;

const content = usePage().props.storefront || {};

/* Promo tiles beside the hero (admin-managed) */
const sideTiles = content.promo_tiles || [];

/* Hero slideshow (admin-managed via CMS → Storefront) */
const slides = content.hero_slides || [];
const slide = ref(0);
let slideTimer;

/* Testimonials (admin-managed) */
const testimonials = content.testimonials || [];
const tIndex = ref(0);
let tTimer;

/* Flash sale (admin-managed) */
const flashSale = content.flash_sale || { enabled: false };

/* Count-up stats (admin-managed) */
const stats = content.stats || [];
const counts = ref(stats.map(() => 0));
const statsEl = ref(null);
let statsDone = false;

function runCounters() {
    if (statsDone) return;
    statsDone = true;
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduced) {
        counts.value = stats.map((s) => s.target);
        return;
    }
    const start = performance.now();
    const dur = 1600;
    const tick = (now) => {
        const p = Math.min((now - start) / dur, 1);
        const ease = 1 - Math.pow(1 - p, 3);
        counts.value = stats.map((s) => Math.round(s.target * ease));
        if (p < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
}

/* Gallery */
const gallery = [
    'photo-1539533018447-63fcce2678e3', 'photo-1583496661160-fb5886a0aaaa', 'photo-1549298916-b41d501d3772',
    'photo-1548036328-c9fa89d128fa', 'photo-1543163521-1bf539c55dd2', 'photo-1515562141207-7a88fb7ce338',
];

/* Category showcase tiles */
const catTiles = [
    { img: 'photo-1539533018447-63fcce2678e3', label: 'Dresses', href: '/shop?category=dresses', span: 'md:row-span-2' },
    { img: 'photo-1496747611176-843222e1e57c', label: 'Office Wear', href: '/shop?category=office-wear', span: '' },
    { img: 'photo-1549298916-b41d501d3772', label: 'Shoes', href: '/shop?category=shoes', span: '' },
    { img: 'photo-1548036328-c9fa89d128fa', label: 'Handbags', href: '/shop?category=handbags', span: 'md:col-span-2' },
];

/* Newsletter */
const email = ref('');
const subscribed = ref(false);


/* Flash-sale countdown to next midnight */
const countdown = ref({ h: '00', m: '00', s: '00' });
let cdTimer;
function tickCountdown() {
    const now = new Date();
    const end = new Date(now); end.setHours(24, 0, 0, 0);
    let d = Math.max(0, Math.floor((end - now) / 1000));
    countdown.value = {
        h: String(Math.floor(d / 3600)).padStart(2, '0'),
        m: String(Math.floor((d % 3600) / 60)).padStart(2, '0'),
        s: String(d % 60).padStart(2, '0'),
    };
}

/* 3D tilt on category tiles */
function onTilt(e) {
    const el = e.currentTarget;
    const r = el.getBoundingClientRect();
    const x = (e.clientX - r.left) / r.width - 0.5;
    const y = (e.clientY - r.top) / r.height - 0.5;
    el.style.transform = `perspective(900px) rotateY(${x * 6}deg) rotateX(${-y * 6}deg)`;
}
function resetTilt(e) { e.currentTarget.style.transform = ''; }

onMounted(() => {
    try { recent.value = JSON.parse(localStorage.getItem('q_recent') || '[]').slice(0, 12); } catch { recent.value = []; }
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reduced && slides.length > 1) {
        slideTimer = setInterval(() => { slide.value = (slide.value + 1) % slides.length; }, 6000);
    }
    if (testimonials.length > 1) {
        tTimer = setInterval(() => { tIndex.value = (tIndex.value + 1) % testimonials.length; }, 5000);
    }
    tickCountdown();
    cdTimer = setInterval(tickCountdown, 1000);
    if (statsEl.value) {
        const io = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) { runCounters(); io.disconnect(); }
        }, { threshold: 0.4 });
        io.observe(statsEl.value);
    }
});
onBeforeUnmount(() => { clearInterval(slideTimer); clearInterval(tTimer); clearInterval(cdTimer); });

const marqueeItems = content.marquee || [];
</script>

<template>
    <Head title="QUEENSONS — Women's fashion, shoes, bags and more" />
    <StorefrontLayout>
        <div class="max-w-7xl mx-auto px-4">
            <!-- Promo banner carousel -->
            <div class="mt-4 grid lg:grid-cols-3 gap-4">
            <section v-if="slides.length" class="lg:col-span-2 relative rounded-2xl overflow-hidden bg-navy-900 text-white h-[300px] md:h-[380px]">
                <template v-for="(s, si) in slides" :key="si">
                    <img :src="s.img" alt="" aria-hidden="true"
                         class="hero-slide absolute inset-0 w-full h-full object-cover object-top"
                         :class="si === slide ? 'opacity-100' : 'opacity-0'" />
                </template>
                <div class="absolute inset-0 bg-gradient-to-r from-navy-900/90 via-navy-900/50 to-transparent"></div>
                <div class="relative h-full flex flex-col justify-center px-8 md:px-14 max-w-xl">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gold-light">{{ slides[slide].eyebrow }}</p>
                    <h1 class="font-display font-extrabold text-3xl md:text-5xl leading-tight mt-2">
                        {{ slides[slide].line1 }} {{ slides[slide].line2 }}
                    </h1>
                    <p class="text-sm md:text-base text-white/85 mt-3">Dresses, office wear, shoes, bags and accessories. Pay with M-Pesa.</p>
                    <Link href="/shop" class="mt-6 self-start bg-white text-navy-900 font-semibold text-sm rounded-full px-7 py-3 hover:bg-gray-100">Shop now</Link>
                </div>
                <button v-if="slides.length > 1" @click="slide = (slide - 1 + slides.length) % slides.length" aria-label="Previous slide"
                        class="absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 text-navy-900 flex items-center justify-center shadow">‹</button>
                <button v-if="slides.length > 1" @click="slide = (slide + 1) % slides.length" aria-label="Next slide"
                        class="absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 text-navy-900 flex items-center justify-center shadow">›</button>
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                    <button v-for="(s, si) in slides" :key="si" @click="slide = si" :aria-label="`Slide ${si + 1}`"
                            :class="['h-2 rounded-full transition-all', si === slide ? 'w-6 bg-white' : 'w-2 bg-white/50']"></button>
                </div>
            </section>
            <div class="grid grid-cols-2 lg:grid-cols-1 gap-4">
                <Link v-for="t in sideTiles" :key="t.title" :href="t.href" class="group relative rounded-2xl overflow-hidden h-[150px] md:h-[182px]">
                    <img :src="t.img" alt="" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-300" />
                    <div class="absolute inset-0 bg-gradient-to-r from-navy-900/80 to-navy-900/10"></div>
                    <div class="relative h-full flex flex-col justify-end p-5 text-white">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-gold-light">{{ t.eyebrow }}</div>
                        <div class="text-lg font-bold leading-tight">{{ t.title }}</div>
                        <span class="text-xs mt-1 underline">Shop now</span>
                    </div>
                </Link>
            </div>
            </div>

            <!-- Category bubbles -->
            <section class="mt-10">
                <h2 class="text-xl md:text-2xl font-bold">Shop by category</h2>
                <div class="mt-5 flex lg:grid lg:grid-cols-7 gap-4 overflow-x-auto snap-x pb-2 -mx-4 px-4 lg:mx-0 lg:px-0">
                    <Link v-for="c in bubbles" :key="c.label" :href="c.href" class="group text-center snap-start shrink-0 w-24 sm:w-28 lg:w-auto">
                        <div class="aspect-square rounded-full overflow-hidden bg-gray-100 mx-auto max-w-[130px]">
                            <img :src="u(c.img, 300)" :alt="c.label" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" />
                        </div>
                        <div class="text-sm font-semibold mt-2 group-hover:underline">{{ c.label }}</div>
                    </Link>
                </div>
            </section>

            <!-- Flash deals -->
            <section v-if="flashSale.enabled" class="mt-12 rounded-2xl bg-red-50 border border-red-100 px-6 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="text-xs font-bold uppercase text-red-600">{{ flashSale.eyebrow }}</div>
                    <div class="text-xl font-bold mt-0.5">{{ flashSale.title }} <span class="text-red-600">{{ flashSale.highlight }}</span></div>
                    <div class="text-sm text-gray-600">{{ flashSale.subtitle }}</div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-sm" role="timer" aria-label="Sale ends in">
                        Ends in <span class="font-bold tabular-nums text-red-600">{{ countdown.h }}:{{ countdown.m }}:{{ countdown.s }}</span>
                    </div>
                    <Link :href="flashSale.link || '/shop'" class="bg-red-600 hover:bg-red-700 text-white rounded-full px-6 py-2.5 text-sm font-semibold whitespace-nowrap">{{ flashSale.cta || 'Shop deals' }}</Link>
                </div>
            </section>

            <!-- Rails -->
            <section v-for="rail in rails" :key="rail.title" class="mt-12">
                <template v-if="rail.items.length">
                    <div class="flex items-end justify-between">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold">{{ rail.title }}</h2>
                            <p class="text-sm text-gray-500">{{ rail.sub }}</p>
                        </div>
                        <Link :href="rail.href" class="text-sm font-semibold underline whitespace-nowrap">See all</Link>
                    </div>
                    <div class="mt-4 flex gap-4 overflow-x-auto snap-x pb-2 -mx-1 px-1">
                        <div v-for="p in rail.items" :key="p.id" class="snap-start shrink-0 w-[46%] sm:w-[30%] md:w-[22%] lg:w-[15.5%]">
                            <ProductCard :product="p" layout="rail" />
                        </div>
                    </div>
                </template>
            </section>

            <!-- Promo tiles from CMS banners -->
            <section v-if="banners.length" class="mt-12 grid md:grid-cols-2 gap-4">
                <Link v-for="(b, bi) in banners" :key="b.id" :href="b.link || '/shop'"
                      class="group relative overflow-hidden rounded-2xl min-h-[200px] flex items-end p-6"
                      :class="bi % 2 ? 'bg-butter text-navy-900' : 'bg-emerald-100 text-navy-900'">
                    <div>
                        <div class="text-2xl font-bold">{{ b.title }}</div>
                        <div class="text-sm mt-1 opacity-80">{{ b.subtitle }}</div>
                        <span class="inline-block mt-4 bg-navy-900 text-white rounded-full px-5 py-2 text-sm font-semibold group-hover:bg-navy-700">Shop now</span>
                    </div>
                </Link>
            </section>

            <!-- Trust strip -->
            <section class="mt-12 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div v-for="t in trust" :key="t.title" class="rounded-xl border border-gray-200 p-4">
                    <div class="font-semibold">{{ t.title }}</div>
                    <div class="text-gray-500 text-xs mt-1">{{ t.sub }}</div>
                </div>
            </section>
        </div>
    </StorefrontLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .5s ease, transform .5s ease; }
.fade-enter-from { opacity: 0; transform: translateY(10px); }
.fade-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
