<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { kes } from '@/money';

const props = defineProps({ stats: Object, salesChart: Array, topProducts: Array, lowStock: Array, searches: Array });

const maxVal = computed(() => Math.max(1, ...props.salesChart.map(d => d.online + d.pos)));
</script>

<template>
    <Head title="Dashboard — QUEENSONS Admin" />
    <AdminLayout>
        <h1 class="text-xl font-bold text-navy-700 mb-6">Business Dashboard</h1>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- staggered entrance handled by v-reveal on wrapper cards -->
            <StatCard label="Sales Today" :value="kes(stats.sales_today_cents)" accent="emerald" />
            <StatCard label="Sales This Month" :value="kes(stats.sales_month_cents)" />
            <StatCard label="Gross Profit (Month)" :value="kes(stats.gross_profit_month_cents)" accent="gold" />
            <StatCard label="Avg Order Value" :value="kes(stats.aov_cents)" :sub="`${stats.orders_month} paid orders`" />
            <StatCard label="Customers" :value="String(stats.customers)" />
            <StatCard label="Products" :value="String(stats.products)" />
            <StatCard label="Pending Fulfilment" :value="String(stats.pending_orders)" accent="emerald" />
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- 14-day sales chart -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-semibold text-sm mb-4">Sales — last 14 days (KES)</h2>
                <div class="flex items-end gap-1.5 h-48">
                    <div v-for="d in salesChart" :key="d.date" class="flex-1 flex flex-col items-center gap-1 group">
                        <div class="w-full flex flex-col justify-end h-40 relative">
                            <div class="bg-emerald-600 rounded-t" :style="{ height: `${(d.pos / maxVal) * 100}%` }" :title="`POS: ${d.pos}`"></div>
                            <div class="bg-navy-700" :style="{ height: `${(d.online / maxVal) * 100}%` }" :title="`Online: ${d.online}`"></div>
                        </div>
                        <span class="text-[9px] text-gray-400 rotate-45 origin-left whitespace-nowrap">{{ d.date }}</span>
                    </div>
                </div>
                <div class="flex gap-4 text-xs mt-6">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 bg-navy-700 rounded-sm inline-block"></span> Online</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 bg-emerald-600 rounded-sm inline-block"></span> POS</span>
                </div>
            </div>

            <!-- Low stock -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-semibold text-sm mb-4">Low Stock Alerts</h2>
                <div v-if="lowStock.length" class="space-y-2">
                    <div v-for="(l, i) in lowStock" :key="i" class="flex justify-between text-sm">
                        <div class="truncate">
                            <div class="truncate">{{ l.product }}</div>
                            <div class="text-xs text-gray-400">{{ l.sku }}</div>
                        </div>
                        <span :class="['font-bold', l.available <= 0 ? 'text-red-600' : 'text-amber-600']">{{ l.available }}</span>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-400">All stock levels healthy.</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6 mt-6">
        <!-- Top products -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 lg:col-span-2">
            <h2 class="font-semibold text-sm mb-4">Top Selling Products</h2>
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-400 uppercase"><th class="py-2">Product</th><th>Units</th><th class="text-right">Revenue</th></tr></thead>
                <tbody>
                    <tr v-for="p in topProducts" :key="p.name_snapshot" class="border-t border-gray-50">
                        <td class="py-2">{{ p.name_snapshot }}</td>
                        <td>{{ p.units }}</td>
                        <td class="text-right font-medium">{{ kes(p.revenue) }}</td>
                    </tr>
                    <tr v-if="!topProducts.length"><td colspan="3" class="py-4 text-center text-gray-400">No sales yet.</td></tr>
                </tbody>
            </table>
        </div>

        <!-- What shoppers searched for -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-semibold text-sm">Shoppers searched for</h2>
                <a href="/admin/search" class="text-xs text-emerald-600 font-semibold hover:underline whitespace-nowrap">All insights</a>
            </div>
            <ul v-if="searches?.length" class="text-sm space-y-2">
                <li v-for="s in searches" :key="s.term" class="flex justify-between gap-3">
                    <span class="truncate">{{ s.term }}<span v-if="+s.min_results === 0" class="ml-1.5 text-[11px] font-semibold text-red-600">no results</span></span>
                    <span class="text-gray-400 shrink-0">{{ s.n }}×</span>
                </li>
            </ul>
            <p v-else class="text-sm text-gray-400">No website searches this week.</p>
        </div>
        </div>
    </AdminLayout>
</template>
