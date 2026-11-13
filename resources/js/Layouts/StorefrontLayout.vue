<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';

const page = usePage();
const cartCount = computed(() => page.props.cartCount || 0);
const customer = computed(() => page.props.auth?.customer);
const flash = computed(() => page.props.flash?.success);
const q = ref('');
const mobileOpen = ref(false);

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
    { label: 'Dresses', href: '/shop?category=dresses' },
    { label: 'Tops', href: '/shop?category=tops-blouses' },
    { label: 'Office Wear', href: '/shop?category=office-wear' },
    { label: 'Shoes', href: '/shop?category=shoes' },
    { label: 'Handbags', href: '/shop?category=handbags' },
    { label: 'Accessories', href: '/shop?category=accessories' },
];

function search() {
    router.get('/shop', { q: q.value });
}
</script>

<template>
    <div class="min-h-screen bg-white font-sans text-navy-900">
        <!-- Top bar: rotating announcements -->
        <div class="bg-navy-700 text-white text-xs text-center py-2 tracking-wide overflow-hidden" aria-live="polite">
            <transition name="ann" mode="out-in">
                <span :key="annIndex" class="inline-flex items-center gap-2">
                    <span class="text-gold" aria-hidden="true">✦</span>{{ announcements[annIndex] }}
                </span>
            </transition>
        </div>

        <!-- Header -->
        <header class="border-b border-gray-100 sticky top-0 bg-white/90 backdrop-blur-md z-40">
            <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16 gap-4">
                <button class="lg:hidden" @click="mobileOpen = !mobileOpen" aria-label="Menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <Link href="/" class="flex items-center gap-2.5">
                    <img src="/logo.svg" alt="QUEENSONS crown logo" class="w-9 h-9" />
                    <span class="font-display text-2xl tracking-widest text-navy-700">QUEENSONS</span>
                </Link>
                <nav class="hidden lg:flex gap-6 text-sm font-medium">
                    <Link v-for="n in nav" :key="n.label" :href="n.href" class="nav-link transition">{{ n.label }}</Link>
                </nav>
                <div class="flex items-center gap-4">
                    <form @submit.prevent="search" class="hidden md:block">
                        <input v-model="q" type="search" placeholder="Search…"
                               class="rounded-full border-gray-200 text-sm w-40 focus:w-56 transition-all focus:border-emerald-600 focus:ring-emerald-600" />
                    </form>
                    <Link :href="customer ? '/account' : '/account/login'" class="text-sm hover:text-emerald-600" aria-label="Account">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.6" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </Link>
                    <Link href="/cart" class="relative" aria-label="Cart">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.6" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 4.6A1 1 0 005.6 19H19M9 22a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/></svg>
                        <span v-if="cartCount" class="absolute -top-2 -right-2 bg-emerald-600 text-white text-[10px] rounded-full w-5 h-5 flex items-center justify-center">{{ cartCount }}</span>
                    </Link>
                </div>
            </div>
            <nav v-if="mobileOpen" class="lg:hidden border-t border-gray-100 px-4 py-3 flex flex-col gap-3 text-sm bg-white">
                <Link v-for="n in nav" :key="n.label" :href="n.href" @click="mobileOpen = false">{{ n.label }}</Link>
            </nav>
        </header>

        <div v-if="flash" class="bg-emerald-600 text-white text-sm text-center py-2">{{ flash }}</div>

        <main><slot /></main>

        <!-- Footer -->
        <footer class="bg-navy-800 text-white mt-20">
            <div class="rule-gold"></div>
            <div class="max-w-7xl mx-auto px-4 py-12 grid grid-cols-2 md:grid-cols-4 gap-8 text-sm">
                <div class="col-span-2 md:col-span-1">
                    <div class="flex items-center gap-2 mb-3">
                        <img src="/logo.svg" alt="" class="w-8 h-8" />
                        <span class="font-display text-xl tracking-widest">QUEENSONS</span>
                    </div>
                    <p class="text-navy-100 text-xs leading-relaxed">Affordable premium women's fashion. Style Beyond Seasons.</p>
                    <div class="flex gap-3 mt-4" aria-label="Social media">
                        <a :href="socials.instagram || '#'" target="_blank" rel="noopener" aria-label="Instagram" class="w-8 h-8 rounded-full border border-navy-600 hover:border-gold hover:text-gold flex items-center justify-center transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5" stroke-width="1.6"/><circle cx="12" cy="12" r="4" stroke-width="1.6"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"/></svg>
                        </a>
                        <a :href="socials.tiktok || '#'" target="_blank" rel="noopener" aria-label="TikTok" class="w-8 h-8 rounded-full border border-navy-600 hover:border-gold hover:text-gold flex items-center justify-center transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M16.6 5.82A4.28 4.28 0 0115.54 3h-3.09v12.4a2.59 2.59 0 11-1.59-2.4V9.84a5.76 5.76 0 00-1-.09 5.66 5.66 0 105.66 5.66V9.35a7.35 7.35 0 004.31 1.38V7.64a4.28 4.28 0 01-3.23-1.82z"/></svg>
                        </a>
                        <a :href="socials.whatsapp || '#'" target="_blank" rel="noopener" aria-label="WhatsApp" class="w-8 h-8 rounded-full border border-navy-600 hover:border-gold hover:text-gold flex items-center justify-center transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm0 18.2a8.2 8.2 0 01-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1112 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.2-.6.8-.8 1-.1.2-.3.2-.5.1a6.7 6.7 0 01-3.3-2.9c-.3-.4.2-.4.6-1.3 0-.2 0-.3-.1-.5l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3a2.8 2.8 0 00-.9 2.1 4.9 4.9 0 001 2.6 11.2 11.2 0 004.3 3.8c1.6.7 2.2.7 3 .6a2.5 2.5 0 001.7-1.2 2 2 0 00.1-1.2c0-.1-.2-.2-.4-.3z"/></svg>
                        </a>
                    </div>
                </div>
                <div>
                    <div class="font-semibold mb-3 text-gold">Shop</div>
                    <div class="flex flex-col gap-2 text-navy-100">
                        <Link href="/shop?collection=new-arrivals">New Arrivals</Link>
                        <Link href="/shop?collection=best-sellers">Best Sellers</Link>
                        <Link href="/shop">All Products</Link>
                    </div>
                </div>
                <div>
                    <div class="font-semibold mb-3 text-gold">Help</div>
                    <div class="flex flex-col gap-2 text-navy-100">
                        <Link href="/page/shipping">Shipping</Link>
                        <Link href="/page/returns">Returns</Link>
                        <Link href="/page/faq">FAQ</Link>
                    </div>
                </div>
                <div>
                    <div class="font-semibold mb-3 text-gold">Company</div>
                    <div class="flex flex-col gap-2 text-navy-100">
                        <Link href="/page/about">About Us</Link>
                        <Link href="/page/terms">Terms</Link>
                        <Link href="/page/privacy">Privacy</Link>
                    </div>
                </div>
            </div>
            <div class="border-t border-navy-600">
                <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col md:flex-row items-center justify-between gap-3 text-xs text-navy-100">
                    <span>© {{ new Date().getFullYear() }} QUEENSONS · Powered by STACKGee Technologies</span>
                    <div class="flex items-center gap-2" aria-label="Payment methods">
                        <span class="eyebrow text-[9px] text-navy-100/70 mr-1">We accept</span>
                        <span class="border border-navy-600 rounded px-2 py-0.5 text-[10px] font-semibold text-emerald-400">M-PESA</span>
                        <span class="border border-navy-600 rounded px-2 py-0.5 text-[10px] font-semibold">VISA</span>
                        <span class="border border-navy-600 rounded px-2 py-0.5 text-[10px] font-semibold">Mastercard</span>
                        <span class="border border-navy-600 rounded px-2 py-0.5 text-[10px] font-semibold text-gold-light">Cash</span>
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
                    <circle cx="24" cy="24" r="21" fill="none" stroke="#C5A66A" stroke-width="2" stroke-linecap="round"
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
