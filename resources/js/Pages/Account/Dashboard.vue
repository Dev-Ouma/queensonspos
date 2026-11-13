<script setup>
import AccountShell from '@/Components/AccountShell.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { kes } from '@/money';

const props = defineProps({ customer: Object, recentOrders: Array, wishlistCount: Number });
const form = useForm({ name: props.customer.name, phone: props.customer.phone, password: '', password_confirmation: '' });
</script>

<template>
    <Head title="My QUEENSONS" />
    <AccountShell>
        <p class="text-lg">Hi <b>{{ customer.name.split(' ')[0] }}</b>, welcome back.</p>
        <div class="grid sm:grid-cols-3 gap-4 mt-5">
            <Link href="/account/orders" class="border border-gray-200 rounded-2xl p-5 hover:border-navy-900">
                <div class="text-sm text-gray-500">Purchases</div>
                <div class="text-2xl font-bold mt-1">{{ recentOrders.length }}<span class="text-sm font-normal text-gray-500"> recent</span></div>
            </Link>
            <Link href="/account/wishlist" class="border border-gray-200 rounded-2xl p-5 hover:border-navy-900">
                <div class="text-sm text-gray-500">Watchlist</div>
                <div class="text-2xl font-bold mt-1">{{ wishlistCount }}<span class="text-sm font-normal text-gray-500"> items</span></div>
            </Link>
            <div class="border border-gray-200 rounded-2xl p-5">
                <div class="text-sm text-gray-500">Loyalty points</div>
                <div class="text-2xl font-bold mt-1 text-gold-dark">{{ customer.loyalty_points }}</div>
            </div>
        </div>

        <div class="mt-10 flex items-end justify-between">
            <h2 class="text-xl font-bold">Recent purchases</h2>
            <Link href="/account/orders" class="text-sm underline">See all</Link>
        </div>
        <div v-if="recentOrders.length" class="mt-3 border border-gray-200 rounded-2xl divide-y divide-gray-200">
            <Link v-for="o in recentOrders" :key="o.reference" :href="`/account/orders/${o.reference}`"
                  class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm hover:bg-gray-50">
                <div>
                    <div class="font-bold">Order {{ o.reference }}</div>
                    <div class="text-xs text-gray-500">Placed {{ o.placed_at }}</div>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold capitalize">{{ o.status.replaceAll('_', ' ') }}</span>
                <span class="font-bold">{{ kes(o.total_cents) }}</span>
            </Link>
        </div>
        <p v-else class="mt-3 text-sm text-gray-500">No purchases yet. <Link href="/shop" class="underline">Start shopping</Link></p>

        <h2 class="text-xl font-bold mt-10">Personal info</h2>
        <form @submit.prevent="form.patch('/account/profile')" class="mt-3 border border-gray-200 rounded-2xl p-5 grid md:grid-cols-2 gap-4 max-w-2xl">
            <label class="block text-sm">Name<input v-model="form.name" autocomplete="name" class="w-full rounded-lg border-gray-300 text-sm mt-1" /></label>
            <label class="block text-sm">Phone<input v-model="form.phone" autocomplete="tel" class="w-full rounded-lg border-gray-300 text-sm mt-1" /></label>
            <label class="block text-sm">New password (optional)<input v-model="form.password" type="password" autocomplete="new-password" class="w-full rounded-lg border-gray-300 text-sm mt-1" /></label>
            <label class="block text-sm">Confirm password<input v-model="form.password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-lg border-gray-300 text-sm mt-1" /></label>
            <button :disabled="form.processing" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full px-6 py-2.5 text-sm font-semibold w-fit">Save changes</button>
        </form>
    </AccountShell>
</template>
