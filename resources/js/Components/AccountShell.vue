<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({ title: String });
const page = usePage();
const path = computed(() => page.url.split('?')[0]);
const links = [
    ['Summary', '/account'],
    ['Purchases', '/account/orders'],
    ['Watchlist', '/account/wishlist'],
    ['Saved searches', '/account/saved-searches'],
];
const isActive = (href) => (href === '/account' ? path.value === '/account' : path.value.startsWith(href));
</script>

<template>
    <StorefrontLayout>
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl md:text-3xl font-bold">My QUEENSONS</h1>
                <Link href="/account/logout" method="post" as="button" class="text-sm underline">Sign out</Link>
            </div>
            <nav class="mt-4 flex gap-6 border-b border-gray-200 text-sm overflow-x-auto" aria-label="Account">
                <Link v-for="l in links" :key="l[1]" :href="l[1]"
                      :class="['pb-3 -mb-px border-b-2 whitespace-nowrap', isActive(l[1]) ? 'border-navy-900 font-bold' : 'border-transparent text-gray-600 hover:text-navy-900']">{{ l[0] }}</Link>
            </nav>
            <h2 v-if="title" class="text-xl font-bold mt-6">{{ title }}</h2>
            <div class="mt-4"><slot /></div>
        </div>
    </StorefrontLayout>
</template>
