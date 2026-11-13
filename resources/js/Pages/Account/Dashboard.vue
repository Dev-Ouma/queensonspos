<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { kes } from '@/money';

const props = defineProps({ customer: Object, recentOrders: Array, wishlistCount: Number });
const form = useForm({ name: props.customer.name, phone: props.customer.phone, password: '', password_confirmation: '' });
</script>

<template>
    <Head title="My Account — QUEENSONS" />
    <StorefrontLayout>
        <div class="max-w-5xl mx-auto px-4 py-12">
            <div class="flex items-center justify-between mb-8">
                <h1 class="font-display text-3xl">Hello, {{ customer.name.split(' ')[0] }}</h1>
                <Link href="/account/logout" method="post" as="button" class="text-sm text-gray-400 hover:text-red-500">Sign out</Link>
            </div>

            <div class="grid md:grid-cols-3 gap-4 mb-10">
                <Link href="/account/orders" class="bg-navy-50 rounded-xl p-5 hover:shadow transition">
                    <div class="text-xs uppercase text-gray-500">Orders</div>
                    <div class="text-xl font-bold text-navy-700 mt-1">View history →</div>
                </Link>
                <Link href="/account/wishlist" class="bg-navy-50 rounded-xl p-5 hover:shadow transition">
                    <div class="text-xs uppercase text-gray-500">Wishlist</div>
                    <div class="text-xl font-bold text-navy-700 mt-1">{{ wishlistCount }} saved</div>
                </Link>
                <div class="bg-gold/10 rounded-xl p-5 border border-gold/30">
                    <div class="text-xs uppercase text-gray-500">Loyalty points</div>
                    <div class="text-xl font-bold text-gold-dark mt-1">{{ customer.loyalty_points }} pts</div>
                </div>
            </div>

            <h2 class="font-semibold mb-4">Recent Orders</h2>
            <div v-if="recentOrders.length" class="space-y-3 mb-10">
                <Link v-for="o in recentOrders" :key="o.reference" :href="`/account/orders/${o.reference}`"
                      class="flex justify-between items-center border border-gray-100 rounded-xl p-4 hover:border-navy-700 transition text-sm">
                    <div>
                        <div class="font-mono font-semibold">{{ o.reference }}</div>
                        <div class="text-xs text-gray-400">{{ o.placed_at }}</div>
                    </div>
                    <span class="text-xs bg-navy-50 rounded-full px-3 py-1 capitalize">{{ o.status.replaceAll('_', ' ') }}</span>
                    <span class="font-semibold">{{ kes(o.total_cents) }}</span>
                </Link>
            </div>
            <p v-else class="text-gray-400 text-sm mb-10">No orders yet. <Link href="/shop" class="text-emerald-600 underline">Start shopping</Link></p>

            <h2 class="font-semibold mb-4">Profile</h2>
            <form @submit.prevent="form.patch('/account/profile')" class="grid md:grid-cols-2 gap-4 max-w-xl">
                <div>
                    <label class="text-xs text-gray-500">Name</label>
                    <input v-model="form.name" class="w-full rounded border-gray-200 text-sm mt-1" />
                </div>
                <div>
                    <label class="text-xs text-gray-500">Phone</label>
                    <input v-model="form.phone" class="w-full rounded border-gray-200 text-sm mt-1" />
                </div>
                <div>
                    <label class="text-xs text-gray-500">New password (optional)</label>
                    <input v-model="form.password" type="password" class="w-full rounded border-gray-200 text-sm mt-1" />
                </div>
                <div>
                    <label class="text-xs text-gray-500">Confirm password</label>
                    <input v-model="form.password_confirmation" type="password" class="w-full rounded border-gray-200 text-sm mt-1" />
                </div>
                <button :disabled="form.processing" class="bg-navy-700 text-white rounded-full px-6 py-2 text-sm w-fit">Save changes</button>
            </form>
        </div>
    </StorefrontLayout>
</template>
