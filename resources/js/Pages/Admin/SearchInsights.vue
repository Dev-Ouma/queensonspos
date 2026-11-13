<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ days: Number, stats: Object, top: Array, zero: Array, saved: Array, recent: Array });

const when = (d) => new Date(d).toLocaleString('en-KE', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
const shop = (term) => `/shop?q=${encodeURIComponent(term)}`;
const filterText = (f) => f ? Object.entries(f).map(([k, v]) => `${k.replace('_', ' ')}: ${v}`).join(' · ') : '';
</script>

<template>
    <Head title="Search Insights" />
    <AdminLayout>
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-xl font-bold">Search Insights</h1>
                <p class="text-sm text-gray-500">What shoppers search for on the website, and where the catalogue has gaps.</p>
            </div>
            <div class="flex gap-1 bg-white border border-gray-200 rounded-full p-1 text-xs">
                <Link v-for="d in [7, 30, 90]" :key="d" :href="`/admin/search?days=${d}`"
                      :class="['px-3 py-1 rounded-full', days === d ? 'bg-navy-900 text-white' : 'text-gray-600']">{{ d }} days</Link>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
            <div v-for="s in [
                { l: 'Searches', v: stats.searches },
                { l: 'Signed-in shoppers', v: stats.shoppers },
                { l: 'No-result rate', v: stats.zero_rate + '%' },
                { l: 'Read by AI', v: stats.ai_rate + '%' },
                { l: 'Saved searches', v: stats.saved },
            ]" :key="s.l" class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="text-xs text-gray-500">{{ s.l }}</div>
                <div class="text-2xl font-bold mt-1">{{ s.v }}</div>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <section class="bg-white rounded-xl border border-gray-200">
                <h2 class="font-semibold text-sm px-4 py-3 border-b border-gray-100">Top searches</h2>
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500"><tr><th class="text-left px-4 py-2">Term</th><th class="text-right px-4">Searches</th><th class="text-right px-4">Avg results</th></tr></thead>
                    <tbody>
                        <tr v-for="t in top" :key="t.term" class="border-t border-gray-50">
                            <td class="px-4 py-2"><a :href="shop(t.term)" target="_blank" class="hover:underline">{{ t.term }}</a></td>
                            <td class="text-right px-4">{{ t.searches }}</td>
                            <td class="text-right px-4" :class="+t.avg_results === 0 && 'text-red-600 font-semibold'">{{ t.avg_results }}</td>
                        </tr>
                        <tr v-if="!top.length"><td colspan="3" class="px-4 py-6 text-center text-gray-400">No searches yet.</td></tr>
                    </tbody>
                </table>
            </section>

            <section class="bg-white rounded-xl border border-gray-200">
                <h2 class="font-semibold text-sm px-4 py-3 border-b border-gray-100">Searches with no results <span class="text-gray-400 font-normal">· stock or tag ideas</span></h2>
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500"><tr><th class="text-left px-4 py-2">Term</th><th class="text-right px-4">Searches</th><th class="text-right px-4">Last</th></tr></thead>
                    <tbody>
                        <tr v-for="t in zero" :key="t.term" class="border-t border-gray-50">
                            <td class="px-4 py-2">{{ t.term }}</td>
                            <td class="text-right px-4">{{ t.searches }}</td>
                            <td class="text-right px-4 text-xs text-gray-500">{{ when(t.last_at) }}</td>
                        </tr>
                        <tr v-if="!zero.length"><td colspan="3" class="px-4 py-6 text-center text-gray-400">Every search found something.</td></tr>
                    </tbody>
                </table>
            </section>

            <section class="bg-white rounded-xl border border-gray-200">
                <h2 class="font-semibold text-sm px-4 py-3 border-b border-gray-100">Most saved searches</h2>
                <ul class="text-sm">
                    <li v-for="s in saved" :key="s.label" class="flex justify-between px-4 py-2 border-t border-gray-50 first:border-0">
                        <span>{{ s.label }}</span><span class="text-gray-500">{{ s.customers }} {{ s.customers == 1 ? 'shopper' : 'shoppers' }}</span>
                    </li>
                    <li v-if="!saved.length" class="px-4 py-6 text-center text-gray-400">No saved searches yet.</li>
                </ul>
            </section>

            <section class="bg-white rounded-xl border border-gray-200">
                <h2 class="font-semibold text-sm px-4 py-3 border-b border-gray-100">Latest searches</h2>
                <ul class="text-sm">
                    <li v-for="(r, i) in recent" :key="i" class="px-4 py-2 border-t border-gray-50 first:border-0">
                        <div class="flex justify-between gap-3">
                            <span class="font-medium">{{ r.term }}</span>
                            <span class="text-xs text-gray-500 whitespace-nowrap">{{ r.results }} results · {{ when(r.created_at) }}</span>
                        </div>
                        <div v-if="r.filters && Object.keys(r.filters).length" class="text-xs text-gray-500 mt-0.5">
                            <span :class="r.ai ? 'text-emerald-700 font-semibold' : 'text-gray-600 font-semibold'">{{ r.ai ? 'AI' : 'Smart' }}:</span> {{ filterText(r.filters) }}
                        </div>
                    </li>
                    <li v-if="!recent.length" class="px-4 py-6 text-center text-gray-400">No searches yet.</li>
                </ul>
            </section>
        </div>
    </AdminLayout>
</template>
