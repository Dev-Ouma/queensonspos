<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { kes } from '@/money';

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
    <Head title="Checkout — QUEENSONS" />
    <StorefrontLayout>
        <div class="max-w-5xl mx-auto px-4 py-10">
            <div class="mb-8">
                <p class="eyebrow text-gold-dark mb-1">Almost there</p>
                <h1 class="font-display text-3xl">Checkout</h1>
                <div class="flex items-center gap-2 mt-3 text-[11px] text-gray-400">
                    <span class="text-navy-700 font-semibold">Cart</span>
                    <span class="h-px w-6 bg-gold/60"></span>
                    <span class="text-navy-700 font-semibold">Details & Payment</span>
                    <span class="h-px w-6 bg-gray-200"></span>
                    <span>Confirmation</span>
                </div>
            </div>
            <div v-if="page.props.errors.checkout" class="bg-red-50 text-red-600 text-sm rounded-lg p-3 mb-6">{{ page.props.errors.checkout }}</div>

            <form @submit.prevent="form.post('/checkout')" class="grid lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    <section class="border border-gray-100 rounded-xl p-6">
                        <h2 class="font-semibold mb-4">1 · Contact Details</h2>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-gray-500">Full name</label>
                                <input v-model="form.name" required class="w-full rounded border-gray-200 text-sm mt-1" />
                                <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Phone (for M-Pesa & delivery)</label>
                                <input v-model="form.phone" required placeholder="07XX XXX XXX" class="w-full rounded border-gray-200 text-sm mt-1" />
                                <div v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500">Email</label>
                                <input v-model="form.email" type="email" required class="w-full rounded border-gray-200 text-sm mt-1" />
                                <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                            </div>
                        </div>
                    </section>

                    <section class="border border-gray-100 rounded-xl p-6">
                        <h2 class="font-semibold mb-4">2 · Delivery</h2>
                        <div class="flex gap-3 mb-4">
                            <button type="button" @click="form.delivery_method = 'delivery'"
                                    :class="['flex-1 rounded-lg border p-3 text-sm', form.delivery_method === 'delivery' ? 'border-emerald-600 bg-emerald-50' : 'border-gray-200']">
                                🚚 Home / Office Delivery
                            </button>
                            <button type="button" @click="form.delivery_method = 'pickup'"
                                    :class="['flex-1 rounded-lg border p-3 text-sm', form.delivery_method === 'pickup' ? 'border-emerald-600 bg-emerald-50' : 'border-gray-200']">
                                🏬 Store Pickup (Free)
                            </button>
                        </div>
                        <div v-if="form.delivery_method === 'delivery'" class="grid md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500">Address</label>
                                <input v-model="form.address_line1" placeholder="Street, building, house no." class="w-full rounded border-gray-200 text-sm mt-1" />
                                <div v-if="form.errors.address_line1" class="text-red-500 text-xs mt-1">{{ form.errors.address_line1 }}</div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">City / Town</label>
                                <input v-model="form.city" class="w-full rounded border-gray-200 text-sm mt-1" />
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500">Collect from QUEENSONS Flagship, Nairobi CBD. We'll notify you when ready.</p>
                    </section>

                    <section class="border border-gray-100 rounded-xl p-6">
                        <h2 class="font-semibold mb-4">3 · Payment</h2>
                        <div class="space-y-2">
                            <label v-for="m in [['mpesa', 'M-Pesa (STK Push)'], ['card', 'Card'], ['bank_transfer', 'Bank Transfer']]" :key="m[0]"
                                   :class="['flex items-center gap-3 rounded-lg border p-3 text-sm cursor-pointer', form.payment_method === m[0] ? 'border-emerald-600 bg-emerald-50' : 'border-gray-200']">
                                <input type="radio" v-model="form.payment_method" :value="m[0]" class="text-emerald-600" />
                                {{ m[1] }}
                            </label>
                        </div>
                    </section>
                </div>

                <div class="bg-gray-50 rounded-xl p-6 h-fit">
                    <h2 class="font-semibold mb-4">Order Summary</h2>
                    <div class="space-y-2 text-sm max-h-48 overflow-y-auto mb-3">
                        <div v-for="l in lines" :key="l.variant_id" class="flex justify-between gap-2">
                            <span class="text-gray-600 truncate">{{ l.qty }}× {{ l.name }} ({{ l.size }})</span>
                            <span class="shrink-0">{{ kes(l.line_total_cents) }}</span>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm border-t pt-3">
                        <div class="flex justify-between"><span>Subtotal</span><span>{{ kes(subtotal_cents) }}</span></div>
                        <div v-if="discount_cents" class="flex justify-between text-emerald-600"><span>Discount</span><span>−{{ kes(discount_cents) }}</span></div>
                        <div class="flex justify-between"><span>Delivery</span><span>{{ form.delivery_method === 'pickup' ? 'Free' : kes(delivery_cents) }}</span></div>
                        <div class="flex justify-between font-bold text-base border-t pt-3"><span>Total</span><span>{{ kes(form.delivery_method === 'pickup' ? total_cents - delivery_cents : total_cents) }}</span></div>
                    </div>
                    <button :disabled="form.processing" class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-full py-3 text-sm font-semibold mt-5 transition">
                        {{ form.processing ? 'Placing order…' : 'Place Order & Pay' }}
                    </button>
                    <p class="text-[11px] text-gray-400 mt-3 text-center">Secure checkout · Payment verified before dispatch</p>
                </div>
            </form>
        </div>
    </StorefrontLayout>
</template>
