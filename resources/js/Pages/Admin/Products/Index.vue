<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { kes } from '@/money';

const props = defineProps({ products: Object, categories: Array, filters: Object });
const f = reactive({ ...props.filters });
const apply = () => router.get('/admin/products', Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true });
</script>

<template>
    <Head title="Products — Admin" />
    <AdminLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-bold text-navy-700">Products</h1>
            <Link href="/admin/products/create" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 text-sm font-semibold">+ New Product</Link>
        </div>

        <div class="flex flex-wrap gap-3 mb-4">
            <input v-model="f.q" @keyup.enter="apply" placeholder="Search name or SKU…" class="rounded-lg border-gray-200 text-sm" />
            <select v-model="f.status" @change="apply" class="rounded-lg border-gray-200 text-sm">
                <option value="">All statuses</option>
                <option value="published">Published</option>
                <option value="draft">Draft</option>
                <option value="archived">Archived</option>
            </select>
            <select v-model="f.category" @change="apply" class="rounded-lg border-gray-200 text-sm">
                <option value="">All categories</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-400 uppercase border-b">
                        <th class="p-3">Product</th><th>SKU</th><th>Categories</th><th>Variants</th><th>Stock</th><th>From</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in products.data" :key="p.id" class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="p-3 font-medium">{{ p.name }} <span v-if="p.featured" class="text-gold" title="Featured">★</span></td>
                        <td class="font-mono text-xs">{{ p.sku_root }}</td>
                        <td class="text-xs text-gray-500">{{ p.categories.join(', ') }}</td>
                        <td>{{ p.variant_count }}</td>
                        <td :class="p.stock <= 5 ? 'text-red-600 font-bold' : ''">{{ p.stock }}</td>
                        <td>{{ kes(p.min_price_cents) }}</td>
                        <td>
                            <span :class="['text-xs rounded-full px-2 py-0.5',
                                           p.status === 'published' ? 'bg-emerald-50 text-emerald-700' : p.status === 'draft' ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-500']">
                                {{ p.status }}
                            </span>
                        </td>
                        <td class="text-right pr-3">
                            <Link :href="`/admin/products/${p.id}/edit`" class="text-navy-700 hover:underline text-xs">Edit</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination :links="products.links" />
    </AdminLayout>
</template>
