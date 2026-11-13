<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import { Link, Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount } from 'vue';

defineProps({ banners: Array, featured: Array, newArrivals: Array, categories: Array });

const u = (id, w) => `https://images.unsplash.com/${id}?q=80&w=${w}&auto=format&fit=crop`;

const content = usePage().props.storefront || {};

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

/* Hero mouse parallax */
const px = ref(0);
const py = ref(0);
function onHeroMove(e) {
    const r = e.currentTarget.getBoundingClientRect();
    px.value = ((e.clientX - r.left) / r.width - 0.5) * 2;
    py.value = ((e.clientY - r.top) / r.height - 0.5) * 2;
}
function resetHero() { px.value = 0; py.value = 0; }

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
    <Head title="QUEENSONS — Style Beyond Seasons" />
    <StorefrontLayout>
        <!-- Editorial hero slideshow -->
        <section v-if="slides.length" class="relative overflow-hidden bg-navy-900 text-white min-h-[560px] md:min-h-[680px] flex items-center"
                 @mousemove="onHeroMove" @mouseleave="resetHero">
            <template v-for="(s, si) in slides" :key="si">
                <img :src="s.img" alt="" aria-hidden="true"
                     class="hero-slide absolute -inset-4 w-[calc(100%+2rem)] h-[calc(100%+2rem)] object-cover object-top will-change-transform"
                     :class="si === slide ? 'opacity-80 animate-kenburns' : 'opacity-0'"
                     :style="{ translate: `${px * -12}px ${py * -8}px` }" />
            </template>
            <div class="absolute inset-0 bg-gradient-to-r from-navy-900/90 via-navy-900/55 to-navy-900/10"></div>
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-gold/10 blur-3xl"></div>

            <div class="relative max-w-7xl mx-auto px-4 w-full py-24" :style="{ translate: `${px * 8}px ${py * 5}px` }">
                <div class="max-w-xl" :key="slide">
                    <div class="flex items-center gap-3 mb-6 animate-fade-up">
                        <img src="/logo.svg" alt="" class="w-10 h-10" />
                        <div class="h-px w-16 bg-gold/70"></div>
                        <p class="eyebrow text-gold">{{ slides[slide].eyebrow }}</p>
                    </div>
                    <h1 class="font-display text-5xl md:text-7xl leading-[1.05]">
                        <span class="block animate-fade-up" style="animation-delay:.1s">{{ slides[slide].line1 }}</span>
                        <span class="block italic text-gold-light animate-fade-up" style="animation-delay:.3s">{{ slides[slide].line2 }}</span>
                    </h1>
                    <p class="text-navy-100/90 mt-6 max-w-md text-[15px] leading-relaxed animate-fade-up" style="animation-delay:.45s">
                        Elegant women's fashion — dresses, office wear, shoes, bags and accessories.
                        Designed to last, priced to love.
                    </p>
                    <div class="mt-10 flex flex-wrap gap-4 animate-fade-up" style="animation-delay:.6s">
                        <Link href="/shop" class="sheen bg-emerald-600 hover:bg-emerald-700 text-white px-9 py-3.5 rounded-full text-sm font-semibold transition">
                            Shop the Collection
                        </Link>
                        <Link href="/shop?collection=new-arrivals"
                              class="border border-gold/80 text-gold-light hover:bg-gold hover:text-navy-900 px-9 py-3.5 rounded-full text-sm font-semibold transition">
                            New Arrivals
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Slide dots -->
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-2.5">
                <button v-for="(s, si) in slides" :key="si" @click="slide = si"
                        :aria-label="`Slide ${si + 1}`"
                        :class="['h-1.5 rounded-full transition-all duration-500', si === slide ? 'w-8 bg-gold' : 'w-3 bg-white/40 hover:bg-white/70']"></button>
            </div>
            <!-- Scroll hint -->
            <div class="scroll-hint absolute bottom-7 right-8 hidden md:flex flex-col items-center text-gold/80" aria-hidden="true">
                <span class="text-[10px] tracking-[0.3em] uppercase rotate-90 origin-center translate-y-[-14px]">Scroll</span>
                <svg class="w-4 h-4 mt-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.5" d="M19 14l-7 7-7-7M12 21V3"/></svg>
            </div>
            <div class="absolute bottom-0 inset-x-0 rule-gold"></div>
        </section>

        <!-- Marquee strip -->
        <div v-if="marqueeItems.length" class="bg-navy-900 border-t border-navy-800 py-3 overflow-hidden" aria-hidden="true">
            <div class="marquee-track">
                <div v-for="n in 2" :key="n" class="flex shrink-0">
                    <span v-for="(m, mi) in marqueeItems" :key="mi" class="flex items-center text-gold-light/90 text-xs tracking-[0.25em] uppercase whitespace-nowrap">
                        <span class="px-6">{{ m }}</span><span class="text-gold">✦</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Flash sale countdown -->
        <section v-if="flashSale.enabled" class="max-w-7xl mx-auto px-4 mt-12" v-reveal>
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-navy-900 via-navy-800 to-navy-900 text-white border border-gold/30 px-8 py-8 flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="absolute -top-10 right-24 w-40 h-40 rounded-full bg-gold/10 blur-2xl" aria-hidden="true"></div>
                <div class="relative">
                    <p class="eyebrow text-gold mb-1.5">{{ flashSale.eyebrow }}</p>
                    <div class="font-display text-2xl md:text-3xl">{{ flashSale.title }} — <span class="italic text-gold-light">{{ flashSale.highlight }}</span></div>
                    <p class="text-navy-100/80 text-sm mt-1.5">{{ flashSale.subtitle }}</p>
                </div>
                <div class="relative flex items-center gap-5">
                    <div class="flex gap-2 text-center" role="timer" aria-label="Sale ends in">
                        <div v-for="part in [{ v: countdown.h, l: 'HRS' }, { v: countdown.m, l: 'MIN' }, { v: countdown.s, l: 'SEC' }]" :key="part.l"
                             class="bg-navy-700/80 border border-gold/20 rounded-xl px-3.5 py-2.5 min-w-[64px]">
                            <div class="font-display text-2xl text-gold-light tabular-nums">{{ part.v }}</div>
                            <div class="text-[9px] tracking-[0.25em] text-navy-100/70 mt-0.5">{{ part.l }}</div>
                        </div>
                    </div>
                    <Link :href="flashSale.link || '/shop'" class="sheen bg-gold text-navy-900 hover:bg-gold-light rounded-full px-7 py-3 text-sm font-semibold transition whitespace-nowrap">
                        {{ flashSale.cta }}
                    </Link>
                </div>
            </div>
        </section>

        <!-- Banners -->
        <section v-if="banners.length" class="max-w-7xl mx-auto px-4 mt-12 grid md:grid-cols-2 gap-5">
            <Link v-for="(b, bi) in banners" :key="b.id" :href="b.link || '/shop'" v-reveal="bi * 120"
                  class="lift group relative overflow-hidden rounded-2xl border border-gold/30 bg-gradient-to-br from-navy-700 to-navy-900 text-white p-10">
                <div class="eyebrow text-gold mb-3">The Edit</div>
                <div class="font-display text-3xl leading-snug">{{ b.title }}</div>
                <div class="text-navy-100/80 text-sm mt-2 max-w-sm">{{ b.subtitle }}</div>
                <span class="inline-flex items-center gap-2 text-gold-light text-xs mt-6 tracking-[0.2em] uppercase group-hover:gap-4 transition-all">
                    Shop the edit <span aria-hidden="true">→</span>
                </span>
            </Link>
        </section>

        <!-- Category showcase tiles -->
        <section class="max-w-7xl mx-auto px-4 mt-20">
            <div class="text-center mb-10" v-reveal>
                <p class="eyebrow text-gold-dark mb-2">Curated For You</p>
                <h2 class="font-display text-3xl mb-3">Shop by Category</h2>
                <div class="rule-gold max-w-[120px] mx-auto"></div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 md:auto-rows-[220px]">
                <Link v-for="(t, ti) in catTiles" :key="t.label" :href="t.href" v-reveal="ti * 100"
                      @mousemove="onTilt" @mouseleave="resetTilt"
                      :class="['group relative overflow-hidden rounded-2xl min-h-[180px] transition-transform duration-200 will-change-transform', t.span]">
                    <img :src="u(t.img, 900)" :alt="t.label"
                         class="absolute inset-0 w-full h-full object-cover transition duration-700 group-hover:scale-110" />
                    <div class="absolute inset-0 bg-gradient-to-t from-navy-900/80 via-navy-900/20 to-transparent"></div>
                    <div class="absolute bottom-0 inset-x-0 p-5 flex items-end justify-between">
                        <span class="font-display text-xl text-white">{{ t.label }}</span>
                        <span class="text-gold-light text-xs tracking-[0.2em] uppercase opacity-0 group-hover:opacity-100 transition">Shop →</span>
                    </div>
                </Link>
            </div>
            <div class="flex flex-wrap justify-center gap-3 mt-8" v-reveal>
                <Link v-for="c in categories" :key="c.id" :href="`/shop?category=${c.slug}`"
                      class="border border-gray-200 hover:border-gold hover:text-gold-dark rounded-full px-6 py-2.5 text-sm tracking-wide transition">
                    {{ c.name }}
                </Link>
            </div>
        </section>

        <!-- New arrivals -->
        <section v-if="newArrivals.length" class="max-w-7xl mx-auto px-4 mt-20">
            <div class="flex items-end justify-between mb-8" v-reveal>
                <div>
                    <p class="eyebrow text-gold-dark mb-1">Just Landed</p>
                    <h2 class="font-display text-3xl">New Arrivals</h2>
                </div>
                <Link href="/shop?collection=new-arrivals" class="text-sm text-emerald-600 hover:underline">View all →</Link>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div v-for="(p, pi) in newArrivals" :key="p.id" v-reveal="(pi % 4) * 90">
                    <ProductCard :product="p" />
                </div>
            </div>
        </section>

        <!-- Editorial interlude -->
        <section class="mt-24 bg-navy-900 text-white overflow-hidden" v-reveal>
            <div class="max-w-7xl mx-auto grid md:grid-cols-2 items-center">
                <div class="relative h-72 md:h-[420px] overflow-hidden group">
                    <img :src="u('photo-1591047139829-d91aecb6caea', 1200)" alt="Tailored garment detail"
                         class="absolute inset-0 w-full h-full object-cover transition duration-700 group-hover:scale-105" />
                    <div class="absolute inset-0 bg-navy-900/20"></div>
                </div>
                <div class="px-8 md:px-16 py-14">
                    <p class="eyebrow text-gold mb-3">The Queensons Standard</p>
                    <h2 class="font-display text-3xl md:text-4xl leading-snug">
                        Tailored finishes.<br /><span class="italic text-gold-light">Honest prices.</span>
                    </h2>
                    <p class="text-navy-100/85 text-sm mt-5 leading-relaxed max-w-md">
                        Every piece is hand-checked before dispatch — stitching, lining, hardware.
                        Quality fabrics and finishes without the premium markup.
                    </p>
                    <Link href="/shop" class="inline-block mt-8 border border-gold/70 text-gold-light hover:bg-gold hover:text-navy-900 px-8 py-3 rounded-full text-sm font-semibold transition">
                        Discover the Collection
                    </Link>
                </div>
            </div>
        </section>

        <!-- Stats band -->
        <section v-if="stats.length" ref="statsEl" class="max-w-7xl mx-auto px-4 mt-20">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center" v-reveal>
                <div v-for="(s, si) in stats" :key="s.label">
                    <div class="font-display text-4xl md:text-5xl text-navy-700">
                        {{ counts[si].toLocaleString() }}<span class="text-gold">{{ s.suffix }}</span>
                    </div>
                    <div class="text-xs tracking-[0.2em] uppercase text-gray-400 mt-2">{{ s.label }}</div>
                </div>
            </div>
            <div class="rule-gold mt-12"></div>
        </section>

        <!-- Featured -->
        <section v-if="featured.length" class="max-w-7xl mx-auto px-4 mt-20">
            <div class="flex items-end justify-between mb-8" v-reveal>
                <div>
                    <p class="eyebrow text-gold-dark mb-1">House Favourites</p>
                    <h2 class="font-display text-3xl">Featured</h2>
                </div>
                <Link href="/shop" class="text-sm text-emerald-600 hover:underline">Shop all →</Link>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div v-for="(p, pi) in featured" :key="p.id" v-reveal="(pi % 4) * 90">
                    <ProductCard :product="p" />
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <section v-if="testimonials.length" class="bg-navy-50/60 mt-24 py-16" v-reveal>
            <div class="max-w-3xl mx-auto px-4 text-center">
                <p class="eyebrow text-gold-dark mb-2">Word of Mouth</p>
                <h2 class="font-display text-3xl mb-10">Loved across Kenya</h2>
                <div class="relative min-h-[150px]">
                    <transition name="fade" mode="out-in">
                        <blockquote :key="tIndex">
                            <div class="text-gold text-2xl mb-4" aria-hidden="true">“</div>
                            <p class="font-display text-xl md:text-2xl leading-relaxed text-navy-800">
                                {{ testimonials[tIndex].quote }}
                            </p>
                            <footer class="mt-5 text-sm text-gray-500">
                                <span class="font-semibold text-navy-700">{{ testimonials[tIndex].name }}</span> · {{ testimonials[tIndex].city }}
                            </footer>
                        </blockquote>
                    </transition>
                </div>
                <div class="flex justify-center gap-2.5 mt-6">
                    <button v-for="(t, i) in testimonials" :key="i" @click="tIndex = i"
                            :aria-label="`Testimonial ${i + 1}`"
                            :class="['h-1.5 rounded-full transition-all duration-500', i === tIndex ? 'w-8 bg-gold' : 'w-3 bg-navy-100 hover:bg-navy-600/40']"></button>
                </div>
            </div>
        </section>

        <!-- Gallery -->
        <section class="max-w-7xl mx-auto px-4 mt-20">
            <div class="text-center mb-10" v-reveal>
                <p class="eyebrow text-gold-dark mb-2">The Lookbook</p>
                <h2 class="font-display text-3xl mb-3">As Worn by You</h2>
                <div class="rule-gold max-w-[120px] mx-auto"></div>
            </div>
            <div class="grid grid-cols-3 md:grid-cols-6 gap-3">
                <Link v-for="(g, gi) in gallery" :key="g" href="/shop" v-reveal="gi * 70"
                      class="group relative overflow-hidden rounded-xl aspect-[3/4]">
                    <img :src="u(g, 600)" alt="Queensons lookbook style"
                         class="absolute inset-0 w-full h-full object-cover transition duration-700 group-hover:scale-110" />
                    <div class="absolute inset-0 bg-navy-900/0 group-hover:bg-navy-900/30 transition flex items-center justify-center">
                        <span class="text-white text-xs tracking-[0.2em] uppercase opacity-0 group-hover:opacity-100 transition">Shop</span>
                    </div>
                </Link>
            </div>
        </section>

        <!-- Newsletter -->
        <section class="mt-24 bg-navy-900 text-white relative overflow-hidden" v-reveal>
            <div class="absolute -top-20 -left-20 w-72 h-72 rounded-full bg-gold/10 blur-3xl"></div>
            <div class="absolute -bottom-24 -right-16 w-80 h-80 rounded-full bg-navy-700/60 blur-3xl"></div>
            <div class="relative max-w-2xl mx-auto px-4 py-16 text-center">
                <img src="/logo.svg" alt="" class="w-12 h-12 mx-auto mb-5 animate-float" />
                <p class="eyebrow text-gold mb-2">The Inner Circle</p>
                <h2 class="font-display text-3xl">First looks, private drops.</h2>
                <p class="text-navy-100/80 text-sm mt-3">New arrivals, members-only offers and styling notes — once a week, never spam.</p>
                <form v-if="!subscribed" @submit.prevent="subscribed = true" class="mt-8 flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
                    <input v-model="email" type="email" required placeholder="your@email.com"
                           class="flex-1 rounded-full border-navy-600 bg-navy-800/70 text-white placeholder-navy-100/50 text-sm px-5 py-3 focus:border-gold focus:ring-gold" />
                    <button type="submit" class="sheen bg-gold text-navy-900 hover:bg-gold-light rounded-full px-7 py-3 text-sm font-semibold transition">
                        Join
                    </button>
                </form>
                <p v-else class="mt-8 text-gold-light text-sm">Karibu to the inner circle — watch your inbox. ✦</p>
            </div>
        </section>

        <!-- Value props -->
        <section class="max-w-7xl mx-auto px-4 mt-20 mb-4">
            <div class="grid md:grid-cols-3 gap-10 text-center">
                <div v-reveal>
                    <div class="font-display text-gold-dark text-lg mb-2">I</div>
                    <div class="font-semibold tracking-wide">Affordable Premium</div>
                    <p class="text-sm text-gray-500 mt-2 max-w-xs mx-auto">Quality fabrics and finishes without the premium markup.</p>
                </div>
                <div v-reveal="120">
                    <div class="font-display text-gold-dark text-lg mb-2">II</div>
                    <div class="font-semibold tracking-wide">Fast Delivery</div>
                    <p class="text-sm text-gray-500 mt-2 max-w-xs mx-auto">Nairobi within 24 hours, countrywide in 2–4 days.</p>
                </div>
                <div v-reveal="240">
                    <div class="font-display text-gold-dark text-lg mb-2">III</div>
                    <div class="font-semibold tracking-wide">Easy Returns</div>
                    <p class="text-sm text-gray-500 mt-2 max-w-xs mx-auto">7-day returns on unworn items with tags attached.</p>
                </div>
            </div>
        </section>
    </StorefrontLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .5s ease, transform .5s ease; }
.fade-enter-from { opacity: 0; transform: translateY(10px); }
.fade-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
