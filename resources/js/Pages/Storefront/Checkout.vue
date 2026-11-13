<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { kes, img, fallbackImage } from '@/money';

const props = defineProps({
    lines: Array, subtotal_cents: Number, discount_cents: Number, delivery_cents: Number,
    total_cents: Number, coupon: String, customer: Object, addresses: Array,
});
const page = usePage();

const form = useForm({
    name: props.customer?.name || '',
    email: props.customer?.email || '',
    phone: props.customer?.phone || '',
    delivery_method: 'delivery',
    address_line1: '',
    city: 'Nairobi',
    payment_method: 'mpesa',
});
</script>

<template>
    <Head title="Checkout" />
    <StorefrontLayout>
        <div class="max-w-6xl mx-auto px-4 py-6">
            <h1 class="text-2xl md:text-3xl font-bold">Checkout</h1>
            <div v-if="page.props.errors.checkout" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-3 mt-4">{{ page.props.errors.checkout }}</div>

            <form @submit.prevent="form.post('/checkout')" class="mt-6 grid lg:grid-cols-3 gap-8 items-start">
                <div class="lg:col-span-2 space-y-4">
                    <!-- Contact -->
                    <section class="border border-gray-200 rounded-2xl p-5">
                        <h2 class="text-lg font-bold mb-4">Contact</h2>
                        <div class="grid md:grid-cols-2 gap-4">
                            <label class="block text-sm">Full name
                                <input v-model="form.name" required autocomplete="name" class="w-full rounded-lg border-gray-300 text-sm mt-1" />
                                <span v-if="form.errors.name" class="text-red-600 text-xs">{{ form.errors.name }}</span>
                            </label>
                            <label class="block text-sm">Phone number (M-Pesa)
                                <input v-model="form.phone" required autocomplete="tel" inputmode="tel" placeholder="07XX XXX XXX" class="w-full rounded-lg border-gray-300 text-sm mt-1" />
                                <span v-if="form.errors.phone" class="text-red-600 text-xs">{{ form.errors.phone }}</span>
                            </label>
                            <label class="block text-sm md:col-span-2">Email
                                <input v-model="form.email" type="email" required autocomplete="email" class="w-full rounded-lg border-gray-300 text-sm mt-1" />
                                <span v-if="form.errors.email" class="text-red-600 text-xs">{{ form.errors.email }}</span>
                            </label>
                        </div>
                    </section>

                    <!-- Delivery -->
                    <section class="border border-gray-200 rounded-2xl p-5">
                        <h2 class="text-lg font-bold mb-4">Delivery</h2>
                        <div class="grid sm:grid-cols-2 gap-3 mb-4">
                            <label v-for="o in [['delivery', 'Deliver to me', 'Nairobi next day, countrywide 2–4 days'], ['pickup', 'Click & collect', 'Free from our flagship store']]" :key="o[0]"
                                   :class="['flex gap-3 rounded-xl border-2 p-4 cursor-pointer text-sm', form.delivery_method === o[0] ? 'border-navy-900' : 'border-gray-200']">
                                <input type="radio" v-model="form.delivery_method" :value="o[0]" class="mt-0.5 text-navy-900 focus:ring-navy-900" />
                                <span><b>{{ o[1] }}</b><span class="block text-gray-500 text-xs mt-0.5">{{ o[2] }}</span></span>
                            </label>
                        </div>
                        <div v-if="form.delivery_method === 'delivery'" class="grid md:grid-cols-3 gap-4">
                            <label class="block text-sm md:col-span-2">Street address
                                <input v-model="form.address_line1" autocomplete="street-address" placeholder="Street, building, house no." class="w-full rounded-lg border-gray-300 text-sm mt-1" />
                                <span v-if="form.errors.address_line1" class="text-red-600 text-xs">{{ form.errors.address_line1 }}</span>
                            </label>
                            <label class="block text-sm">City / Town
                                <input v-model="form.city" autocomplete="address-level2" class="w-full rounded-lg border-gray-300 text-sm mt-1" />
                            </label>
                        </div>
                    </section>

                    <!-- Payment -->
                    <section class="border border-gray-200 rounded-2xl p-5">
                        <h2 class="text-lg font-bold mb-4">Pay with</h2>
                        <div class="space-y-3">
                            <label v-for="m in [['mpesa', 'M-Pesa', 'We send a payment prompt to your phone'], ['card', 'Debit or credit card', 'Visa and Mastercard'], ['bank_transfer', 'Bank transfer', 'Order ships once payment clears']]" :key="m[0]"
                                   :class="['flex items-center gap-3 rounded-xl border-2 p-4 text-sm cursor-pointer', form.payment_method === m[0] ? 'border-navy-900' : 'border-gray-200']">
                                <input type="radio" v-model="form.payment_method" :value="m[0]" class="text-navy-900 focus:ring-navy-900" />
                                <span><b>{{ m[1] }}</b><span class="block text-gray-500 text-xs mt-0.5">{{ m[2] }}</span></span>
                            </label>
                        </div>
                    </section>

                    <!-- Review items -->
                    <section class="border border-gray-200 rounded-2xl p-5">
                        <h2 class="text-lg font-bold mb-4">Review items</h2>
                        <div class="divide-y divide-gray-200">
                            <div v-for="l in lines" :key="l.variant_id" class="flex gap-4 py-3">
                                <img :src="img(l.image) || fallbackImage(l.variant_id, 200)" :alt="l.name" class="w-16 h-16 rounded-lg object-cover bg-gray-100" />
                                <div class="flex-1 text-sm">
                                    <div>{{ l.name }}</div>
                                    <div class="text-xs text-gray-500">{{ l.color }} / {{ l.size }} · Qty {{ l.qty }}</div>
                                </div>
                                <div class="font-bold text-sm">{{ kes(l.line_total_cents) }}</div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Summary -->
                <aside class="border border-gray-200 rounded-2xl p-5 lg:sticky lg:top-36">
                    <h2 class="text-lg font-bold">Order total</h2>
                    <div class="space-y-2 text-sm mt-4">
                        <div class="flex justify-between"><span>Items ({{ lines.reduce((a, l) => a + l.qty, 0) }})</span><span>{{ kes(subtotal_cents) }}</span></div>
                        <div v-if="discount_cents" class="flex justify-between text-green-700"><span>Discount</span><span>−{{ kes(discount_cents) }}</span></div>
                        <div class="flex justify-between"><span>Delivery</span><span>{{ form.delivery_method === 'pickup' || !delivery_cents ? 'Free' : kes(delivery_cents) }}</span></div>
                        <div class="flex justify-between font-bold text-lg border-t border-gray-200 pt-3 mt-3"><span>Order total</span><span>{{ kes(form.delivery_method === 'pickup' ? total_cents - delivery_cents : total_cents) }}</span></div>
                    </div>
                    <button :disabled="form.processing" class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-full py-3 font-semibold mt-5">
                        {{ form.processing ? 'Placing order…' : 'Confirm and pay' }}
                    </button>
                    <p class="text-xs text-gray-500 mt-3 leading-relaxed">By confirming, you agree to our <a href="/page/terms" class="underline">terms</a>. Payment is verified before dispatch.</p>
                </aside>
            </form>
        </div>
    </StorefrontLayout>
</template>
