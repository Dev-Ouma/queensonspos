<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { kes, img } from '@/money';

const props = defineProps({ lines: Array, subtotal_cents: Number, discount_cents: Number, delivery_cents: Number, total_cents: Number, coupon: String });
const page = usePage();
const couponForm = useForm({ code: '' });

const freeAt = computed(() => page.props.storefront?.free_delivery_threshold_cents || 0);
const freeProgress = computed(() => (freeAt.value ? Math.min(props.subtotal_cents / freeAt.value, 1) : 1));
const remaining = computed(() => Math.max(freeAt.value - props.subtotal_cents, 0));

function setQty(variantId, qty) {
    router.post('/cart/update', { variant_id: variantId, qty }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Cart — QUEENSONS" />
    <StorefrontLayout>
        <div class="max-w-5xl mx-auto px-4 py-10">
            <h1 class="font-display text-3xl mb-8">Shopping Cart</h1>

            <div v-if="!lines.length" class="text-center py-20">
                <p class="text-gray-400 mb-4">Your cart is empty.</p>
                <Link href="/shop" class="bg-navy-700 text-white rounded-full px-8 py-3 text-sm">Continue shopping</Link>
            </div>

            <div v-else class="grid lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-4">
                    <div v-for="l in lines" :key="l.variant_id" class="flex gap-4 border border-gray-100 rounded-xl p-4">
                        <Link :href="`/product/${l.slug}`" class="w-20 h-24 bg-navy-50 rounded-lg shrink-0 overflow-hidden flex items-center justify-center">
                            <img v-if="l.image" :src="img(l.image)" class="w-full h-full object-cover" />
                            <span v-else class="font-display text-navy-100 text-xl">Q</span>
                        </Link>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm">{{ l.name }}</div>
                            <div class="text-xs text-gray-400">{{ l.color }} / {{ l.size }} · {{ l.sku }}</div>
                            <div class="text-sm font-semibold text-navy-700 mt-1">{{ kes(l.price_cents) }}</div>
                            <div v-if="l.qty > l.available" class="text-xs text-red-500 mt-1">Only {{ l.available }} available</div>
                            <div class="flex items-center gap-3 mt-2">
                                <select :value="l.qty" @change="setQty(l.variant_id, +$event.target.value)" class="rounded border-gray-200 text-sm py-1">
                                    <option v-for="n in Math.min(10, Math.max(l.available, l.qty))" :key="n" :value="n">{{ n }}</option>
                                </select>
                                <button @click="setQty(l.variant_id, 0)" class="text-xs text-gray-400 hover:text-red-500 underline">Remove</button>
                            </div>
                        </div>
                        <div class="font-semibold text-sm">{{ kes(l.line_total_cents) }}</div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-6 h-fit">
                    <!-- Free delivery progress -->
                    <div v-if="freeAt" class="mb-5">
                        <p class="text-xs mb-2">
                            <template v-if="remaining > 0">
                                You're <span class="font-semibold text-navy-700">{{ kes(remaining) }}</span> away from <span class="text-gold-dark font-semibold">free delivery</span> ✦
                            </template>
                            <template v-else>
                                <span class="text-emerald-600 font-semibold">✦ You've unlocked free delivery!</span>
                            </template>
                        </p>
                        <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-gold-dark to-gold rounded-full transition-all duration-700"
                                 :style="{ width: `${freeProgress * 100}%` }"></div>
                        </div>
                    </div>

                    <h2 class="font-semibold mb-4">Order Summary</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span>Subtotal</span><span>{{ kes(subtotal_cents) }}</span></div>
                        <div v-if="discount_cents" class="flex justify-between text-emerald-600"><span>Discount ({{ coupon }})</span><span>−{{ kes(discount_cents) }}</span></div>
                        <div class="flex justify-between"><span>Delivery</span><span>{{ delivery_cents ? kes(delivery_cents) : 'Free' }}</span></div>
                        <div class="flex justify-between font-bold text-base border-t pt-3 mt-3"><span>Total</span><span>{{ kes(total_cents) }}</span></div>
                    </div>

                    <form @submit.prevent="couponForm.post('/cart/coupon', { preserveScroll: true })" class="mt-4 flex gap-2">
                        <input v-model="couponForm.code" placeholder="Promo code" class="flex-1 rounded border-gray-200 text-sm" />
                        <button class="bg-gold hover:bg-gold-dark text-navy-800 rounded px-4 text-sm font-semibold">Apply</button>
                    </form>
                    <div v-if="page.props.errors.coupon" class="text-red-500 text-xs mt-1">{{ page.props.errors.coupon }}</div>

                    <Link href="/checkout" class="sheen block text-center bg-emerald-600 hover:bg-emerald-700 text-white rounded-full py-3 text-sm font-semibold mt-5 transition">
                        Proceed to Checkout
                    </Link>
                    <div class="flex items-center justify-center gap-2 mt-4 text-[10px] text-gray-400">
                        <svg class="w-3.5 h-3.5 text-gold-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.8" d="M12 11V7a4 4 0 118 0m-4 8v2m-8 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H8a2 2 0 00-2 2v6a2 2 0 002 2z" transform="translate(-2)"/></svg>
                        Secure checkout · M-PESA, card or cash on delivery
                    </div>
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
