<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({ title: String });
const page = usePage();
const path = computed(() => page.url.split('?')[0]);

const activity = [
    ['Summary', '/account'],
    ['Recently viewed', '/account/recently-viewed'],
    ['Purchases', '/account/orders'],
    ['Watchlist', '/account/wishlist'],
    ['Saved searches', '/account/saved-searches'],
];
const account = [
    ['Personal info', '/account/settings#personal'],
    ['Addresses', '/account/settings#addresses'],
    ['Sign in and security', '/account/settings#security'],
];

const onAccountTab = computed(() => path.value.startsWith('/account/settings'));
const side = computed(() => (onAccountTab.value ? account : activity));
const isActive = (href) => {
    const p = href.split('#')[0];
    return p === '/account' ? path.value === '/account' : path.value.startsWith(p) && !href.includes('#');
};
const first = computed(() => page.props.auth?.customer?.name?.split(' ')[0]);
</script>

<template>
    <StorefrontLayout>
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold">My QUEENSONS</h1>
                    <p v-if="first" class="text-sm text-gray-600 mt-0.5">Hi {{ first }}</p>
                </div>
                <Link href="/account/logout" method="post" as="button" class="text-sm underline">Sign out</Link>
            </div>

            <nav class="mt-5 flex gap-8 border-b border-gray-200 text-sm" aria-label="My QUEENSONS">
                <Link href="/account" :class="['pb-3 -mb-px border-b-2', !onAccountTab ? 'border-navy-900 font-bold' : 'border-transparent text-gray-600 hover:text-navy-900']">Activity</Link>
                <Link href="/account/settings" :class="['pb-3 -mb-px border-b-2', onAccountTab ? 'border-navy-900 font-bold' : 'border-transparent text-gray-600 hover:text-navy-900']">Account</Link>
            </nav>

            <div class="mt-6 grid lg:grid-cols-[200px_1fr] gap-8">
                <aside>
                    <nav class="flex lg:flex-col gap-1 overflow-x-auto text-sm -mx-1 px-1" aria-label="Section">
                        <Link v-for="l in side" :key="l[1]" :href="l[1]"
                              :class="['whitespace-nowrap rounded-full lg:rounded-lg px-3 py-2',
                                       isActive(l[1]) ? 'bg-gray-100 font-bold' : 'hover:bg-gray-50 text-gray-700']">{{ l[0] }}</Link>
                    </nav>
                </aside>
                <div class="min-w-0">
                    <h2 v-if="title" class="text-xl md:text-2xl font-bold mb-4">{{ title }}</h2>
                    <slot />
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
