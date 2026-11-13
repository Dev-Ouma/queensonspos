<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';

const page = usePage();
const cartCount = computed(() => page.props.cartCount || 0);
const customer = computed(() => page.props.auth?.customer);
const flash = computed(() => page.props.flash?.success);
const q = ref('');
const mobileOpen = ref(false);
const catOpen = ref(false);
const qCat = ref('');
const mega = [
    { title: 'Clothing', items: [['Dresses', 'dresses'], ['Tops & Blouses', 'tops-blouses'], ['Trousers', 'trousers'], ['Jeans', 'jeans'], ['Skirts', 'skirts'], ['Jackets & Coats', 'jackets-coats']] },
    { title: 'Shop by occasion', items: [['Office Wear', 'office-wear'], ['Casual Wear', 'casual-wear'], ['Occasion Wear', 'occasion-wear']] },
    { title: 'Shoes, bags & more', items: [['Shoes', 'shoes'], ['Handbags', 'handbags'], ['Accessories', 'accessories']] },
];

/* Rotating announcement bar (admin-managed via CMS → Storefront) */
const storefront = computed(() => page.props.storefront || {});
const announcements = storefront.value.announcements || ['Style Beyond Seasons.'];
const socials = storefront.value.socials || {};
const annIndex = ref(0);
let annTimer;

/* Social-proof toasts */
const cities = ['Nairobi', 'Westlands', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Thika', 'Karen'];
const firstNames = ['Amina', 'Wanjiru', 'Achieng', 'Fatma', 'Njeri', 'Zawadi', 'Makena', 'Sifa'];
const toast = ref(null);
let toastTimers = [];

function scheduleToast() {
    const items = page.props.socialProof || [];
    if (!items.length || storefront.value.social_proof_enabled === false) return;
    const show = () => {
        toast.value = {
            name: firstNames[Math.floor(Math.random() * firstNames.length)],
            city: cities[Math.floor(Math.random() * cities.length)],
            product: items[Math.floor(Math.random() * items.length)].name,
            mins: 2 + Math.floor(Math.random() * 38),
        };
        toastTimers.push(setTimeout(() => { toast.value = null; }, 5000));
        toastTimers.push(setTimeout(show, 14000 + Math.random() * 10000));
    };
    toastTimers.push(setTimeout(show, 7000));
}

/* Scroll progress + back-to-top */
const scrollPct = ref(0);
const scrolled = ref(false);
function toTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function onScroll() {
    const h = document.documentElement;
    const max = h.scrollHeight - h.clientHeight;
    scrollPct.value = max > 0 ? Math.min(h.scrollTop / max, 1) : 0;
    scrolled.value = h.scrollTop > 600;
}

onMounted(() => {
    if (announcements.length > 1) {
        annTimer = setInterval(() => { annIndex.value = (annIndex.value + 1) % announcements.length; }, 4000);
    }
    scheduleToast();
    window.addEventListener('scroll', onScroll, { passive: true });
});
onBeforeUnmount(() => {
    clearInterval(annTimer);
    toastTimers.forEach(clearTimeout);
    window.removeEventListener('scroll', onScroll);
});

const nav = [
    { label: 'New Arrivals', href: '/shop?collection=new-arrivals' },
    { label: 'Dresses', slug: 'dresses', href: '/shop?category=dresses' },
    { label: 'Tops', slug: 'tops-blouses', href: '/shop?category=tops-blouses' },
    { label: 'Office Wear', slug: 'office-wear', href: '/shop?category=office-wear' },
    { label: 'Shoes', slug: 'shoes', href: '/shop?category=shoes' },
    { label: 'Handbags', slug: 'handbags', href: '/shop?category=handbags' },
    { label: 'Accessories', slug: 'accessories', href: '/shop?category=accessories' },
];

function search() {
    router.get('/shop', Object.fromEntries(Object.entries({ q: q.value, category: qCat.value }).filter(([, v]) => v)));
}
</script>

<template>
    <div class="min-h-screen bg-white font-sans text-navy-900 flex flex-col">
        <!-- Utility bar -->
        <div class="border-b border-gray-200 text-xs text-navy-900">
            <div class="max-w-7xl mx-auto px-4 h-8 flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <span v-if="customer">Hi <b>{{ customer.name?.split(' ')[0] }}</b>!</span>
                    <span v-else>Hi! <Link href="/account/login" class="text-emerald-700 underline">Sign in</Link> or <Link href="/account/register" class="text-emerald-700 underline">register</Link></span>
                    <Link href="/shop?sort=price_asc" class="hidden md:inline hover:underline">Daily Deals</Link>
                    <Link href="/page/faq" class="hidden md:inline hover:underline">Help &amp; Contact</Link>
                </div>
                <div class="hidden md:block text-gray-600 truncate" aria-live="polite">{{ announcements[annIndex] }}</div>
                <div class="flex items-center gap-4">
                    <Link href="/account/wishlist" class="hidden sm:inline hover:underline">Watchlist</Link>
                    <Link :href="customer ? '/account' : '/account/login'" class="hover:underline">My QUEENSONS</Link>
                    <Link href="/cart" class="relative flex items-center" aria-label="Cart">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.6" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 4.6A1 1 0 005.6 19H19M9 22a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/></svg>
                        <span v-if="cartCount" class="absolute -top-1.5 -right-2 bg-emerald-600 text-white text-[10px] rounded-full min-w-4 h-4 px-1 flex items-center justify-center">{{ cartCount }}</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Main header: logo, category picker, search -->
        <header class="bg-white sticky top-0 z-40 border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-3 md:gap-5">
                <button class="md:hidden" @click="mobileOpen = !mobileOpen" aria-label="Menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <Link href="/" class="flex items-center gap-2 shrink-0">
                    <img src="/logo.svg" alt="QUEENSONS" class="w-9 h-9" />
                    <span class="hidden sm:inline font-display text-2xl tracking-tight font-extrabold text-navy-900">queensons<span class="text-gold">.</span></span>
                </Link>
                <div class="relative hidden md:block shrink-0">
                    <button @click="catOpen = !catOpen" class="text-xs leading-tight text-left text-gray-600 hover:text-navy-900 flex items-center gap-1">
                        Shop by<br />category
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div v-if="catOpen" @mouseleave="catOpen = false"
                         class="absolute top-10 left-0 bg-white border border-gray-200 shadow-2xl rounded-xl p-6 flex gap-8 w-[760px] text-sm z-50">
                        <div v-for="g in mega" :key="g.title" class="min-w-[150px]">
                            <div class="font-bold mb-3">{{ g.title }}</div>
                            <div class="flex flex-col gap-2 text-gray-700">
                                <Link v-for="c in g.items" :key="c[1]" :href="`/shop?category=${c[1]}`" class="hover:underline" @click="catOpen = false">{{ c[0] }}</Link>
                            </div>
                        </div>
                        <Link href="/shop?collection=new-arrivals" @click="catOpen = false"
                              class="ml-auto relative w-56 rounded-lg overflow-hidden group">
                            <img src="https://images.unsplash.com/photo-1583496661160-fb5886a0aaaa?q=75&w=500&auto=format&fit=crop" alt="" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-300" />
                            <div class="absolute inset-0 bg-gradient-to-t from-navy-900/80 to-transparent"></div>
                            <div class="absolute bottom-3 left-3 text-white">
                                <div class="text-xs uppercase tracking-wider text-gold-light">Just landed</div>
                                <div class="font-bold">New arrivals →</div>
                            </div>
                        </Link>
                    </div>
                </div>
                <form @submit.prevent="search" class="flex-1 flex items-stretch gap-2">
                    <div class="flex-1 flex items-center border-2 border-navy-900 rounded-full overflow-hidden">
                        <svg class="w-5 h-5 ml-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M20 20l-3.5-3.5"/></svg>
                        <input v-model="q" type="search" placeholder="Search for anything"
                               class="flex-1 border-0 focus:ring-0 text-sm py-2.5 min-w-0" />
                        <select v-model="qCat" class="hidden lg:block border-0 border-l border-gray-300 text-xs text-gray-700 focus:ring-0 py-2.5 pr-8">
                            <option value="">All Categories</option>
                            <option v-for="n in nav.slice(1)" :key="n.slug" :value="n.slug">{{ n.label }}</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-full px-4 sm:px-6 md:px-12" aria-label="Search"><span class="hidden sm:inline">Search</span><svg class="w-5 h-5 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M20 20l-3.5-3.5"/></svg></button>
                </form>
            </div>
            <!-- Category strip -->
            <nav class="hidden md:block">
                <div class="max-w-7xl mx-auto px-4 flex gap-6 text-[13px] text-gray-700 h-9 items-center overflow-x-auto">
                    <Link href="/" class="hover:underline whitespace-nowrap">Home</Link>
                    <Link v-for="n in nav" :key="n.label" :href="n.href" class="hover:underline whitespace-nowrap">{{ n.label }}</Link>
                    <Link href="/shop?sort=price_asc" class="hover:underline whitespace-nowrap text-red-600 font-semibold">Deals</Link>
                </div>
            </nav>
            <nav v-if="mobileOpen" class="md:hidden border-t border-gray-100 px-4 py-3 flex flex-col gap-3 text-sm bg-white">
                <Link v-for="n in nav" :key="n.label" :href="n.href" @click="mobileOpen = false">{{ n.label }}</Link>
            </nav>
        </header>

        <div v-if="flash" class="bg-emerald-600 text-white text-sm text-center py-2">{{ flash }}</div>

        <main class="flex-1"><slot /></main>

        <!-- Footer -->
        <footer class="bg-gray-50 border-t border-gray-200 mt-16 text-navy-900">
            <div class="max-w-7xl mx-auto px-4 py-10 grid grid-cols-2 md:grid-cols-5 gap-8 text-xs">
                <div>
                    <div class="font-bold text-sm mb-3">Buy</div>
                    <div class="flex flex-col gap-2 text-gray-600">
                        <Link href="/account/register" class="hover:underline">Registration</Link>
                        <Link href="/shop" class="hover:underline">Shop all</Link>
                        <Link href="/shop?collection=new-arrivals" class="hover:underline">New arrivals</Link>
                        <Link href="/shop?collection=best-sellers" class="hover:underline">Best sellers</Link>
                        <Link href="/shop?sort=price_asc" class="hover:underline">Daily deals</Link>
                    </div>
                </div>
                <div>
                    <div class="font-bold text-sm mb-3">Categories</div>
                    <div class="flex flex-col gap-2 text-gray-600">
                        <Link v-for="n in nav.slice(1)" :key="n.label" :href="n.href" class="hover:underline">{{ n.label }}</Link>
                    </div>
                </div>
                <div>
                    <div class="font-bold text-sm mb-3">My QUEENSONS</div>
                    <div class="flex flex-col gap-2 text-gray-600">
                        <Link href="/account" class="hover:underline">Summary</Link>
                        <Link href="/account/orders" class="hover:underline">Purchases</Link>
                        <Link href="/account/wishlist" class="hover:underline">Watchlist</Link>
                        <Link href="/cart" class="hover:underline">Cart</Link>
                    </div>
                </div>
                <div>
                    <div class="font-bold text-sm mb-3">Help &amp; Contact</div>
                    <div class="flex flex-col gap-2 text-gray-600">
                        <Link href="/page/shipping" class="hover:underline">Shipping</Link>
                        <Link href="/page/returns" class="hover:underline">Returns</Link>
                        <Link href="/page/faq" class="hover:underline">FAQ</Link>
                        <a :href="socials.whatsapp || '#'" target="_blank" rel="noopener" class="hover:underline">Chat on WhatsApp</a>
                    </div>
                </div>
                <div>
                    <div class="font-bold text-sm mb-3">About QUEENSONS</div>
                    <div class="flex flex-col gap-2 text-gray-600">
                        <Link href="/page/about" class="hover:underline">Company info</Link>
                        <Link href="/page/terms" class="hover:underline">Terms</Link>
                        <Link href="/page/privacy" class="hover:underline">Privacy</Link>
                        <a :href="socials.instagram || '#'" target="_blank" rel="noopener" class="hover:underline">Instagram</a>
                        <a :href="socials.tiktok || '#'" target="_blank" rel="noopener" class="hover:underline">TikTok</a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-200">
                <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col md:flex-row items-center justify-between gap-3 text-[11px] text-gray-500">
                    <span>Copyright © {{ new Date().getFullYear() }} QUEENSONS. All rights reserved. Powered by STACKGee Technologies.</span>
                    <div class="flex items-center gap-2" aria-label="Payment methods">
                        <span class="border border-gray-300 bg-white rounded px-2 py-0.5 font-semibold text-green-700">M-PESA</span>
                        <span class="border border-gray-300 bg-white rounded px-2 py-0.5 font-semibold">VISA</span>
                        <span class="border border-gray-300 bg-white rounded px-2 py-0.5 font-semibold">Mastercard</span>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Social-proof toast -->
        <transition name="toast">
            <div v-if="toast" class="fixed bottom-6 left-6 z-50 bg-white/95 backdrop-blur border border-gold/40 shadow-xl rounded-2xl px-4 py-3 flex items-center gap-3 max-w-xs">
                <div class="w-9 h-9 rounded-full bg-navy-800 flex items-center justify-center shrink-0">
                    <img src="/logo.svg" alt="" class="w-5 h-5" />
                </div>
                <div class="text-xs leading-snug">
                    <span class="font-semibold text-navy-800">{{ toast.name }}</span>
                    <span class="text-gray-500">&nbsp;in {{ toast.city }} bought&nbsp;</span>
                    <span class="font-medium text-navy-700">{{ toast.product }}</span>
                    <div class="text-[10px] text-gray-400 mt-0.5">{{ toast.mins }} min ago · <span class="text-gold-dark">✦ verified order</span></div>
                </div>
                <button @click="toast = null" class="text-gray-300 hover:text-gray-500 shrink-0" aria-label="Dismiss">✕</button>
            </div>
        </transition>

        <!-- WhatsApp chat float -->
        <a v-if="socials.whatsapp" :href="socials.whatsapp" target="_blank" rel="noopener"
           class="fixed bottom-6 right-6 z-40 w-12 h-12 rounded-full bg-emerald-600 text-white shadow-xl flex items-center justify-center hover:bg-emerald-700 hover:scale-110 transition"
           :class="{ 'bottom-20': scrolled }"
           aria-label="Chat with us on WhatsApp">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm4.5 13.1c-.2.6-1.2 1.1-1.7 1.2-.8.1-1.4.1-3-.6a11.2 11.2 0 01-4.3-3.8 4.9 4.9 0 01-1-2.6 2.8 2.8 0 01.9-2.1c.2-.2.5-.3.7-.3h.5c.2 0 .4-.1.6.4l.8 1.8c.1.2.1.3.1.5-.4.9-.9.9-.6 1.3a6.7 6.7 0 003.3 2.9c.2.1.4.1.5-.1.2-.2.6-.8.8-1 .2-.2.4-.2.6-.1.2.1 1.5.7 1.7.8.2.1.4.2.4.3a2 2 0 01-.3 1.4z"/></svg>
        </a>

        <!-- Back to top with gold progress ring -->
        <transition name="toast">
            <button v-if="scrolled" @click="toTop"
                    class="fixed bottom-6 right-6 z-50 w-12 h-12 rounded-full bg-navy-800 text-gold shadow-xl flex items-center justify-center hover:bg-navy-700 transition"
                    aria-label="Back to top">
                <svg class="absolute inset-0 w-12 h-12 -rotate-90" viewBox="0 0 48 48" aria-hidden="true">
                    <circle cx="24" cy="24" r="21" fill="none" stroke="rgba(197,166,106,.25)" stroke-width="2" />
                    <circle cx="24" cy="24" r="21" fill="none" stroke="#FF5FA2" stroke-width="2" stroke-linecap="round"
                            :stroke-dasharray="2 * Math.PI * 21" :stroke-dashoffset="2 * Math.PI * 21 * (1 - scrollPct)" />
                </svg>
                <svg class="w-4 h-4 relative" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
            </button>
        </transition>
    </div>
</template>

<style scoped>
.ann-enter-active, .ann-leave-active { transition: opacity .4s ease, transform .4s ease; }
.ann-enter-from { opacity: 0; transform: translateY(8px); }
.ann-leave-to { opacity: 0; transform: translateY(-8px); }
.toast-enter-active, .toast-leave-active { transition: opacity .4s ease, transform .4s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(16px); }
</style>
