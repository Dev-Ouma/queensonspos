<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { kes, img, fallbackImage } from '@/money';

const props = defineProps({ lines: Array, subtotal_cents: Number, discount_cents: Number, delivery_cents: Number, total_cents: Number, coupon: String });
const page = usePage();
const couponForm = useForm({ code: '' });

const freeAt = computed(() => page.props.storefront?.free_delivery_threshold_cents || 0);
const itemCount = computed(() => props.lines.reduce((a, l) => a + l.qty, 0));
const freeProgress = computed(() => (freeAt.value ? Math.min(props.subtotal_cents / freeAt.value, 1) : 1));
const remaining = computed(() => Math.max(freeAt.value - props.subtotal_cents, 0));

function setQty(variantId, qty) {
    router.post('/cart/update', { variant_id: variantId, qty }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Your shopping cart" />
    <StorefrontLayout>
        <div class="max-w-6xl mx-auto px-4 py-6">
            <h1 class="text-2xl md:text-3xl font-bold">Shopping cart</h1>

            <div v-if="!lines.length" class="text-center py-20 border border-gray-200 rounded-2xl mt-6">
                <div class="text-lg font-bold">You don't have any items in your cart.</div>
                <p class="text-sm text-gray-500 mt-1">Have an account? Sign in to see your items.</p>
                <div class="flex justify-center gap-3 mt-6">
                    <Link href="/shop" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full px-8 py-3 text-sm font-semibold">Start shopping</Link>
                    <Link href="/account/login" class="border-2 border-emerald-600 text-emerald-700 rounded-full px-8 py-3 text-sm font-semibold">Sign in</Link>
                </div>
            </div>

            <div v-else class="mt-6 grid lg:grid-cols-3 gap-8 items-start">
                <div class="lg:col-span-2">
                    <!-- Free delivery progress -->
                    <div v-if="freeAt" class="border border-gray-200 rounded-2xl p-4 mb-4">
                        <p class="text-sm">
                            <template v-if="remaining > 0">Add <b>{{ kes(remaining) }}</b> more to get <b class="text-green-700">free delivery</b>.</template>
                            <template v-else><b class="text-green-700">Your order qualifies for free delivery.</b></template>
                        </p>
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden mt-2">
                            <div class="h-full bg-green-600 rounded-full transition-all duration-700" :style="{ width: `${freeProgress * 100}%` }"></div>
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-2xl divide-y divide-gray-200">
                        <div class="px-5 py-3 text-sm font-bold flex justify-between">
                            <span>Seller: QUEENSONS</span>
                            <Link href="/page/faq" class="font-normal underline">Contact</Link>
                        </div>
                        <div v-for="l in lines" :key="l.variant_id" class="p-5 flex gap-4">
                            <Link :href="`/product/${l.slug}`" class="w-24 h-24 md:w-32 md:h-32 bg-gray-100 rounded-xl shrink-0 overflow-hidden">
                                <img :src="img(l.image) || fallbackImage(l.variant_id)" :alt="l.name" class="w-full h-full object-cover" />
                            </Link>
                            <div class="flex-1 min-w-0 grid md:grid-cols-[1fr_auto] gap-3">
                                <div>
                                    <Link :href="`/product/${l.slug}`" class="text-sm md:text-base hover:underline">{{ l.name }}</Link>
                                    <div class="text-xs text-gray-500 mt-1">New with tags · {{ l.color }} / {{ l.size }}</div>
                                    <div v-if="l.qty > l.available" class="text-xs text-red-600 font-semibold mt-1">Only {{ l.available }} available</div>
                                    <div class="flex items-center gap-4 mt-3 text-sm">
                                        <label class="flex items-center gap-2">Qty
                                            <select :value="l.qty" @change="setQty(l.variant_id, +$event.target.value)" class="rounded-lg border-gray-300 text-sm py-1">
                                                <option v-for="n in Math.min(10, Math.max(l.available, l.qty))" :key="n" :value="n">{{ n }}</option>
                                            </select>
                                        </label>
                                        <button @click="setQty(l.variant_id, 0)" class="underline">Remove</button>
                                    </div>
                                </div>
                                <div class="md:text-right">
                                    <div class="text-lg font-bold">{{ kes(l.line_total_cents) }}</div>
                                    <div v-if="l.qty > 1" class="text-xs text-gray-500">{{ kes(l.price_cents) }} each</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <aside class="border border-gray-200 rounded-2xl p-5 lg:sticky lg:top-36">
                    <Link href="/checkout" class="block text-center bg-emerald-600 hover:bg-emerald-700 text-white rounded-full py-3 font-semibold">Go to checkout</Link>
                    <div class="space-y-2 text-sm mt-5">
                        <div class="flex justify-between"><span>Items ({{ itemCount }})</span><span>{{ kes(subtotal_cents) }}</span></div>
                        <div v-if="discount_cents" class="flex justify-between text-green-700"><span>Discount ({{ coupon }})</span><span>−{{ kes(discount_cents) }}</span></div>
                        <div class="flex justify-between"><span>Delivery</span><span :class="!delivery_cents && 'text-green-700 font-semibold'">{{ delivery_cents ? kes(delivery_cents) : 'Free' }}</span></div>
                        <div class="flex justify-between font-bold text-lg border-t border-gray-200 pt-3 mt-3"><span>Subtotal</span><span>{{ kes(total_cents) }}</span></div>
                    </div>
                    <form @submit.prevent="couponForm.post('/cart/coupon', { preserveScroll: true })" class="mt-4 flex gap-2">
                        <input v-model="couponForm.code" placeholder="Coupon code" aria-label="Coupon code" class="flex-1 min-w-0 rounded-full border-gray-300 text-sm" />
                        <button class="border-2 border-navy-900 rounded-full px-4 text-sm font-semibold">Apply</button>
                    </form>
                    <div v-if="page.props.errors.coupon" class="text-red-600 text-xs mt-1">{{ page.props.errors.coupon }}</div>
                    <p class="text-xs text-gray-500 mt-4 leading-relaxed">Pay securely with M-Pesa, Visa or Mastercard. Free 7-day returns on unworn items.</p>
                </aside>
            </div>
        </div>
    </StorefrontLayout>
</template>
