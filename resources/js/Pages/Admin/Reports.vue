<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { kes } from '@/money';

const props = defineProps({ summary: Object, byProduct: Array, byMethod: Array, filters: Object });
const page = usePage();
const canExport = computed(() => (page.props.auth.user?.permissions || []).includes('reports.export'));
const f = reactive({ ...props.filters });
const apply = () => router.get('/admin/reports', f, { preserveState: true });
</script>

<template>
    <Head title="Reports — Admin" />
    <AdminLayout>
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <h1 class="text-xl font-bold text-navy-700">Financial Reports</h1>
            <div class="flex gap-2 items-center text-sm">
                <input v-model="f.from" type="date" @change="apply" class="rounded border-gray-200 text-sm" />
                <span class="text-gray-400">to</span>
                <input v-model="f.to" type="date" @change="apply" class="rounded border-gray-200 text-sm" />
                <a v-if="canExport" :href="`/admin/reports/export?from=${f.from}&to=${f.to}`"
                   class="bg-gold hover:bg-gold-dark text-navy-800 rounded-lg px-4 py-2 font-semibold">Export CSV</a>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <StatCard label="Online Revenue" :value="kes(summary.online_revenue_cents)" :sub="`${summary.online_orders} orders`" />
            <StatCard label="POS Revenue" :value="kes(summary.pos_revenue_cents)" :sub="`${summary.pos_sales} sales`" accent="emerald" />
            <StatCard label="Total Revenue" :value="kes(summary.total_revenue_cents)" />
            <StatCard label="Cost of Goods" :value="kes(summary.cogs_cents)" />
            <StatCard label="Gross Profit" :value="kes(summary.gross_profit_cents)" accent="gold" />
            <StatCard label="Approved Expenses" :value="kes(summary.expenses_cents)" />
            <StatCard label="Net Result" :value="kes(summary.net_result_cents)" :accent="summary.net_result_cents >= 0 ? 'emerald' : 'navy'" />
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-semibold text-sm mb-3">Product Performance (online, paid)</h2>
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs text-gray-400 uppercase"><th class="py-2">Product</th><th>Units</th><th class="text-right">Revenue</th><th class="text-right">COGS</th><th class="text-right">Margin</th></tr></thead>
                    <tbody>
                        <tr v-for="p in byProduct" :key="p.name_snapshot" class="border-t border-gray-50">
                            <td class="py-2">{{ p.name_snapshot }}</td>
                            <td>{{ p.units }}</td>
                            <td class="text-right">{{ kes(p.revenue_cents) }}</td>
                            <td class="text-right text-gray-500">{{ kes(p.cogs_cents) }}</td>
                            <td class="text-right font-medium text-emerald-700">{{ kes(p.revenue_cents - p.cogs_cents) }}</td>
                        </tr>
                        <tr v-if="!byProduct.length"><td colspan="5" class="py-4 text-center text-gray-400">No sales in period.</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-semibold text-sm mb-3">Payment Methods</h2>
                <div v-for="m in byMethod" :key="m.method" class="flex justify-between text-sm py-2 border-b border-gray-50">
                    <span class="uppercase text-xs font-semibold">{{ m.method }}</span>
                    <span>{{ m.count }}× · {{ kes(m.total_cents) }}</span>
                </div>
                <p v-if="!byMethod.length" class="text-sm text-gray-400">No payments in period.</p>
            </div>
        </div>
    </AdminLayout>
</template>
