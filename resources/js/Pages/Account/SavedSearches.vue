<script setup>
import AccountShell from '@/Components/AccountShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';
defineProps({ searches: Array });
</script>

<template>
    <Head title="Saved searches" />
    <AccountShell :title="`Saved searches (${searches.length})`">
        <div v-if="searches.length" class="border border-gray-200 rounded-2xl divide-y divide-gray-200">
            <div v-for="s in searches" :key="s.id" class="p-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <Link :href="s.url" class="font-semibold hover:underline">{{ s.label }}</Link>
                    <div class="text-xs text-gray-500">Saved {{ s.saved_at }}</div>
                </div>
                <div class="flex gap-2">
                    <Link :href="s.url" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full px-5 py-2 text-sm font-semibold">See results</Link>
                    <button @click="router.delete(`/account/saved-searches/${s.id}`, { preserveScroll: true })"
                            class="border-2 border-gray-300 hover:border-navy-900 rounded-full px-5 py-2 text-sm font-semibold">Delete</button>
                </div>
            </div>
        </div>
        <div v-else class="border border-gray-200 rounded-2xl p-10 text-center">
            <div class="font-bold">No saved searches yet</div>
            <p class="text-sm text-gray-500 mt-1">Search or filter the shop, then tap "Save this search" to come back to it in one click.</p>
            <Link href="/shop" class="inline-block mt-4 bg-emerald-600 text-white rounded-full px-6 py-2.5 text-sm font-semibold">Start searching</Link>
        </div>
    </AccountShell>
</template>
