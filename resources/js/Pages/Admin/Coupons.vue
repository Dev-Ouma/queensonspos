<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { kes } from '@/money';

defineProps({ coupons: Array });
const form = useForm({ code: '', type: 'percent', value: 10, min_order_cents: 0, max_uses: null, starts_at: null, ends_at: null });
</script>

<template>
    <Head title="Coupons — Admin" />
    <AdminLayout>
        <h1 class="text-xl font-bold text-navy-700 mb-6">Promotions & Coupons</h1>

        <form @submit.prevent="form.post('/admin/coupons', { onSuccess: () => form.reset() })"
              class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end text-sm">
            <input v-model="form.code" required placeholder="CODE" class="rounded border-gray-200 uppercase w-32" />
            <select v-model="form.type" class="rounded border-gray-200">
                <option value="percent">% off</option>
                <option value="fixed">KES off</option>
                <option value="free_delivery">Free delivery</option>
            </select>
            <input v-if="form.type !== 'free_delivery'" v-model.number="form.value" type="number" min="0" :placeholder="form.type === 'percent' ? '%' : 'KES'" class="rounded border-gray-200 w-24" />
            <input v-model="form.starts_at" type="date" class="rounded border-gray-200" title="Starts" />
            <input v-model="form.ends_at" type="date" class="rounded border-gray-200" title="Ends" />
            <input v-model.number="form.max_uses" type="number" min="1" placeholder="Max uses" class="rounded border-gray-200 w-28" />
            <button :disabled="form.processing" class="bg-emerald-600 text-white rounded-lg px-4 py-2 font-semibold">Create</button>
        </form>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-400 uppercase border-b"><th class="p-3">Code</th><th>Type</th><th>Value</th><th>Min Order</th><th>Used</th><th>Window</th><th>Status</th></tr></thead>
                <tbody>
                    <tr v-for="c in coupons" :key="c.id" class="border-b border-gray-50">
                        <td class="p-3 font-mono font-semibold">{{ c.code }}</td>
                        <td class="text-xs capitalize">{{ c.type.replaceAll('_', ' ') }}</td>
                        <td>{{ c.type === 'percent' ? c.value + '%' : c.type === 'fixed' ? kes(c.value) : '—' }}</td>
                        <td>{{ c.min_order_cents ? kes(c.min_order_cents) : '—' }}</td>
                        <td>{{ c.used_count }}{{ c.max_uses ? ` / ${c.max_uses}` : '' }}</td>
                        <td class="text-xs text-gray-400">{{ c.starts_at || '…' }} → {{ c.ends_at || '…' }}</td>
                        <td>
                            <button @click="router.patch(`/admin/coupons/${c.id}/toggle`, {}, { preserveScroll: true })"
                                    :class="['text-xs rounded-full px-2 py-0.5', c.active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500']">
                                {{ c.active ? 'Active' : 'Disabled' }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
